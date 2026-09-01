<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterPage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FooterPageController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-footer_pages|create-footer_pages|edit-footer_pages|delete-footer_pages', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-footer_pages', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-footer_pages', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-footer_pages', ['only' => ['destroy']]);
    }

    function generateUniqueSlug($title, $id = null)
    {
        $slug = Str::slug($title);
        $query = FooterPage::whereTranslation('slug', $slug);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        $count = $query->count();

        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        return $slug;
    }

    public function index()
    {
        $footer_pages = FooterPage::paginate(10);
        return view('admin.footer_pages.index', compact('footer_pages'));
    }

    public function create()
    {
        return view('admin.footer_pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'type' => 'required|unique:footer_pages,type',
        ]);

        DB::beginTransaction();
        try {
            $filename = null;
            if ($request->hasFile('image')) {
                $filename = $this->imageUploadService->upload($request->file('image'));
            }

            FooterPage::create([
                'image' => $filename,
                'type' => $request->type,
                'is_active' => $request->boolean('is_active', true),
                'az' => [
                    'title' => $request->az_title,
                    'slug' => $this->generateUniqueSlug($request->az_title),
                    'description' => $request->az_description,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'slug' => $this->generateUniqueSlug($request->en_title),
                    'description' => $request->en_description,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'slug' => $this->generateUniqueSlug($request->ru_title),
                    'description' => $request->ru_description,
                ]
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->route('footer_pages.index')->with('message', 'Səhifə uğurla əlavə edildi');
    }

    public function edit(FooterPage $footer_page)
    {
        return view('admin.footer_pages.edit', compact('footer_page'));
    }

    public function update(Request $request, FooterPage $footer_page)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'type' => 'required|unique:footer_pages,type,' . $footer_page->id,
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $footer_page->image = $this->imageUploadService->upload($request->file('image'));
            }

            $footer_page->update([
                'type' => $request->type,
                'is_active' => $request->boolean('is_active'),
                'az' => [
                    'title' => $request->az_title,
                    'slug' => $this->generateUniqueSlug($request->az_title, $footer_page->id),
                    'description' => $request->az_description,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'slug' => $this->generateUniqueSlug($request->en_title, $footer_page->id),
                    'description' => $request->en_description,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'slug' => $this->generateUniqueSlug($request->ru_title, $footer_page->id),
                    'description' => $request->ru_description,
                ]
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('message', 'Səhifə uğurla yeniləndi');
    }

    public function destroy(FooterPage $footer_page)
    {
        $footer_page->delete();
        return redirect()->route('footer_pages.index')->with('message', 'Səhifə uğurla silindi');
    }
}
