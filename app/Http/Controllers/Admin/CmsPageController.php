<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-pages')->only('index');
        $this->middleware('permission:edit-pages')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $pages = Page::query()->withTranslation()->withCount('sections')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('key', 'like', '%'.$request->string('q').'%')
                    ->orWhereTranslationLike('title', '%'.$request->string('q').'%');
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->orderBy('sort_order')->paginate(15)->withQueryString();

        return view('admin.cms_pages.index', compact('pages'));
    }

    public function edit(Page $cms_page): View
    {
        $cms_page->load('sections.translations');
        return view('admin.cms_pages.form', ['page' => $cms_page]);
    }

    public function update(Request $request, Page $cms_page): RedirectResponse
    {
        $cms_page->update($this->validated($request));
        return back()->with('message', 'Page updated.');
    }

    private function validated(Request $request): array
    {
        $rules = ['is_active' => ['nullable', 'boolean']];

        // One block of rules per configured locale, so adding a language needs no code change here.
        foreach (config('translatable.locales') as $locale) {
            $rules["$locale.title"] = ['required', 'string', 'max:255'];
            $rules["$locale.meta_title"] = ['nullable', 'string', 'max:255'];
            $rules["$locale.meta_description"] = ['nullable', 'string'];
            $rules["$locale.meta_keywords"] = ['nullable', 'string', 'max:255'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
