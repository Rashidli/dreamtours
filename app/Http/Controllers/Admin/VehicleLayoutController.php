<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transportation;
use App\Models\VehicleLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Reusable bus and van seating plans, drawn on a grid (CRM §9). */
class VehicleLayoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-vehicle-layouts')->only('index');
        $this->middleware('permission:create-vehicle-layouts')->only(['create', 'store']);
        $this->middleware('permission:edit-vehicle-layouts')->only(['edit', 'update']);
        $this->middleware('permission:delete-vehicle-layouts')->only('destroy');
    }

    public function index(): View
    {
        return view('admin.vehicles.index', ['layouts' => VehicleLayout::with('transportation.translations')->orderByDesc('is_active')->orderBy('name')->get()]);
    }

    public function create(Request $request): View
    {
        $copy = $request->filled('copy') ? VehicleLayout::find($request->integer('copy')) : null;
        $layout = new VehicleLayout(['name' => $copy ? $copy->name.' (copy)' : '', 'transportation_id' => $copy?->transportation_id, 'is_active' => true, 'layout' => $copy?->layout ?? VehicleLayout::generate(10, 2, 2)]);

        return view('admin.vehicles.form', ['layout' => $layout, 'transportations' => Transportation::with('translations')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $layout = VehicleLayout::create($this->data($request));

        return redirect()->route('vehicle-layouts.index')->with('message', "“{$layout->name}” saved with {$layout->seat_count} seats.");
    }

    public function edit(VehicleLayout $vehicleLayout): View
    {
        return view('admin.vehicles.form', ['layout' => $vehicleLayout, 'transportations' => Transportation::with('translations')->get()]);
    }

    public function update(Request $request, VehicleLayout $vehicleLayout): RedirectResponse
    {
        $vehicleLayout->update($this->data($request));

        return redirect()->route('vehicle-layouts.index')->with('message', "“{$vehicleLayout->name}” saved with {$vehicleLayout->seat_count} seats. Buses already added to a date keep the plan they were given.");
    }

    /** Archived, not erased: dates that use a copy of it are unaffected. */
    public function destroy(VehicleLayout $vehicleLayout): RedirectResponse
    {
        $vehicleLayout->delete();

        return redirect()->route('vehicle-layouts.index')->with('message', "“{$vehicleLayout->name}” removed from the list.");
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'transportation_id' => ['nullable', 'exists:transportations,id'],
            'is_active' => ['nullable', 'boolean'],
            'layout' => ['required', 'json'],
        ]);
        $layout = VehicleLayout::clean(json_decode($d['layout'], true));

        return ['name' => $d['name'], 'transportation_id' => $d['transportation_id'] ?? null, 'is_active' => $request->boolean('is_active'), 'layout' => $layout, 'seat_count' => VehicleLayout::countSeats($layout)];
    }
}
