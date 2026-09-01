<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageSectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-page-sections')->only('index');
        $this->middleware('permission:create-page-sections')->only(['create', 'store']);
        $this->middleware('permission:edit-page-sections')->only(['edit', 'update']);
        $this->middleware('permission:delete-page-sections')->only('destroy');
    }

    public function index(Request $request): View
    {
        $sections = PageSection::query()->with(['page', 'translations'])
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('key', 'like', '%'.$request->string('q').'%')
                    ->orWhereTranslationLike('title', '%'.$request->string('q').'%');
            }))
            ->when($request->filled('page_id'), fn ($query) => $query->where('page_id', $request->integer('page_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->orderBy('page_id')->orderBy('sort_order')->paginate(15)->withQueryString();
        $pages = Page::query()->withTranslation()->orderByTranslation('title')->get();
        return view('admin.page_sections.index', compact('sections', 'pages'));
    }

    public function create(Request $request): View
    {
        return view('admin.page_sections.form', [
            'section' => new PageSection(['page_id' => $request->integer('page_id') ?: null]),
            'pages' => Page::withTranslation()->orderByTranslation('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PageSection::create($this->validated($request));
        return redirect()->route('page-sections.index')->with('message', 'Section created successfully.');
    }

    public function edit(PageSection $page_section): View
    {
        return view('admin.page_sections.form', ['section' => $page_section, 'pages' => Page::withTranslation()->get()]);
    }

    public function update(Request $request, PageSection $page_section): RedirectResponse
    {
        $page_section->update($this->validated($request, $page_section));
        return back()->with('message', 'Section updated successfully.');
    }

    public function destroy(PageSection $page_section): RedirectResponse
    {
        $page_section->delete();
        return back()->with('message', 'Section deleted successfully.');
    }

    private function validated(Request $request, ?PageSection $section = null): array
    {
        $data = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'key' => ['required', 'alpha_dash', 'max:100', 'unique:page_sections,key,'.($section?->id ?? 'NULL').',id,page_id,'.$request->integer('page_id')],
            'type' => ['required', 'string', 'max:80'],
            'image' => ['nullable', 'string', 'max:500'],
            'background_image' => ['nullable', 'string', 'max:500'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'options_json' => ['nullable', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'en.eyebrow' => ['nullable', 'string', 'max:255'],
            'en.title' => ['nullable', 'string', 'max:255'],
            'en.subtitle' => ['nullable', 'string'],
            'en.body' => ['nullable', 'string'],
            'en.button_text' => ['nullable', 'string', 'max:255'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] ??= 0;
        $data['options'] = $request->filled('options_json') ? json_decode($request->string('options_json')->toString(), true) : null;
        unset($data['options_json']);
        return $data;
    }
}
