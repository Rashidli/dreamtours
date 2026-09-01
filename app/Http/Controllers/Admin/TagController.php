<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-tags|create-tags|edit-tags|delete-tags', ['only' => ['index','show']]);
        $this->middleware('permission:create-tags', ['only' => ['create','store']]);
        $this->middleware('permission:edit-tags', ['only' => ['edit']]);
        $this->middleware('permission:delete-tags', ['only' => ['destroy']]);
    }

    public function index()
    {
        $tags = Tag::paginate(10);
        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        Tag::create([
            'az' => [
                'title' => $request->az_title,
                'slug' => Str::slug($request->az_title),
            ],
            'en' => [
                'title' => $request->en_title,
                'slug' => Str::slug($request->en_title),
            ],
            'ru' => [
                'title' => $request->ru_title,
                'slug' => Str::slug($request->ru_title),
            ],
        ]);

        return redirect()->route('tags.index')->with('message', 'Tag uğurla əlavə edildi');
    }

    public function show(Tag $tag)
    {
        //
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        $tag->update([
            'is_active' => $request->boolean('is_active'),
            'az' => [
                'title' => $request->az_title,
                'slug' => Str::slug($request->az_title),
            ],
            'en' => [
                'title' => $request->en_title,
                'slug' => Str::slug($request->en_title),
            ],
            'ru' => [
                'title' => $request->ru_title,
                'slug' => Str::slug($request->ru_title),
            ],
        ]);

        return redirect()->back()->with('message', 'Tag uğurla yeniləndi');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return redirect()->route('tags.index')->with('message', 'Tag uğurla silindi');
    }
}
