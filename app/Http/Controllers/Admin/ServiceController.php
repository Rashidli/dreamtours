<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-services|create-services|edit-services|delete-services', ['only' => ['index','show']]);
        $this->middleware('permission:create-services', ['only' => ['create','store']]);
        $this->middleware('permission:edit-services', ['only' => ['edit']]);
        $this->middleware('permission:delete-services', ['only' => ['destroy']]);
    }

    function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = Service::whereTranslation('title', $title)->count();

        if ($count > 0) {
            $slug .= '-' . $count;
        }

        return $slug;
    }

    public function index()
    {
        $services = Service::paginate(10);
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'az_description' => 'required',
            'en_description' => 'required',
            'ru_description' => 'required',
            'image' => 'required',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $filename = $this->imageUploadService->upload($request->file('image'));
            }

            Service::create([
                'image' => $filename,
                'icon' => $request->icon,
                'az' => [
                    'title' => $request->az_title,
                    'description' => $request->az_description,
                    'short_description' => $request->az_short_description,
                    'img_alt' => $request->az_img_alt,
                    'img_title' => $request->az_img_title,
                    'slug' => $this->generateUniqueSlug($request->az_title),
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'description' => $request->en_description,
                    'short_description' => $request->en_short_description,
                    'img_alt' => $request->en_img_alt,
                    'img_title' => $request->en_img_title,
                    'slug' => $this->generateUniqueSlug($request->en_title),
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'description' => $request->ru_description,
                    'short_description' => $request->ru_short_description,
                    'img_alt' => $request->ru_img_alt,
                    'img_title' => $request->ru_img_title,
                    'slug' => $this->generateUniqueSlug($request->ru_title),
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                ],
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }

        return redirect()->route('services.index')->with('message', 'Xidmət uğurla əlavə edildi');
    }

    public function show(Service $service)
    {
        //
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'az_description' => 'required',
            'en_description' => 'required',
            'ru_description' => 'required',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $service->image = $this->imageUploadService->upload($request->file('image'));
            }

            $service->update([
                'is_active' => $request->boolean('is_active'),
                'icon' => $request->icon,
                'az' => [
                    'title' => $request->az_title,
                    'description' => $request->az_description,
                    'short_description' => $request->az_short_description,
                    'img_alt' => $request->az_img_alt,
                    'img_title' => $request->az_img_title,
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'description' => $request->en_description,
                    'short_description' => $request->en_short_description,
                    'img_alt' => $request->en_img_alt,
                    'img_title' => $request->en_img_title,
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'description' => $request->ru_description,
                    'short_description' => $request->ru_short_description,
                    'img_alt' => $request->ru_img_alt,
                    'img_title' => $request->ru_img_title,
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                ],
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }

        return redirect()->back()->with('message', 'Xidmət uğurla yeniləndi');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('services.index')->with('message', 'Xidmət uğurla silindi');
    }
}
