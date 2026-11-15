<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepartureStaff;
use App\Models\TourDeparture;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A staff member's own trips (CRM §12, AT-07): only departures they are assigned to right now.
 * Office staff who can see bookings may open any trip page too, to see what the leader sees.
 */
class MyTripsController extends Controller
{
    public function index(Request $request): View
    {
        $past = $request->query('show') === 'past';
        $assignments = DepartureStaff::where('user_id', $request->user()->id)
            ->whereHas('departure', fn ($q) => $past ? $q->where('departs_at', '<', now()->startOfDay()) : $q->where('departs_at', '>=', now()->startOfDay()))
            ->with(['departure.tour.translations', 'departure.staff.user'])
            ->get()
            ->sortBy(fn ($a) => $a->departure->departs_at->timestamp * ($past ? -1 : 1))
            ->groupBy('tour_departure_id');

        return view('admin.my_trips.index', ['trips' => $assignments, 'past' => $past]);
    }

    public function show(Request $request, TourDeparture $departure): View
    {
        $mine = $departure->staff()->where('user_id', $request->user()->id)->get();
        abort_unless($mine->isNotEmpty() || $request->user()->can('list-bookings'), 403, 'This trip is not assigned to you.');

        $departure->load(['tour.translations', 'tour.itineraryDays.translations', 'staff.user', 'pickupPoints.translations',
            'bookings' => fn ($q) => $q->where('status', '!=', 'cancelled')->orderBy('created_at'),
            'bookings.passengers.pickupPoint.translations']);
        $passengers = $departure->bookings->flatMap(fn ($booking) => $booking->passengers->map(fn ($p) => ['passenger' => $p, 'booking' => $booking]))->values();
        // Stops in the departure's order (or the tour's), then any stop a passenger chose that is no longer listed.
        $stops = ($departure->pickupPoints->isNotEmpty() ? $departure->pickupPoints : $departure->tour->pickupPoints()->with('translations')->get())
            ->concat($passengers->pluck('passenger.pickupPoint')->filter())->unique('id')->values();

        return view('admin.my_trips.show', [
            'departure' => $departure,
            'mine' => $mine,
            'passengers' => $passengers,
            'stops' => $stops,
            'byStop' => $passengers->groupBy(fn ($row) => $row['passenger']->pickup_point_id ?? 0),
        ]);
    }
}
