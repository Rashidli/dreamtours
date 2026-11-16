<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Activity;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\BookingMail;
use App\Support\Leads;
use App\Support\SeatNotices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bookings and enquiries. Seat counts follow the bookings automatically (see Booking::booted):
 * a cancelled booking frees its seats, a passenger added or removed changes them.
 */
class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-bookings')->only(['index', 'show', 'paymentProof']);
        $this->middleware('permission:edit-bookings')->only(['update', 'updateDetails', 'updateSeats', 'sendSeatNotice']);
        $this->middleware('permission:delete-bookings')->only('destroy');
    }

    public function index(Request $r): View|\Symfony\Component\HttpFoundation\Response
    {
        $query = $this->filtered($r);
        if ($format = $r->query('export')) {
            abort_unless($r->user()->can('export-bookings'), 403);
            abort_unless(in_array($format, \App\Support\TableExport::FORMATS, true), 404);

            return $this->export($r, $query, $format);
        }

        return view('admin.bookings.index', [
            'bookings' => $query->latest()->paginate(25)->withQueryString(),
            'tours' => Tour::withTranslation()->orderBy('sort_order')->get(),
            'departures' => $r->filled('tour_id') ? TourDeparture::where('tour_id', $r->integer('tour_id'))->orderBy('departs_at')->get() : collect(),
            'pickups' => PickupPoint::withTranslation()->orderBy('sort_order')->get(),
        ]);
    }

    /** The list as CSV or Excel (CRM §25). Amounts are included only with view-financial-info. */
    private function export(Request $r, \Illuminate\Database\Eloquent\Builder $query, string $format): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $tz = ActivityLogController::DISPLAY_TIMEZONE;
        $bookings = $query->withCount('passengers')->latest()->get();
        $columns = [
            'Reference' => fn ($b) => $b->reference,
            'Booked on' => fn ($b) => $b->created_at?->timezone($tz)->format('Y-m-d H:i'),
            'Type' => fn ($b) => str($b->type)->replace('_', ' ')->title(),
            'Status' => fn ($b) => Booking::STATUSES[$b->status] ?? $b->status,
            'First name' => fn ($b) => $b->first_name,
            'Last name' => fn ($b) => $b->last_name,
            'Email' => fn ($b) => $b->email,
            'Phone' => fn ($b) => $b->phone,
            'Tour' => fn ($b) => $b->tour?->name,
            'Trip date' => fn ($b) => $b->departure?->departs_at?->format('Y-m-d'),
            'Guests' => fn ($b) => $b->participant_count,
        ];
        if ($r->user()->can('view-financial-info')) {
            $amount = fn ($v) => $v === null ? '' : number_format((float) $v, 2, '.', '');
            $columns += [
                'Total (CAD)' => fn ($b) => $b->tour_departure_id ? $amount($b->total_amount) : '',
                'Discounts (CAD)' => fn ($b) => $b->tour_departure_id ? $amount($b->discount_amount) : '',
                'Paid, net (CAD)' => fn ($b) => $b->tour_departure_id ? $amount($b->amount_paid) : '',
                'Outstanding (CAD)' => fn ($b) => $b->tour_departure_id ? $amount($b->outstanding_amount) : '',
                'Payment status' => fn ($b) => Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status,
                'Balance due' => fn ($b) => $b->balance_due_date?->format('Y-m-d'),
            ];
        }
        Activity::record('bookings_exported', null, ['rows' => [null, (string) $bookings->count()], 'format' => [null, strtoupper($format)], 'filters' => [null, http_build_query($r->except(['export', 'page'])) ?: 'none']], label: 'Booking list');

        return \App\Support\TableExport::download('dreamtour-bookings-'.now($tz)->format('Y-m-d'), $bookings, $columns, $format);
    }

    private function filtered(Request $r): \Illuminate\Database\Eloquent\Builder
    {
        return Booking::with(['tour.translations', 'departure'])
            ->when($r->filled('q'), fn ($q) => $q->where(fn ($x) => $x->where('reference', 'like', '%'.$r->q.'%')
                ->orWhere('first_name', 'like', '%'.$r->q.'%')->orWhere('last_name', 'like', '%'.$r->q.'%')
                ->orWhere('email', 'like', '%'.$r->q.'%')->orWhere('phone', 'like', '%'.$r->q.'%')->orWhere('whatsapp', 'like', '%'.$r->q.'%')
                ->orWhereHas('passengers', fn ($p) => $p->where('first_name', 'like', '%'.$r->q.'%')->orWhere('last_name', 'like', '%'.$r->q.'%'))))
            ->when($r->filled('type'), fn ($q) => $q->where('type', $r->type))
            ->when($r->filled('tour_id'), fn ($q) => $q->where('tour_id', $r->integer('tour_id')))
            ->when($r->filled('tour_departure_id'), fn ($q) => $q->where('tour_departure_id', $r->integer('tour_departure_id')))
            ->when($r->filled('pickup_point_id'), fn ($q) => $q->whereHas('passengers', fn ($p) => $p->where('pickup_point_id', $r->integer('pickup_point_id'))))
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))
            ->when($r->filled('payment_status'), fn ($q) => $q->where('payment_status', $r->payment_status))
            // Booked between two dates, in the office's time zone (the dashboard's "bookings today" opens this).
            ->when($r->date('booked_from'), fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d->toDateString(), ActivityLogController::DISPLAY_TIMEZONE)->startOfDay()->utc()))
            ->when($r->date('booked_to'), fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d->toDateString(), ActivityLogController::DISPLAY_TIMEZONE)->endOfDay()->utc()));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['tour.translations', 'departure.pickupPoints.translations', 'departure.tour.pickupPoints.translations', 'pickupPoint.translations', 'passengers.pickupPoint.translations', 'passengers.departureVehicle', 'updatedBy', 'legalAcceptances', 'payments.refundOf', 'payments.recorder', 'payments.confirmer', 'adjustments.passenger', 'adjustments.creator']);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'seatBlocker' => SeatNotices::blocker($booking),
            'pickupOptions' => $booking->departure?->availablePickupPoints() ?? PickupPoint::where('is_active', true)->withTranslation()->orderBy('sort_order')->get(),
            'mealOptions' => $booking->tour?->mealChoices() ?? [],
            'moneySummary' => \App\Support\BookingMoney::summary($booking),
            // This booking's own history, including its passengers (their labels end with the booking reference).
            'activity' => \App\Models\ActivityLog::where(fn ($q) => $q->where(['subject_type' => Booking::class, 'subject_id' => $booking->id])
                ->orWhere(fn ($x) => $x->where('subject_type', \App\Models\BookingPassenger::class)->where('subject_label', 'like', '% · '.$booking->reference)))
                ->latest('id')->limit(100)->get(),
        ]);
    }

    /** Status, payment and internal notes. */
    public function update(Request $r, Booking $booking): RedirectResponse
    {
        $d = $r->validate([
            'status' => ['required', Rule::in(array_keys(Booking::STATUSES))],
            // Paid amounts and payment status come from the payment ledger, never from this form (§7).
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($booking, $d) {
            // Re-activating a cancelled booking needs its seats back.
            if ($booking->status === 'cancelled' && $d['status'] !== 'cancelled') {
                $this->ensureSeats($booking, $booking->seat_count);
            }
            $cancelling = $booking->status !== 'cancelled' && $d['status'] === 'cancelled';
            $booking->update($d + ['updated_by' => auth()->id()]);
            if ($cancelling) {
                \App\Support\Seating::release($booking); // its seats go back to the board
            }
        });
        // Setting a booking to Confirmed emails the customer (once per booking).
        BookingMail::confirmed($booking);
        Leads::bookingConfirmed($booking);

        return back()->with('message', 'Booking updated.');
    }

    /** Contact details and the passenger list; adding or removing passengers changes the seat count. */
    public function updateDetails(Request $r, Booking $booking): RedirectResponse
    {
        $pickupIds = ($booking->departure?->availablePickupPoints() ?? PickupPoint::all())->pluck('id')->all();
        $d = $r->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'passengers' => ['array'],
            'passengers.*.first_name' => ['required', 'string', 'max:100'],
            'passengers.*.last_name' => ['required', 'string', 'max:100'],
            'passengers.*.pickup_point_id' => ['nullable', 'integer', Rule::in($pickupIds)],
            'passengers.*.meal_selection' => ['nullable', 'string', 'max:100'],
            'passengers.*.remove' => ['nullable', 'boolean'],
            'new_passengers' => ['array'],
            'new_passengers.*.first_name' => ['nullable', 'string', 'max:100'],
            'new_passengers.*.last_name' => ['nullable', 'string', 'max:100', 'required_with:new_passengers.*.first_name'],
            'new_passengers.*.pickup_point_id' => ['nullable', 'integer', Rule::in($pickupIds)],
            'new_passengers.*.meal_selection' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($booking, $d) {
            $existing = $booking->passengers()->get()->keyBy('id');
            $removeIds = collect($d['passengers'] ?? [])->filter(fn ($p) => !empty($p['remove']))->keys()->map(fn ($id) => (int) $id);
            $added = collect($d['new_passengers'] ?? [])->filter(fn ($p) => filled($p['first_name'] ?? null));
            $newCount = $existing->count() - $removeIds->intersect($existing->keys())->count() + $added->count();
            // A lap infant is a passenger without a seat; staff-added passengers are adults.
            $kept = $existing->reject(fn ($p) => $removeIds->contains($p->id));
            $lapInfants = $booking->tour?->infant_needs_seat ? 0 : $kept->where('category', 'infant')->count();
            $newSeats = $newCount - $lapInfants;
            // Enquiries without a passenger list keep the head count they were sent with.
            if ($existing->isEmpty() && $added->isEmpty()) {
                $newCount = $booking->participant_count;
                $newSeats = $booking->seat_count;
            }
            if ($newCount < 1) {
                throw ValidationException::withMessages(['passengers' => 'A booking needs at least one passenger. Cancel the booking instead.']);
            }
            if ($booking->status !== 'cancelled' && $newSeats > $booking->seat_count) {
                $this->ensureSeats($booking, $newSeats - $booking->seat_count);
            }

            foreach ($d['passengers'] ?? [] as $id => $p) {
                if ($passenger = $existing->get((int) $id)) {
                    $removeIds->contains((int) $id) ? $passenger->delete() : $passenger->update(collect($p)->only(['first_name', 'last_name', 'pickup_point_id', 'meal_selection'])->all());
                }
            }
            foreach ($added as $p) {
                $booking->passengers()->create(collect($p)->only(['first_name', 'last_name', 'pickup_point_id', 'meal_selection'])->all() + ['category' => 'adult']);
            }

            $lead = $booking->passengers()->orderBy('id')->first();
            $booking->update(collect($d)->only(['first_name', 'last_name', 'email', 'phone', 'whatsapp', 'emergency_contact_name', 'emergency_contact_phone'])->all() + [
                'participant_count' => $newCount,
                'seat_count' => $newSeats,
                'pickup_point_id' => $lead?->pickup_point_id ?? $booking->pickup_point_id,
                'updated_by' => auth()->id(),
            ]);
        });

        return back()->with('message', 'Booking details saved.');
    }

    public function updateSeats(Request $r, Booking $booking): RedirectResponse
    {
        // Dates with buses are seated on the seating board, which keeps every seat unique.
        if ($booking->departure?->vehicles()->exists()) {
            return redirect()->route('seating.show', $booking->tour_departure_id)->withErrors(['seats' => 'This date has buses — choose seats on the seating board.']);
        }
        $d = $r->validate(['seats' => ['array'], 'seats.*' => ['nullable', 'string', 'max:10']]);
        $wanted = collect($d['seats'] ?? [])->map(fn ($s) => trim((string) $s))->filter();
        if ($wanted->count() !== $wanted->map(fn ($s) => mb_strtoupper($s))->unique()->count()) {
            throw ValidationException::withMessages(['seats' => 'Two passengers were given the same seat.']);
        }
        if ($booking->tour_departure_id && $wanted->isNotEmpty()) {
            $taken = \App\Models\BookingPassenger::whereHas('booking', fn ($q) => $q->where('tour_departure_id', $booking->tour_departure_id)->where('status', '!=', 'cancelled')->whereKeyNot($booking->id))
                ->whereNull('departure_vehicle_id')->whereIn('seat_number', $wanted->values())->with('booking')->first();
            if ($taken) {
                throw ValidationException::withMessages(['seats' => "Seat {$taken->seat_number} is already taken by ".trim("{$taken->first_name} {$taken->last_name}")." ({$taken->booking->reference})."]);
            }
        }
        foreach ($booking->passengers as $passenger) {
            if (array_key_exists($passenger->id, $d['seats'] ?? [])) {
                $passenger->update(['seat_number' => $wanted[$passenger->id] ?? null]);
            }
        }

        return back()->with('message', 'Seat numbers saved.');
    }

    public function sendSeatNotice(Booking $booking): RedirectResponse
    {
        if ($reason = SeatNotices::blocker($booking)) {
            return back()->withErrors(['seats' => $reason]);
        }
        SeatNotices::send($booking);

        return back()->with('message', 'Seat numbers emailed to '.$booking->email.'.');
    }

    public function paymentProof(Booking $booking): StreamedResponse
    {
        abort_unless($booking->payment_proof && Storage::disk('local')->exists($booking->payment_proof), 404);

        return Storage::disk('local')->response($booking->payment_proof);
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('bookings.index')->with('message', 'Booking deleted.');
    }

    /** Refuse a change that would put more people on the date than it has seats. */
    /** Extra seats for this booking, within capacity unless an authorised override with a reason (AT-05). */
    private function ensureSeats(Booking $booking, int $extra): void
    {
        if ($booking->departure) {
            \App\Support\Capacity::claim($booking->departure, $extra, auth()->user(), request('override_reason'), 'Booking '.$booking->reference);
        }
    }
}
