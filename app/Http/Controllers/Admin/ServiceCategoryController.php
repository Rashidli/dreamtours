<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-service_categories|create-service_categories|edit-service_categories|delete-service_categories', ['only' => ['index','show']]);
        $this->middleware('permission:create-service_categories', ['only' => ['create','store']]);
        $this->middleware('permission:edit-service_categories', ['only' => ['edit']]);
        $this->middleware('permission:delete-service_categories', ['only' => ['destroy']]);
    }

    public function index()
    {
        $serviceCategories = ServiceCategory::query()->paginate(10);
        return view('admin.service_categories.index', compact('serviceCategories'));
    }

    public function create()
    {
        return view('admin.service_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required|string|max:255',
            'en_title' => 'required|string|max:255',
            'ru_title' => 'required|string|max:255',
        ]);

        ServiceCategory::create([
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

        return redirect()->route('service_categories.index')->with('message', 'Xidmət kateqoriyası uğurla əlavə edildi');
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        return view('admin.service_categories.edit', compact('serviceCategory'));
    }

    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $request->validate([
            'az_title' => 'required|string|max:255',
            'en_title' => 'required|string|max:255',
            'ru_title' => 'required|string|max:255',
        ]);

        $serviceCategory->update([
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

        return redirect()->back()->with('message', 'Xidmət kateqoriyası uğurla yeniləndi');
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        return redirect()->route('service_categories.index')->with('message', 'Xidmət kateqoriyası uğurla silindi');
    }
}
