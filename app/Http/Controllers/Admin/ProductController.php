<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFeature;
use App\Models\ProductImage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-products|create-products|edit-products|delete-products', ['only' => ['index','show']]);
        $this->middleware('permission:create-products', ['only' => ['create','store']]);
        $this->middleware('permission:edit-products', ['only' => ['edit']]);
        $this->middleware('permission:delete-products', ['only' => ['destroy']]);
    }

    function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = Product::whereTranslation('title', $title)->count();

        if ($count > 0) {
            $slug .= '-' . $count;
        }

        return $slug;
    }

    public function index()
    {
        $products = Product::with(['category', 'images'])->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = ProductCategory::active()->get();
        $features = ProductFeature::active()->get();
        return view('admin.products.create', compact('categories', 'features'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image',
        ]);

        DB::beginTransaction();
        try {
            $filename = null;
            if ($request->hasFile('image')) {
                $filename = $this->imageUploadService->upload($request->file('image'));
            }

            $product = Product::create([
                'image' => $filename,
                'product_category_id' => $request->product_category_id,
                'price' => $request->price,
                'old_price' => $request->old_price,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured'),
                'az' => [
                    'title' => $request->az_title,
                    'short_description' => $request->az_short_description,
                    'description' => $request->az_description,
                    'features' => $request->az_features,
                    'slug' => $this->generateUniqueSlug($request->az_title),
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                    'meta_keywords' => $request->az_meta_keywords,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'short_description' => $request->en_short_description,
                    'description' => $request->en_description,
                    'features' => $request->en_features,
                    'slug' => $this->generateUniqueSlug($request->en_title),
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                    'meta_keywords' => $request->en_meta_keywords,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'short_description' => $request->ru_short_description,
                    'description' => $request->ru_description,
                    'features' => $request->ru_features,
                    'slug' => $this->generateUniqueSlug($request->ru_title),
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                    'meta_keywords' => $request->ru_meta_keywords,
                ],
            ]);

            // Attach features
            if ($request->has('feature_ids')) {
                $product->productFeatures()->attach($request->feature_ids);
            }

            // Upload gallery images
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $index => $image) {
                    $imagePath = $this->imageUploadService->upload($image);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $imagePath,
                        'row' => $index,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('products.index')->with('message', 'Məhsul uğurla əlavə edildi');
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::active()->get();
        $features = ProductFeature::active()->get();
        $product->load('images', 'productFeatures');
        return view('admin.products.edit', compact('product', 'categories', 'features'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $product->image = $this->imageUploadService->upload($request->file('image'));
            }

            $product->update([
                'image' => $product->image,
                'product_category_id' => $request->product_category_id,
                'price' => $request->price,
                'old_price' => $request->old_price,
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'az' => [
                    'title' => $request->az_title,
                    'short_description' => $request->az_short_description,
                    'description' => $request->az_description,
                    'features' => $request->az_features,
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                    'meta_keywords' => $request->az_meta_keywords,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'short_description' => $request->en_short_description,
                    'description' => $request->en_description,
                    'features' => $request->en_features,
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                    'meta_keywords' => $request->en_meta_keywords,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'short_description' => $request->ru_short_description,
                    'description' => $request->ru_description,
                    'features' => $request->ru_features,
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                    'meta_keywords' => $request->ru_meta_keywords,
                ],
            ]);

            // Sync features
            $product->productFeatures()->sync($request->feature_ids ?? []);

            // Upload new gallery images
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $index => $image) {
                    $imagePath = $this->imageUploadService->upload($image);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $imagePath,
                        'row' => $product->images()->count() + $index,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('message', 'Məhsul uğurla yeniləndi');
    }

    public function destroy(Product $product)
    {
        $product->images()->delete();
        $product->productFeatures()->detach();
        $product->delete();
        return redirect()->route('products.index')->with('message', 'Məhsul uğurla silindi');
    }

    public function deleteImage($id)
    {
        $image = ProductImage::findOrFail($id);
        $image->delete();
        return response()->json(['success' => true]);
    }
}
