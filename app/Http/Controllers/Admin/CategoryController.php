<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploadService)
    {
        $this->middleware('permission:list-categories|create-categories|edit-categories|delete-categories', ['only' => ['index','show']]);
        $this->middleware('permission:create-categories', ['only' => ['create','store']]);
        $this->middleware('permission:edit-categories', ['only' => ['edit']]);
        $this->middleware('permission:delete-categories', ['only' => ['destroy']]);
    }

    public function index()
    {
        $categories = Category::query()->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {

        return view('admin.categories.create');
    }

    public function store(Request $request)
    {

        $request->validate([
            'en_title' => 'required|string|max:255',
            'ru_title' => 'required|string|max:255',
            'az_title' => 'required|string|max:255',
        ]);


        Category::create([
            'az'=>[
                'title'=>$request->az_title,
            ],
            'en'=>[
                'title'=>$request->en_title,
            ],
            'ru'=>[
                'title'=>$request->ru_title,
            ]
        ]);

        return redirect()->route('categories.index')->with('message','Kateqoriya uğurla əlavə edildi');
    }

    public function edit(Category $category)
    {

        return view('admin.categories.edit', compact('category' ));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'en_title' => 'required|string|max:255',
            'ru_title' => 'required|string|max:255',
            'az_title' => 'required|string|max:255',
        ]);


        $category->update( [

            'az'=>[
                'title'=>$request->az_title,
            ],
            'en'=>[
                'title'=>$request->en_title,
            ],
            'ru'=>[
                'title'=>$request->ru_title,
            ]

        ]);

        return redirect()->back()
            ->with('message', 'Kateqoriya uğurla yeniləndi');
    }


    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kateqoriya uğurla silindi.');
    }
}
