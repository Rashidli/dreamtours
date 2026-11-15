<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerImport;
use App\Models\CustomerStatus;
use App\Models\CustomerTag;
use App\Support\Activity;
use App\Support\CustomerImport as Importer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Customer import in four steps: upload → map columns → preview (nothing saved) → import. */
class CustomerImportController extends Controller
{
    public function __construct() { $this->middleware('permission:import-customers'); }

    public function create(): View
    {
        return view('admin.customers.import.upload', ['imports' => CustomerImport::with('user')->latest('id')->limit(10)->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $r->validate(['file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx']], ['file.mimes' => 'Upload a .csv or .xlsx file (in Excel: File → Save As → CSV or Excel Workbook).']);
        $file = $r->file('file');
        $extension = strtolower($file->getClientOriginalExtension()) === 'xlsx' ? 'xlsx' : 'csv';
        $path = $file->storeAs('imports', Str::uuid().'.'.$extension, 'local'); // private disk, never public

        try {
            $rows = Importer::rows($path);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => 'This file could not be read. Save it again as CSV (UTF-8) or Excel Workbook (.xlsx) and retry.']);
        }
        if (count($rows) < 2) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => 'The file needs a header row and at least one customer.']);
        }
        if (count($rows) > Importer::MAX_ROWS + 1) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => 'Up to '.number_format(Importer::MAX_ROWS).' customers per file. Split the file and import the parts one by one.']);
        }

        $import = CustomerImport::create([
            'user_id' => $r->user()->id,
            'filename' => mb_substr($file->getClientOriginalName(), 0, 250),
            'path' => $path,
            'columns' => $rows[0],
            'mapping' => Importer::guessMapping($rows[0]),
            'options' => ['duplicates' => 'skip', 'status_id' => CustomerStatus::active()->where('name', 'New')->value('id'), 'tag_id' => null],
        ]);

        return redirect()->route('customer-imports.edit', $import);
    }

    public function edit(CustomerImport $import): View
    {
        $this->pending($import);
        $rows = Importer::rows($import->path);

        return view('admin.customers.import.map', [
            'import' => $import,
            'samples' => array_slice($rows, 1, 3),
            'rowCount' => count($rows) - 1,
            'statuses' => CustomerStatus::active()->get(),
            'tags' => CustomerTag::active()->get(),
        ]);
    }

    public function update(Request $r, CustomerImport $import): RedirectResponse
    {
        $this->pending($import);
        $d = $r->validate([
            'mapping' => ['array'],
            'mapping.*' => ['nullable', Rule::in(array_keys(Importer::FIELDS))],
            'duplicates' => ['required', Rule::in(['skip', 'update'])],
            'status_id' => ['nullable', 'exists:customer_statuses,id'],
            'tag_id' => ['nullable', 'exists:customer_tags,id'],
        ]);
        $mapping = array_filter($d['mapping'] ?? []);
        $used = array_count_values($mapping);
        if ($twice = array_keys(array_filter($used, fn ($n) => $n > 1))) {
            return back()->withInput()->withErrors(['mapping' => 'Each field can be used for one column only: '.implode(', ', array_map(fn ($f) => Importer::FIELDS[$f][0], $twice)).'.']);
        }
        if (!array_intersect($mapping, ['first_name', 'full_name']) || !array_intersect($mapping, ['email', 'phone', 'whatsapp'])) {
            return back()->withInput()->withErrors(['mapping' => 'Choose a column for the name (first name or full name) and one for email or phone.']);
        }
        $import->update(['mapping' => $mapping, 'options' => ['duplicates' => $d['duplicates'], 'status_id' => $d['status_id'] ?? null, 'tag_id' => $d['tag_id'] ?? null]]);

        return redirect()->route('customer-imports.show', $import);
    }

    /** Step 3: every row checked, nothing saved. */
    public function show(CustomerImport $import): View
    {
        if ($import->status === 'imported') {
            return view('admin.customers.import.done', ['import' => $import]);
        }
        $results = Importer::analyse($import);
        $counts = collect($results)->countBy('outcome');

        return view('admin.customers.import.preview', [
            'import' => $import,
            'results' => $results,
            'counts' => ['new' => $counts['new'] ?? 0, 'duplicate' => $counts['duplicate'] ?? 0, 'error' => $counts['error'] ?? 0],
            'status' => CustomerStatus::find($import->options['status_id'] ?? null),
            'tag' => CustomerTag::find($import->options['tag_id'] ?? null),
        ]);
    }

    /** Step 4: save, then remove the uploaded file. */
    public function commit(Request $r, CustomerImport $import): RedirectResponse
    {
        $this->pending($import);
        $summary = Importer::commit($import, $r->user()->name);
        Storage::disk('local')->delete($import->path);
        $import->update(['summary' => $summary, 'status' => 'imported', 'imported_at' => now()]);
        Activity::record('customers_imported', null, $summary + ['file' => $import->filename, 'duplicates' => $import->options['duplicates'] === 'update' ? 'filled empty fields' : 'skipped'], label: 'Customer import #'.$import->id);

        return redirect()->route('customer-imports.show', $import);
    }

    /** Rows that will not be imported, with the reason, as CSV. */
    public function problems(CustomerImport $import): StreamedResponse
    {
        $this->pending($import);
        $rows = collect(Importer::analyse($import))->where('outcome', '!=', 'new');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Line', 'Result', 'Reason', 'First name', 'Last name', 'Email', 'Phone']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['line'], $row['outcome'], implode(' ', $row['messages']), $row['contact']['first_name'], $row['contact']['last_name'], $row['contact']['email'], $row['contact']['phone']]);
            }
            fclose($out);
        }, 'import-problems-'.$import->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pending(CustomerImport $import): void
    {
        abort_if($import->status === 'imported', 409, 'This file has already been imported.');
        abort_unless(Storage::disk('local')->exists($import->path), 410, 'The uploaded file is no longer available. Upload it again.');
    }
}
