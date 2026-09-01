<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioImage;
use App\Models\Service;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
    }

    function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = Portfolio::whereTranslation('title', $title)->count();
        if ($count > 0) {
            $slug .= '-' . $count;
        }
        return $slug;
    }

    public function index()
    {
        $portfolios = Portfolio::with('service')->latest()->paginate(10);
        return view('admin.portfolios.index', compact('portfolios'));
    }

    public function create()
    {
        $services = Service::active()->get();
        return view('admin.portfolios.create', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'image' => 'required|image',
        ]);

        DB::beginTransaction();
        try {
            $filename = null;
            if ($request->hasFile('image')) {
                $filename = $this->imageUploadService->upload($request->file('image'));
            }

            $portfolio = Portfolio::create([
                'image' => $filename,
                'service_id' => $request->service_id,
                'client' => $request->client,
                'location' => $request->location,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured'),
                'order' => $request->order ?? 0,
                'az' => [
                    'title' => $request->az_title,
                    'short_description' => $request->az_short_description,
                    'description' => $request->az_description,
                    'slug' => $this->generateUniqueSlug($request->az_title),
                    'img_alt' => $request->az_img_alt,
                    'img_title' => $request->az_img_title,
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'short_description' => $request->en_short_description,
                    'description' => $request->en_description,
                    'slug' => $this->generateUniqueSlug($request->en_title),
                    'img_alt' => $request->en_img_alt,
                    'img_title' => $request->en_img_title,
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'short_description' => $request->ru_short_description,
                    'description' => $request->ru_description,
                    'slug' => $this->generateUniqueSlug($request->ru_title),
                    'img_alt' => $request->ru_img_alt,
                    'img_title' => $request->ru_img_title,
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                ],
            ]);

            // Əlavə şəkillər
            if ($request->hasFile('images')) {
                $order = 0;
                foreach ($request->file('images') as $image) {
                    $imagePath = $this->imageUploadService->upload($image);
                    PortfolioImage::create([
                        'portfolio_id' => $portfolio->id,
                        'image' => $imagePath,
                        'order' => $order++,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('portfolios.index')->with('message', 'Portfolio uğurla əlavə edildi');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(Portfolio $portfolio)
    {
        $services = Service::active()->get();
        return view('admin.portfolios.edit', compact('portfolio', 'services'));
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $portfolio->image = $this->imageUploadService->upload($request->file('image'));
            }

            $portfolio->update([
                'service_id' => $request->service_id,
                'client' => $request->client,
                'location' => $request->location,
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'order' => $request->order ?? 0,
                'az' => [
                    'title' => $request->az_title,
                    'short_description' => $request->az_short_description,
                    'description' => $request->az_description,
                    'img_alt' => $request->az_img_alt,
                    'img_title' => $request->az_img_title,
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'short_description' => $request->en_short_description,
                    'description' => $request->en_description,
                    'img_alt' => $request->en_img_alt,
                    'img_title' => $request->en_img_title,
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'short_description' => $request->ru_short_description,
                    'description' => $request->ru_description,
                    'img_alt' => $request->ru_img_alt,
                    'img_title' => $request->ru_img_title,
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                ],
            ]);

            // Əlavə şəkillər
            if ($request->hasFile('images')) {
                $maxOrder = $portfolio->images()->max('order') ?? -1;
                foreach ($request->file('images') as $image) {
                    $imagePath = $this->imageUploadService->upload($image);
                    PortfolioImage::create([
                        'portfolio_id' => $portfolio->id,
                        'image' => $imagePath,
                        'order' => ++$maxOrder,
                    ]);
                }
            }

            // Şəkil silmə
            if ($request->delete_images) {
                PortfolioImage::whereIn('id', $request->delete_images)->delete();
            }

            DB::commit();
            return redirect()->back()->with('message', 'Portfolio uğurla yeniləndi');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Portfolio $portfolio)
    {
        $portfolio->delete();
        return redirect()->route('portfolios.index')->with('message', 'Portfolio uğurla silindi');
    }
}
