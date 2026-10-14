<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageSectionController extends Controller
{
    use HandlesMediaUploads;

    public function __construct()
    {
        $this->middleware('permission:list-page-sections')->only('index');
        $this->middleware('permission:edit-page-sections')->only(['edit', 'update']);
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



    public function edit(PageSection $page_section): View
    {
        return view('admin.page_sections.form', ['section' => $page_section, 'pages' => Page::withTranslation()->get()]);
    }

    public function update(Request $request, PageSection $page_section): RedirectResponse
    {
        $page_section->update($this->validated($request, $page_section));
        return back()->with('message', 'Section updated.');
    }


    /** One block of rules per configured locale, so adding a language needs no change here. */
    private function localeRules(): array
    {
        $rules = [];

        foreach (config('translatable.locales') as $locale) {
            $rules["$locale.eyebrow"] = ['nullable', 'string', 'max:255'];
            $rules["$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["$locale.subtitle"] = ['nullable', 'string'];
            $rules["$locale.body"] = ['nullable', 'string'];
            $rules["$locale.button_text"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    private function validated(Request $request, ?PageSection $section = null): array
    {
        $data = $request->validate($this->mediaRules('image') + $this->mediaRules('background_image')
            + $this->mediaRules('image_2') + $this->mediaRules('image_3') + [
            'alt' => ['nullable', 'array'],
            'alt.*' => ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'options_json' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
        ] + $this->localeRules());
        $this->resolveMedia($request, $data, 'image', $section?->getRawOriginal('image'));
        $this->resolveMedia($request, $data, 'background_image', $section?->getRawOriginal('background_image'));
        $data['is_active'] = $request->boolean('is_active');
        $options = $request->filled('options_json') ? \Illuminate\Support\Arr::except((array) json_decode($request->string('options_json')->toString(), true), ['images', 'alt']) : [];
        // Extra collage photos and alt texts live in options, outside the free-form JSON editor.
        foreach (['image_2', 'image_3'] as $slot) {
            $this->resolveMedia($request, $data, $slot, $section?->options['images'][$slot] ?? null);
            $options['images'][$slot] = $data[$slot];
            unset($data[$slot]);
        }
        $options['images'] = array_filter($options['images']);
        $options['alt'] = array_filter(array_map(fn ($v) => trim((string) $v), $data['alt'] ?? []));
        unset($data['alt'], $data['options_json']);
        $data['options'] = array_filter($options) ?: null;
        return $data;
    }
}
