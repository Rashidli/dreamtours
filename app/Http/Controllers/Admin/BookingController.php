<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\SeatNotices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function index(Request $r): View
    {
        $bookings = Booking::with(['tour.translations', 'departure'])
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
            ->latest()->paginate(25)->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'tours' => Tour::withTranslation()->orderBy('sort_order')->get(),
            'departures' => $r->filled('tour_id') ? TourDeparture::where('tour_id', $r->integer('tour_id'))->orderBy('departs_at')->get() : collect(),
            'pickups' => PickupPoint::withTranslation()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['tour.translations', 'departure.pickupPoints.translations', 'departure.tour.pickupPoints.translations', 'pickupPoint.translations', 'passengers.pickupPoint.translations', 'updatedBy']);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'seatBlocker' => SeatNotices::blocker($booking),
            'pickupOptions' => $booking->departure?->availablePickupPoints() ?? PickupPoint::where('is_active', true)->withTranslation()->orderBy('sort_order')->get(),
            'mealOptions' => $booking->tour?->mealChoices() ?? [],
        ]);
    }

    /** Status, payment and internal notes. */
    public function update(Request $r, Booking $booking): RedirectResponse
    {
        $d = $r->validate([
            'status' => ['required', Rule::in(array_keys(Booking::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(Booking::PAYMENT_STATUSES))],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $d['amount_paid'] = $d['amount_paid'] ?? 0;

        DB::transaction(function () use ($booking, $d) {
            // Re-activating a cancelled booking needs its seats back.
            if ($booking->status === 'cancelled' && $d['status'] !== 'cancelled') {
                $this->ensureSeats($booking, $booking->participant_count);
            }
            $booking->update($d + ['updated_by' => auth()->id()]);
        });

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
            // Enquiries without a passenger list keep the head count they were sent with.
            if ($existing->isEmpty() && $added->isEmpty()) {
                $newCount = $booking->participant_count;
            }
            if ($newCount < 1) {
                throw ValidationException::withMessages(['passengers' => 'A booking needs at least one passenger. Cancel the booking instead.']);
            }
            if ($booking->status !== 'cancelled' && $newCount > $booking->participant_count) {
                $this->ensureSeats($booking, $newCount - $booking->participant_count);
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
                'pickup_point_id' => $lead?->pickup_point_id ?? $booking->pickup_point_id,
                'updated_by' => auth()->id(),
            ]);
        });

        return back()->with('message', 'Booking details saved.');
    }

    public function updateSeats(Request $r, Booking $booking): RedirectResponse
    {
        $d = $r->validate(['seats' => ['array'], 'seats.*' => ['nullable', 'string', 'max:10']]);
        foreach ($booking->passengers as $passenger) {
            if (array_key_exists($passenger->id, $d['seats'] ?? [])) {
                $passenger->update(['seat_number' => $d['seats'][$passenger->id] ?: null]);
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
    private function ensureSeats(Booking $booking, int $extra): void
    {
        $departure = $booking->tour_departure_id ? TourDeparture::lockForUpdate()->find($booking->tour_departure_id) : null;
        if ($departure && $departure->available_seats < $extra) {
            throw ValidationException::withMessages(['status' => "Only {$departure->available_seats} seats are left on this date; {$extra} needed."]);
        }
    }
}
