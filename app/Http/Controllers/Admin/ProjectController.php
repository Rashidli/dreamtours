<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-projects|create-projects|edit-projects|delete-projects', ['only' => ['index','show']]);
        $this->middleware('permission:create-projects', ['only' => ['create','store']]);
        $this->middleware('permission:edit-projects', ['only' => ['edit']]);
        $this->middleware('permission:delete-projects', ['only' => ['destroy']]);
    }

    function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = Project::whereTranslation('title', $title)->count();

        if ($count > 0) {
            $slug .= '-' . $count;
        }

        return $slug;
    }

    public function index()
    {
        $projects = Project::paginate(10);
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $services = Service::active()->get();
        return view('admin.projects.create', compact('services'));
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

            $galleryImages = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $galleryImages[] = $this->imageUploadService->upload($file);
                }
            }

            $data = [
                'image' => $filename,
                'service_id' => $request->service_id,
                'gallery' => !empty($galleryImages) ? $galleryImages : null,
                'az' => [
                    'title' => $request->az_title,
                    'description' => $request->az_description,
                    'short_description' => $request->az_short_description,
                    'img_alt' => $request->az_img_alt,
                    'img_title' => $request->az_img_title,
                    'slug' => $this->generateUniqueSlug($request->az_title),
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                    'client' => $request->az_client,
                    'location' => $request->az_location,
                    'delivery_date' => $request->az_delivery_date,
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
                    'client' => $request->en_client,
                    'location' => $request->en_location,
                    'delivery_date' => $request->en_delivery_date,
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
                    'client' => $request->ru_client,
                    'location' => $request->ru_location,
                    'delivery_date' => $request->ru_delivery_date,
                ],
            ];

            if ($request->hasFile('before_image')) {
                $data['before_image'] = $this->imageUploadService->upload($request->file('before_image'));
            }
            if ($request->hasFile('after_image')) {
                $data['after_image'] = $this->imageUploadService->upload($request->file('after_image'));
            }

            Project::create($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }

        return redirect()->route('projects.index')->with('message', 'Layihə uğurla əlavə edildi');
    }

    public function show(Project $project)
    {
        //
    }

    public function edit(Project $project)
    {
        $services = Service::active()->get();
        return view('admin.projects.edit', compact('project', 'services'));
    }

    public function update(Request $request, Project $project)
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
                $project->image = $this->imageUploadService->upload($request->file('image'));
            }

            $data = [
                'is_active' => $request->boolean('is_active'),
                'service_id' => $request->service_id,
                'az' => [
                    'title' => $request->az_title,
                    'description' => $request->az_description,
                    'short_description' => $request->az_short_description,
                    'img_alt' => $request->az_img_alt,
                    'img_title' => $request->az_img_title,
                    'meta_title' => $request->az_meta_title,
                    'meta_description' => $request->az_meta_description,
                    'client' => $request->az_client,
                    'location' => $request->az_location,
                    'delivery_date' => $request->az_delivery_date,
                ],
                'en' => [
                    'title' => $request->en_title,
                    'description' => $request->en_description,
                    'short_description' => $request->en_short_description,
                    'img_alt' => $request->en_img_alt,
                    'img_title' => $request->en_img_title,
                    'meta_title' => $request->en_meta_title,
                    'meta_description' => $request->en_meta_description,
                    'client' => $request->en_client,
                    'location' => $request->en_location,
                    'delivery_date' => $request->en_delivery_date,
                ],
                'ru' => [
                    'title' => $request->ru_title,
                    'description' => $request->ru_description,
                    'short_description' => $request->ru_short_description,
                    'img_alt' => $request->ru_img_alt,
                    'img_title' => $request->ru_img_title,
                    'meta_title' => $request->ru_meta_title,
                    'meta_description' => $request->ru_meta_description,
                    'client' => $request->ru_client,
                    'location' => $request->ru_location,
                    'delivery_date' => $request->ru_delivery_date,
                ],
            ];

            if ($request->hasFile('gallery')) {
                $galleryImages = [];
                foreach ($request->file('gallery') as $file) {
                    $galleryImages[] = $this->imageUploadService->upload($file);
                }
                $data['gallery'] = $galleryImages;
            }

            if ($request->hasFile('before_image')) {
                $data['before_image'] = $this->imageUploadService->upload($request->file('before_image'));
            }
            if ($request->hasFile('after_image')) {
                $data['after_image'] = $this->imageUploadService->upload($request->file('after_image'));
            }

            $project->update($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }

        return redirect()->back()->with('message', 'Layihə uğurla yeniləndi');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('message', 'Layihə uğurla silindi');
    }
}
