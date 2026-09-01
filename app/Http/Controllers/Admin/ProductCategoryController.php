<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-product_categories|create-product_categories|edit-product_categories|delete-product_categories', ['only' => ['index','show']]);
        $this->middleware('permission:create-product_categories', ['only' => ['create','store']]);
        $this->middleware('permission:edit-product_categories', ['only' => ['edit']]);
        $this->middleware('permission:delete-product_categories', ['only' => ['destroy']]);
    }

    function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = ProductCategory::whereTranslation('title', $title)->count();

        if ($count > 0) {
            $slug .= '-' . $count;
        }

        return $slug;
    }

    public function index()
    {
        $categories = ProductCategory::withCount('products')->paginate(10);
        return view('admin.product_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.product_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $filename = null;
            if ($request->hasFile('image')) {
                $filename = $this->imageUploadService->upload($request->file('image'));
            }

            ProductCategory::create([
                'image' => $filename,
                'is_active' => $request->boolean('is_active', true),
                'row' => $request->row ?? 0,
                'az' => [
                    'title' => $request->az_title,
                    'slug' => $this->generateUniqueSlug($request->az_title),
                ],
                'en' => [
                    'title' => $request->en_title,
                    'slug' => $this->generateUniqueSlug($request->en_title),
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'slug' => $this->generateUniqueSlug($request->ru_title),
                ],
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('product_categories.index')->with('message', 'Kateqoriya uğurla əlavə edildi');
    }

    public function edit(ProductCategory $productCategory)
    {
        return view('admin.product_categories.edit', compact('productCategory'));
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $productCategory->image = $this->imageUploadService->upload($request->file('image'));
            }

            $productCategory->update([
                'image' => $productCategory->image,
                'is_active' => $request->boolean('is_active'),
                'row' => $request->row ?? 0,
                'az' => [
                    'title' => $request->az_title,
                ],
                'en' => [
                    'title' => $request->en_title,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                ],
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('message', 'Kateqoriya uğurla yeniləndi');
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();
        return redirect()->route('product_categories.index')->with('message', 'Kateqoriya uğurla silindi');
    }
}
