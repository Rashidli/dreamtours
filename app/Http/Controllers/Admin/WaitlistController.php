<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourDeparture;
use App\Models\WaitlistEntry;
use App\Support\Waitlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Waitlists of full departures (CRM §8), in queue order, with the seats that are free right now. */
class WaitlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-bookings');
        $this->middleware('permission:edit-bookings')->except('index');
    }

    public function index(Request $request): View
    {
        $f = $request->validate(['departure' => ['nullable', 'integer'], 'show' => ['nullable', Rule::in(['active', 'closed'])]]);
        $closed = ($f['show'] ?? 'active') === 'closed';
        $entries = WaitlistEntry::query()
            ->when($closed, fn ($q) => $q->whereIn('status', ['booked', 'removed'])->latest('updated_at'), fn ($q) => $q->active())
            ->when($f['departure'] ?? null, fn ($q, $id) => $q->where('tour_departure_id', $id))
            ->whereHas('departure', fn ($q) => $closed ? $q : $q->where('departs_at', '>=', now()))
            ->with(['departure.tour.translations', 'booking', 'offeredBy'])->get();

        return view('admin.waitlist.index', [
            'groups' => $entries->groupBy('tour_departure_id')->sortBy(fn ($g) => $g->first()->departure->departs_at),
            'f' => ['departure' => $f['departure'] ?? null, 'show' => $closed ? 'closed' : 'active'],
            'departures' => TourDeparture::with('tour.translations')->where('departs_at', '>=', now())->whereNotIn('status', ['cancelled'])->orderBy('departs_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'tour_departure_id' => ['required', 'integer', 'exists:tour_departures,id'],
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'], 'phone' => ['nullable', 'required_without:email', 'string', 'max:30'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'], 'note' => ['nullable', 'string', 'max:500'],
        ]);
        // Staff may add someone to any upcoming date, full or not (e.g. a date they are about to close).
        $entry = Waitlist::add(TourDeparture::with('tour')->findOrFail($d['tour_departure_id']), $d, $request->user());

        return redirect()->route('waitlist.index', ['departure' => $entry->tour_departure_id])->with('message', "{$entry->full_name} added to the waitlist (#{$entry->position}).");
    }

    public function offer(Request $request, WaitlistEntry $entry): RedirectResponse
    {
        $emailed = Waitlist::offer($entry, $request->user(), $request->boolean('email'));

        return back()->with('message', $entry->full_name.' marked as offered'.($emailed ? ' and emailed.' : '. Call them, then press Book.'));
    }

    public function remove(Request $request, WaitlistEntry $entry): RedirectResponse
    {
        $d = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        abort_unless(in_array($entry->status, ['waiting', 'offered'], true), 422);
        Waitlist::remove($entry, $d['reason']);

        return back()->with('message', "{$entry->full_name} removed from the waitlist.");
    }

    public function move(Request $request, WaitlistEntry $entry): RedirectResponse
    {
        Waitlist::move($entry, $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction']);

        return back();
    }
}
