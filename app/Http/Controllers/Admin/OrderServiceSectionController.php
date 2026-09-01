<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderServiceSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderServiceSectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-order_service|edit-order_service', ['only' => ['index','edit']]);
        $this->middleware('permission:create-order_service', ['only' => ['create','store']]);
        $this->middleware('permission:edit-order_service', ['only' => ['update']]);
    }

    public function index()
    {
        $section = OrderServiceSection::first();
        if ($section) {
            return redirect()->route('order-service-section.edit', $section->id);
        }
        return redirect()->route('order-service-section.create');
    }

    public function create()
    {
        return view('admin.order_service_section.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
        ]);

        $data = [
            'az' => [
                'title' => $request->az_title,
                'description' => $request->az_description,
                'button_text' => $request->az_button_text,
            ],
            'en' => [
                'title' => $request->en_title,
                'description' => $request->en_description,
                'button_text' => $request->en_button_text,
            ],
            'ru' => [
                'title' => $request->ru_title,
                'description' => $request->ru_description,
                'button_text' => $request->ru_button_text,
            ],
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('order_service', 'public');
        }

        OrderServiceSection::create($data);

        return redirect()->route('order-service-section.index')->with('message', 'Məlumat uğurla əlavə edildi');
    }

    public function edit(OrderServiceSection $order_service_section)
    {
        return view('admin.order_service_section.edit', ['section' => $order_service_section]);
    }

    public function update(Request $request, OrderServiceSection $order_service_section)
    {
        $request->validate([
            'az_title' => 'required',
        ]);

        $data = [
            'az' => [
                'title' => $request->az_title,
                'description' => $request->az_description,
                'button_text' => $request->az_button_text,
            ],
            'en' => [
                'title' => $request->en_title,
                'description' => $request->en_description,
                'button_text' => $request->en_button_text,
            ],
            'ru' => [
                'title' => $request->ru_title,
                'description' => $request->ru_description,
                'button_text' => $request->ru_button_text,
            ],
        ];

        if ($request->hasFile('image')) {
            if ($order_service_section->image) {
                Storage::disk('public')->delete($order_service_section->image);
            }
            $data['image'] = $request->file('image')->store('order_service', 'public');
        }

        $order_service_section->update($data);

        return redirect()->back()->with('message', 'Məlumat uğurla yeniləndi');
    }
}
