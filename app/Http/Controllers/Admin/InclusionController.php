<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inclusion;
use App\Models\TourContentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Tours & CRM → Inclusions: the shared library of included / not-included lines. */
class InclusionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-inclusions')->only('index');
        $this->middleware('permission:create-inclusions')->only('store');
        $this->middleware('permission:edit-inclusions')->only('update');
        $this->middleware('permission:delete-inclusions')->only('destroy');
    }

    public function index(Request $request): View
    {
        $inclusions = Inclusion::with(['translations', 'tours.translations'])
            ->withCount('tours')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('q'), fn ($q) => $q->whereTranslationLike('title', '%'.$request->q.'%'))
            ->ordered()->get()->groupBy('type');

        return view('admin.inclusions.index', ['groups' => $inclusions]);
    }

    public function store(Request $request): RedirectResponse
    {
        $inclusion = new Inclusion($this->data($request) + ['sort_order' => (int) Inclusion::max('sort_order') + 1]);
        $this->translate($inclusion, $request)->save();

        return redirect()->route('inclusions.index')->with('message', '“'.$inclusion->title.'” added. Tick it on any tour under “What’s included”.');
    }

    public function update(Request $request, Inclusion $inclusion): RedirectResponse
    {
        $inclusion->fill($this->data($request) + ['sort_order' => $request->integer('sort_order', $inclusion->sort_order)]);
        $this->translate($inclusion, $request)->save();
        $count = $inclusion->tours()->count();

        return back()->with('message', '“'.$inclusion->title.'” saved'.($count ? ' — updated on '.$count.' '.str('tour')->plural($count).'.' : '.'));
    }

    public function destroy(Inclusion $inclusion): RedirectResponse
    {
        $count = $inclusion->tours()->count();
        $title = $inclusion->title;
        $inclusion->delete();

        return back()->with('message', '“'.$title.'” deleted'.($count ? ' and removed from '.$count.' '.str('tour')->plural($count).'.' : '.'));
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Inclusion::TYPES))],
            'icon' => ['nullable', Rule::in(array_keys(TourContentItem::ICONS))],
            'en.title' => ['required', 'string', 'max:255'],
            'en.description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return ['type' => $data['type'], 'icon' => $data['icon'] ?? null, 'is_active' => $request->boolean('is_active', !$request->has('is_active_present'))];
    }

    private function translate(Inclusion $inclusion, Request $request): Inclusion
    {
        $inclusion->translateOrNew('en')->title = trim($request->input('en.title'));
        $inclusion->translateOrNew('en')->description = trim((string) $request->input('en.description')) ?: null;

        return $inclusion;
    }
}
