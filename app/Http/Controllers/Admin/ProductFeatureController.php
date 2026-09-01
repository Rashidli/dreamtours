<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductFeatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-product_features|create-product_features|edit-product_features|delete-product_features', ['only' => ['index','show']]);
        $this->middleware('permission:create-product_features', ['only' => ['create','store']]);
        $this->middleware('permission:edit-product_features', ['only' => ['edit']]);
        $this->middleware('permission:delete-product_features', ['only' => ['destroy']]);
    }

    public function index()
    {
        $features = ProductFeature::withCount('products')->paginate(10);
        return view('admin.product_features.index', compact('features'));
    }

    public function create()
    {
        return view('admin.product_features.create');
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
            ProductFeature::create([
                'is_active' => $request->boolean('is_active', true),
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

        return redirect()->route('product_features.index')->with('message', 'Xüsusiyyət uğurla əlavə edildi');
    }

    public function edit(ProductFeature $productFeature)
    {
        return view('admin.product_features.edit', compact('productFeature'));
    }

    public function update(Request $request, ProductFeature $productFeature)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $productFeature->update([
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

        return redirect()->back()->with('message', 'Xüsusiyyət uğurla yeniləndi');
    }

    public function destroy(ProductFeature $productFeature)
    {
        $productFeature->products()->detach();
        $productFeature->delete();
        return redirect()->route('product_features.index')->with('message', 'Xüsusiyyət uğurla silindi');
    }
}
