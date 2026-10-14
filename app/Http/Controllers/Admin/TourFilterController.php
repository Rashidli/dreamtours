<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourFilter;
use App\Models\TourFilterOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Filter groups and their options for the One Day / Multi Day listings, all on one page. */
class TourFilterController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-tour-filters')->only('index');
        $this->middleware('permission:create-tour-filters')->only(['store', 'storeOption']);
        $this->middleware('permission:edit-tour-filters')->only(['update', 'updateOption']);
        $this->middleware('permission:delete-tour-filters')->only(['destroy', 'destroyOption']);
    }

    public function index(): View
    {
        $filters = TourFilter::with(['options' => fn ($q) => $q->withCount('tours')])->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.tour_filters.index', ['filters' => $filters]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $this->groupData($r);
        $data['sort_order'] ??= (int) TourFilter::max('sort_order') + 1;
        $filter = TourFilter::create($data + ['is_active' => true]);

        return redirect()->to(route('tour-filters.index').'#filter-'.$filter->id)->with('message', "Filter “{$filter->name}” added — now add its options.");
    }

    public function update(Request $r, TourFilter $tour_filter): RedirectResponse
    {
        $tour_filter->update($this->groupData($r) + ['is_active' => $r->boolean('is_active')]);

        return redirect()->to(route('tour-filters.index').'#filter-'.$tour_filter->id)->with('message', 'Filter saved.');
    }

    public function destroy(TourFilter $tour_filter): RedirectResponse
    {
        // Options and their links to tours go with it; the tours themselves are untouched.
        $tour_filter->delete();

        return redirect()->route('tour-filters.index')->with('message', 'Filter removed. Tours are unchanged.');
    }

    public function storeOption(Request $r, TourFilter $tour_filter): RedirectResponse
    {
        $data = $r->validate(['name' => ['required', 'string', 'max:100', Rule::unique('tour_filter_options')->where('tour_filter_id', $tour_filter->id)]]);
        $tour_filter->options()->create($data + ['sort_order' => (int) $tour_filter->options()->max('sort_order') + 1, 'is_active' => true]);

        return redirect()->to(route('tour-filters.index').'#filter-'.$tour_filter->id)->with('message', 'Option added. Assign it to tours on each tour’s page.');
    }

    public function updateOption(Request $r, TourFilterOption $option): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('tour_filter_options')->where('tour_filter_id', $option->tour_filter_id)->ignore($option->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $option->update(['name' => $data['name'], 'sort_order' => $data['sort_order'] ?? $option->sort_order, 'is_active' => $r->boolean('is_active')]);

        return redirect()->to(route('tour-filters.index').'#filter-'.$option->tour_filter_id)->with('message', 'Option saved.');
    }

    public function destroyOption(TourFilterOption $option): RedirectResponse
    {
        $used = $option->tours()->count();
        $option->tours()->detach();
        $option->delete();

        return redirect()->to(route('tour-filters.index').'#filter-'.$option->tour_filter_id)->with('message', $used ? "Option removed and unassigned from {$used} ".str('tour')->plural($used).'.' : 'Option removed.');
    }

    private function groupData(Request $r): array
    {
        return $r->validate([
            'name' => ['required', 'string', 'max:100'],
            'listing' => ['required', Rule::in(array_keys(TourFilter::LISTINGS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
