<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerStatus;
use App\Models\LeadStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Editable pipeline stages (§3). Built-in stages (with a key) drive the automations,
 * so they can be renamed and recoloured but not removed or change kind; custom
 * stages can be added, reordered, archived, or deleted while unused.
 */
class LeadStageController extends Controller
{
    public function __construct() { $this->middleware('permission:manage-lead-stages'); }

    public function index(): View
    {
        return view('admin.leads.stages', ['stages' => LeadStage::withCount('leads')->orderByRaw('archived_at is not null')->orderBy('sort_order')->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:60', 'unique:lead_stages,name'], 'color' => ['required', Rule::in(CustomerStatus::COLORS)], 'kind' => ['required', Rule::in(array_keys(LeadStage::KINDS))]]);
        // New custom stages go before the closed (won / lost / spam) ones.
        $position = $d['kind'] === 'open' ? (int) LeadStage::where('kind', 'open')->max('sort_order') + 1 : (int) LeadStage::max('sort_order') + 1;
        LeadStage::where('sort_order', '>=', $position)->increment('sort_order');
        LeadStage::create($d + ['sort_order' => $position]);

        return back()->with('message', 'Stage added.');
    }

    public function update(Request $r, LeadStage $stage): RedirectResponse
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:60', Rule::unique('lead_stages', 'name')->ignore($stage)], 'color' => ['required', Rule::in(CustomerStatus::COLORS)], 'kind' => ['nullable', Rule::in(array_keys(LeadStage::KINDS))]]);
        if ($stage->key) {
            unset($d['kind']); // built-in stages keep their meaning
        }
        $stage->update(array_filter($d));

        return back()->with('message', 'Stage saved.');
    }

    public function move(Request $r, LeadStage $stage): RedirectResponse
    {
        $neighbour = LeadStage::whereNull('archived_at')->where('sort_order', $r->input('direction') === 'up' ? '<' : '>', $stage->sort_order)
            ->orderBy('sort_order', $r->input('direction') === 'up' ? 'desc' : 'asc')->first();
        if ($neighbour) {
            [$stage->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $stage->sort_order];
            $stage->save();
            $neighbour->save();
        }

        return back();
    }

    public function archive(LeadStage $stage): RedirectResponse
    {
        abort_if($stage->key, 422, 'Built-in stages cannot be archived.');
        $stage->update(['archived_at' => $stage->archived_at ? null : now()]);

        return back()->with('message', $stage->archived_at ? 'Stage archived. Leads in it keep it.' : 'Stage restored.');
    }

    public function destroy(LeadStage $stage): RedirectResponse
    {
        abort_if($stage->key, 422, 'Built-in stages cannot be deleted.');
        if ($stage->leads()->withTrashed()->exists()) {
            return back()->withErrors(['stage' => "“{$stage->name}” has leads. Move them or archive the stage instead."]);
        }
        $stage->delete();

        return back()->with('message', 'Stage deleted.');
    }
}
