<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Central Privacy / Terms / Refund / Waiver texts. There is no delete: unpublish instead. */
class LegalDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-legal-documents')->only('index');
        // Booking staff open the version a customer accepted from the booking page.
        $this->middleware('permission:list-legal-documents|list-bookings')->only('version');
        $this->middleware('permission:edit-legal-documents')->only(['edit', 'update']);
    }

    public function index(): View
    {
        $documents = LegalDocument::with('currentVersion')
            ->withCount('versions')
            ->orderBy('sort_order')->get();
        $acceptances = LegalDocumentVersion::query()
            ->join('booking_legal_acceptances', 'booking_legal_acceptances.legal_document_version_id', '=', 'legal_document_versions.id')
            ->selectRaw('legal_document_id, count(*) as total')->groupBy('legal_document_id')->pluck('total', 'legal_document_id');

        return view('admin.legal_documents.index', compact('documents', 'acceptances'));
    }

    public function edit(LegalDocument $legalDocument): View
    {
        $legalDocument->load(['versions' => fn ($q) => $q->with('author')->withCount('bookings')]);

        return view('admin.legal_documents.form', ['document' => $legalDocument]);
    }

    public function update(Request $request, LegalDocument $legalDocument): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'heading' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'show_on_tours' => ['nullable', 'boolean'],
            'require_acceptance' => ['nullable', 'boolean'],
        ]);
        foreach (['is_published', 'show_on_tours', 'require_acceptance'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $newVersion = $legalDocument->saveWithVersion($data, $request->user()?->id);
        $message = $newVersion
            ? 'Saved as version '.$legalDocument->versions()->max('version').'. New bookings accept this version; earlier bookings keep the version they accepted.'
            : 'Settings saved. The wording did not change, so the version stays the same.';

        return redirect()->route('legal-documents.edit', $legalDocument)->with('message', $message);
    }

    /** Read-only view of one stored version, e.g. the text a customer accepted. */
    public function version(LegalDocumentVersion $version): View
    {
        $version->load(['document', 'author'])->loadCount('bookings');

        return view('admin.legal_documents.version', compact('version'));
    }
}
