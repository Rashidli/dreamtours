<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPassenger;
use App\Models\DepartureVehicle;
use App\Models\TourDeparture;
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

    public function index(Request $request): View
    {
        return view('admin.vehicles.index', [
            'layouts' => VehicleLayout::with('transportation.translations')->orderByDesc('is_active')->orderBy('name')->get(),
            'departures' => $request->user()->can('list-bookings') ? $this->upcoming($request) : null,
        ]);
    }

    /**
     * Upcoming dates with their seating progress, those needing attention first: passengers but no
     * bus, then seats still to assign, then drafts, then published. Counts are computed in SQL.
     */
    private function upcoming(Request $request)
    {
        $passengers = fn (bool $seated) => BookingPassenger::selectRaw('count(*)')
            ->join('bookings', 'bookings.id', '=', 'booking_passengers.booking_id')
            ->whereColumn('bookings.tour_departure_id', 'tour_departures.id')->where('bookings.status', '!=', 'cancelled')
            ->when($seated, fn ($q) => $q->whereNotNull('booking_passengers.departure_vehicle_id')->whereNotNull('booking_passengers.seat_number'));
        $all = $request->boolean('all');

        $departures = TourDeparture::query()->select('tour_departures.*')
            ->selectSub($passengers(false), 'passengers_count')
            ->selectSub($passengers(true), 'seated_count')
            ->selectSub(DepartureVehicle::selectRaw('count(*)')->whereColumn('tour_departure_id', 'tour_departures.id'), 'vehicles_count')
            ->selectSub(DepartureVehicle::selectRaw('count(*)')->whereColumn('tour_departure_id', 'tour_departures.id')->where(fn ($q) => $q->whereNull('published_at')->orWhereNotNull('seats_changed_at')), 'draft_count')
            ->where('departs_at', '>=', now()->startOfDay())->where('status', '!=', 'cancelled')
            ->with('tour.translations')
            ->when(!$all, fn ($q) => $q->having('passengers_count', '>', 0))
            ->get();

        return $departures->sortBy(fn ($d) => [self::stage($d), $d->departs_at->timestamp])->values();
    }

    /** 0 passengers but no bus · 1 seats to assign · 2 draft · 3 published · 4 nobody booked yet */
    public static function stage(TourDeparture $d): int
    {
        return match (true) {
            !$d->passengers_count => 4,
            !$d->vehicles_count => 0,
            $d->seated_count < $d->passengers_count => 1,
            $d->draft_count > 0 => 2,
            default => 3,
        };
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
