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
        $this->middleware('permission:create-pages')->only(['create', 'store']);
        $this->middleware('permission:edit-pages')->only(['edit', 'update']);
        $this->middleware('permission:delete-pages')->only('destroy');
    }

    public function index(Request $request): View
    {
        $pages = Page::query()->withTranslation()
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('key', 'like', '%'.$request->string('q').'%')
                    ->orWhereTranslationLike('title', '%'.$request->string('q').'%');
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->orderBy('sort_order')->paginate(15)->withQueryString();

        return view('admin.cms_pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.cms_pages.form', ['page' => new Page]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->validated($request));
        return redirect()->route('cms-pages.edit', $page)->with('message', 'Page created successfully.');
    }

    public function edit(Page $cms_page): View
    {
        $cms_page->load('sections.translations');
        return view('admin.cms_pages.form', ['page' => $cms_page]);
    }

    public function update(Request $request, Page $cms_page): RedirectResponse
    {
        $cms_page->update($this->validated($request, $cms_page));
        return back()->with('message', 'Page updated successfully.');
    }

    public function destroy(Page $cms_page): RedirectResponse
    {
        $cms_page->delete();
        return back()->with('message', 'Page deleted successfully.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'alpha_dash', 'max:100', 'unique:pages,key,'.($page?->id ?? 'NULL')],
            'template' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'en.title' => ['required', 'string', 'max:255'],
            'en.slug' => ['nullable', 'string', 'max:255'],
            'en.meta_title' => ['nullable', 'string', 'max:255'],
            'en.meta_description' => ['nullable', 'string'],
            'en.meta_keywords' => ['nullable', 'string', 'max:255'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] ??= 0;
        return $data;
    }
}
