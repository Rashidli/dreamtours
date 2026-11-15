<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerStatus;
use App\Models\CustomerTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Customer statuses and tags (§4): create, rename, recolour, archive. Archiving
 * keeps the label on the customers that have it; deleting is only allowed while
 * no customer uses it, so customer history never loses a label.
 */
class CustomerLabelController extends Controller
{
    private const KINDS = ['status' => CustomerStatus::class, 'tag' => CustomerTag::class];

    public function __construct() { $this->middleware('permission:manage-customer-labels'); }

    public function index(): View
    {
        return view('admin.customers.labels', [
            'statuses' => CustomerStatus::withCount('customers')->orderByRaw('archived_at is not null')->orderBy('sort_order')->get(),
            'tags' => CustomerTag::withCount('customers')->orderByRaw('archived_at is not null')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $r, string $kind): RedirectResponse
    {
        $class = $this->kind($kind);
        $class::create($this->validated($r, $kind) + ($kind === 'status' ? ['sort_order' => (int) CustomerStatus::max('sort_order') + 1] : []));

        return back()->with('message', ucfirst($kind).' created.');
    }

    public function update(Request $r, string $kind, int $id): RedirectResponse
    {
        $label = $this->kind($kind)::findOrFail($id);
        $label->update($this->validated($r, $kind, $label));

        return back()->with('message', ucfirst($kind).' updated.');
    }

    public function archive(string $kind, int $id): RedirectResponse
    {
        $label = $this->kind($kind)::findOrFail($id);
        $label->update(['archived_at' => $label->archived_at ? null : now()]);

        return back()->with('message', ucfirst($kind).($label->archived_at ? ' archived. Customers keep it; it can no longer be chosen.' : ' restored.'));
    }

    public function destroy(string $kind, int $id): RedirectResponse
    {
        $label = $this->kind($kind)::withCount('customers')->findOrFail($id);
        if ($label->customers_count > 0) {
            return back()->withErrors(['label' => "“{$label->name}” is used by {$label->customers_count} customer(s). Archive it instead, so their history keeps it."]);
        }
        $label->delete();

        return back()->with('message', ucfirst($kind).' deleted.');
    }

    /** @return class-string<Model> */
    private function kind(string $kind): string
    {
        return self::KINDS[$kind] ?? abort(404);
    }

    private function validated(Request $r, string $kind, ?Model $label = null): array
    {
        $table = $kind === 'status' ? 'customer_statuses' : 'customer_tags';
        $d = $r->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique($table, 'name')->ignore($label?->id)],
            'color' => ['required', Rule::in(CustomerStatus::COLORS)],
            'blocks_marketing' => ['nullable', 'boolean'],
        ]);
        if ($kind === 'status') {
            $d['blocks_marketing'] = $r->boolean('blocks_marketing');
        } else {
            unset($d['blocks_marketing']);
        }

        return $d;
    }
}
