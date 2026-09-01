<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Service;
use App\Models\Tag;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-blogs|create-blogs|edit-blogs|delete-blogs', ['only' => ['index','show']]);
        $this->middleware('permission:create-blogs', ['only' => ['create','store']]);
        $this->middleware('permission:edit-blogs', ['only' => ['edit']]);
        $this->middleware('permission:delete-blogs', ['only' => ['destroy']]);
    }

    function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = Blog::whereTranslation('title', $title)->count();

        if ($count > 0) {
            $slug .= '-' . $count;
        }

        return $slug;
    }

    public function index()
    {

        $blogs = Blog::paginate(10);
        return view('admin.blogs.index', compact('blogs'));

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create()
    {
        $tags = Tag::active()->get();
        $services = Service::active()->get();
        return view('admin.blogs.create', compact('tags', 'services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'az_title'=>'required',
            'en_title'=>'required',
            'ru_title'=>'required',
            'az_img_alt'=>'nullable',
            'en_img_alt'=>'nullable',
            'ru_img_alt'=>'nullable',
            'az_img_title'=>'nullable',
            'en_img_title'=>'nullable',
            'ru_img_title'=>'nullable',
            'az_description'=>'required',
            'en_description'=>'required',
            'ru_description'=>'required',
            'image'=>'required',
        ]);
        DB::beginTransaction();
        try {
            if($request->hasFile('image')){
                $filename = $this->imageUploadService->upload($request->file('image'));
            }

            $blog = Blog::create([
                'image'=>  $filename,
                'service_id'=> $request->service_id,
                'az'=>[
                    'title'=>$request->az_title,
                    'description'=>$request->az_description,
                    'short_description'=>$request->az_short_description,
                    'img_alt'=>$request->az_img_alt,
                    'img_title'=>$request->az_img_title,
                    'slug'=>$this->generateUniqueSlug($request->az_title),
                    'meta_title'=>$request->az_meta_title,
                    'meta_description'=>$request->az_meta_description,
                ],
                'en'=>[
                    'title'=>$request->en_title,
                    'description'=>$request->en_description,
                    'short_description'=>$request->en_short_description,
                    'img_alt'=>$request->en_img_alt,
                    'img_title'=>$request->en_img_title,
                    'slug'=>$this->generateUniqueSlug($request->en_title),
                    'meta_title'=>$request->en_meta_title,
                    'meta_description'=>$request->en_meta_description,
                ],
                'ru'=>[
                    'title'=>$request->ru_title,
                    'description'=>$request->ru_description,
                    'short_description'=>$request->ru_short_description,
                    'img_alt'=>$request->ru_img_alt,
                    'img_title'=>$request->ru_img_title,
                    'slug'=>$this->generateUniqueSlug($request->ru_title),
                    'meta_title'=>$request->ru_meta_title,
                    'meta_description'=>$request->ru_meta_description,
                ]
            ]);

            if ($request->has('tags')) {
                $blog->tags()->sync($request->tags);
            }

            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }

        return redirect()->route('blogs.index')->with('message','Bloq uğurla əlavə edildi');

    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog)
    {
        $tags = Tag::active()->get();
        $services = Service::active()->get();
        return view('admin.blogs.edit', compact('blog', 'tags', 'services'));
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, Blog $blog)
    {
        $request->validate([
            'az_title'=>'required',
            'en_title'=>'required',
            'ru_title'=>'required',
            'az_img_alt'=>'nullable',
            'en_img_alt'=>'nullable',
            'ru_img_alt'=>'nullable',
            'az_img_title'=>'nullable',
            'en_img_title'=>'nullable',
            'ru_img_title'=>'nullable',
            'az_description'=>'required',
            'en_description'=>'required',
            'ru_description'=>'required',
        ]);
        DB::beginTransaction();
        try {
            if($request->hasFile('image')){
                $blog->image = $this->imageUploadService->upload($request->file('image'));
            }

            $blog->update( [
                'is_active'=> $request->boolean('is_active'),
                'service_id'=> $request->service_id,
                'az'=>[
                    'title'=>$request->az_title,
                    'img_alt'=>$request->az_img_alt,
                    'img_title'=>$request->az_img_title,
                    'description'=>$request->az_description,
                    'short_description'=>$request->az_short_description,
                    'meta_title'=>$request->az_meta_title,
                    'meta_description'=>$request->az_meta_description,
                ],
                'en'=>[
                    'title'=>$request->en_title,
                    'img_alt'=>$request->en_img_alt,
                    'img_title'=>$request->en_img_title,
                    'description'=>$request->en_description,
                    'short_description'=>$request->en_short_description,
                    'meta_title'=>$request->en_meta_title,
                    'meta_description'=>$request->en_meta_description,
                ],
                'ru'=>[
                    'title'=>$request->ru_title,
                    'img_alt'=>$request->ru_img_alt,
                    'img_title'=>$request->ru_img_title,
                    'description'=>$request->ru_description,
                    'short_description'=>$request->ru_short_description,
                    'meta_title'=>$request->ru_meta_title,
                    'meta_description'=>$request->ru_meta_description,
                ]

            ]);

            $blog->tags()->sync($request->tags ?? []);

            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }
        return redirect()->back()->with('message','Bloq uğurla yeniləndi');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {

        $blog->delete();
        return redirect()->route('blogs.index')->with('message', 'Bloq uğurla silindi');

    }

}
