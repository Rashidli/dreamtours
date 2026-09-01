<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-sliders|create-sliders|edit-sliders|delete-sliders', ['only' => ['index','show']]);
        $this->middleware('permission:create-sliders', ['only' => ['create','store']]);
        $this->middleware('permission:edit-sliders', ['only' => ['edit']]);
        $this->middleware('permission:delete-sliders', ['only' => ['destroy']]);
    }

    public function index()
    {
        $sliders = Slider::orderBy('order')->paginate(10);
        return view('admin.sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('admin.sliders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'button_link' => $request->button_link,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'az' => [
                'subtitle' => $request->az_subtitle,
                'title' => $request->az_title,
                'description' => $request->az_description,
                'button_text' => $request->az_button_text,
            ],
            'en' => [
                'subtitle' => $request->en_subtitle,
                'title' => $request->en_title,
                'description' => $request->en_description,
                'button_text' => $request->en_button_text,
            ],
            'ru' => [
                'subtitle' => $request->ru_subtitle,
                'title' => $request->ru_title,
                'description' => $request->ru_description,
                'button_text' => $request->ru_button_text,
            ],
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('sliders', 'public');
        }

        Slider::create($data);

        return redirect()->route('sliders.index')->with('message', 'Slayder uğurla əlavə edildi');
    }

    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'button_link' => $request->button_link,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active'),
            'az' => [
                'subtitle' => $request->az_subtitle,
                'title' => $request->az_title,
                'description' => $request->az_description,
                'button_text' => $request->az_button_text,
            ],
            'en' => [
                'subtitle' => $request->en_subtitle,
                'title' => $request->en_title,
                'description' => $request->en_description,
                'button_text' => $request->en_button_text,
            ],
            'ru' => [
                'subtitle' => $request->ru_subtitle,
                'title' => $request->ru_title,
                'description' => $request->ru_description,
                'button_text' => $request->ru_button_text,
            ],
        ];

        if ($request->hasFile('image')) {
            if ($slider->image) {
                Storage::disk('public')->delete($slider->image);
            }
            $data['image'] = $request->file('image')->store('sliders', 'public');
        }

        $slider->update($data);

        return redirect()->back()->with('message', 'Slayder uğurla yeniləndi');
    }

    public function destroy(Slider $slider)
    {
        if ($slider->image) {
            Storage::disk('public')->delete($slider->image);
        }
        $slider->delete();
        return redirect()->route('sliders.index')->with('message', 'Slayder uğurla silindi');
    }
}
