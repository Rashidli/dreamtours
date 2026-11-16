<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\TourDeparture;
use App\Support\Attendance;
use App\Support\Roster;
use App\Support\RosterExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** The office passenger list of one departure, its exports, and check-in marks from any trip page. */
class RosterController extends Controller
{
    public function index(Request $request, TourDeparture $departure): Response|\Illuminate\View\View
    {
        abort_unless($request->user()->can('list-bookings'), 403);
        $f = $this->filters($request);
        $all = Roster::all($departure);
        $stops = Roster::stops($departure, $all);
        $rows = Roster::filter($all, $f, $stops, $request->user());
        if ($format = $request->query('export')) {
            abort_unless(in_array($format, RosterExport::FORMATS, true), 404);

            return RosterExport::download($departure, $rows, Roster::columns($request->user(), office: true), $format);
        }

        return view('admin.tours.passengers', [
            'departure' => $departure, 'all' => $all, 'rows' => $rows, 'stops' => $stops, 'f' => $f,
            'meals' => $all->pluck('passenger.meal_selection')->filter()->unique()->sort()->values(),
            'cancelled' => $departure->bookings->where('status', 'cancelled'),
            'canMark' => Attendance::canMark($request->user(), $departure),
        ]);
    }

    public function attendance(Request $request, TourDeparture $departure, BookingPassenger $passenger): RedirectResponse
    {
        $passenger->loadMissing('booking');
        abort_unless($passenger->booking?->tour_departure_id === $departure->id && $passenger->booking->status !== 'cancelled', 404);
        abort_unless(Attendance::canMark($request->user(), $departure), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_merge(array_keys(Attendance::STATUSES), ['expected']))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $status = $data['status'] === 'expected' ? null : $data['status'];
        Attendance::mark($passenger, $status, $request->user(), filled($data['note'] ?? null) ? $data['note'] : null);
        $name = trim("{$passenger->first_name} {$passenger->last_name}");

        return redirect()->to(url()->previous().'#p-'.$passenger->id)->with('message', "$name: ".Attendance::label($status).'.');
    }

    /** @return array<string, mixed> */
    public static function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'pickup' => ['nullable', 'integer'],
            'vehicle' => ['nullable', 'integer'],
            'attendance' => ['nullable', Rule::in(array_keys(Roster::ATTENDANCE_FILTERS))],
            'meal' => ['nullable', 'string', 'max:100'],
            'booking_status' => ['nullable', Rule::in(array_keys(Booking::STATUSES))],
            'sort' => ['nullable', Rule::in(array_keys(Roster::SORTS))],
        ]);
    }
}
