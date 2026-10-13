<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NavigationItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-navigation')->only('index');
        $this->middleware('permission:create-navigation')->only(['create', 'store']);
        $this->middleware('permission:edit-navigation')->only(['edit', 'update']);
        $this->middleware('permission:delete-navigation')->only('destroy');
    }

    public function index(Request $request): View
    {
        $items = NavigationItem::with(['translations', 'parent.translations'])
            ->when($request->filled('q'), fn ($q) => $q->whereTranslationLike('title', '%'.$request->string('q').'%'))
            ->when($request->filled('location'), fn ($q) => $q->where('location', $request->string('location')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('location')->orderBy('sort_order')->paginate(20)->withQueryString();
        return view('admin.navigation.index', compact('items'));
    }

    public function create(): View { return $this->form(new NavigationItem); }
    public function edit(NavigationItem $navigation_item): View { return $this->form($navigation_item); }

    public function store(Request $request): RedirectResponse
    {
        NavigationItem::create($this->validated($request));
        return redirect()->route('navigation-items.index')->with('message', 'Navigation item created.');
    }

    public function update(Request $request, NavigationItem $navigation_item): RedirectResponse
    {
        $navigation_item->update($this->validated($request, $navigation_item));
        return back()->with('message', 'Navigation item updated.');
    }

    public function destroy(NavigationItem $navigation_item): RedirectResponse
    {
        $navigation_item->delete();
        return back()->with('message', 'Navigation item deleted.');
    }

    private function form(NavigationItem $item): View
    {
        $parents = NavigationItem::whereNull('parent_id')->whereKeyNot($item->id)->withTranslation()->orderBy('location')->orderBy('sort_order')->get();
        return view('admin.navigation.form', compact('item', 'parents'));
    }

    /** One block of rules per configured locale, so adding a language needs no change here. */
    private function localeRules(): array
    {
        return collect(config('translatable.locales'))
            ->mapWithKeys(fn (string $locale) => ["$locale.title" => ['required', 'string', 'max:255']])
            ->all();
    }

    private function validated(Request $request, ?NavigationItem $item = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:navigation_items,id', Rule::notIn([$item?->id])],
            'location' => ['required', Rule::in(['header', 'footer_company', 'footer_support', 'footer_legal'])],
            'url' => ['required', 'string', 'max:500'], 'target' => ['required', Rule::in(['_self', '_blank'])],
            'is_active' => ['nullable', 'boolean'],
        ] + $this->localeRules());
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
