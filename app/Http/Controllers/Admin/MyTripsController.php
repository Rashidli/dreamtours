<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepartureStaff;
use App\Models\TourDeparture;
use App\Support\Attendance;
use App\Support\Roster;
use App\Support\RosterExport;
use Symfony\Component\HttpFoundation\Response;
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

    public function show(Request $request, TourDeparture $departure): View|Response
    {
        $mine = $departure->staff()->where('user_id', $request->user()->id)->get();
        abort_unless($mine->isNotEmpty() || $request->user()->can('list-bookings'), 403, 'This trip is not assigned to you.');

        $f = RosterController::filters($request);
        $all = Roster::all($departure);
        $stops = Roster::stops($departure, $all);
        $rows = Roster::filter($all, $f, $stops, $request->user());
        if ($format = $request->query('export')) {
            abort_unless(in_array($format, RosterExport::FORMATS, true), 404);

            // The leader's copy: only what the trip needs (§11), whoever downloads it from here.
            return RosterExport::download($departure, $rows, Roster::columns($request->user(), office: false), $format);
        }
        $departure->loadMissing('tour.itineraryDays.translations');

        return view('admin.my_trips.show', [
            'departure' => $departure, 'mine' => $mine, 'all' => $all, 'rows' => $rows, 'stops' => $stops, 'f' => $f,
            'byStop' => $all->groupBy(fn ($row) => $row['passenger']->pickup_point_id ?? 0),
            'canMark' => Attendance::canMark($request->user(), $departure),
        ]);
    }
}
