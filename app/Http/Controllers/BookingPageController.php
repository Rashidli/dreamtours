<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesFrontLayout;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\BookingQuote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * The step-by-step tour booking: passengers and rooms → names → pickup → contact →
 * emergency contact → extras → deposit and payment proof → policies.
 */
class BookingPageController extends Controller
{
    use SharesFrontLayout;

    public function create(string $slug): View|RedirectResponse
    {
        $tour = $this->tour($slug);

        if ($tour->is_private || $tour->departures->isEmpty()) {
            return redirect()->route('front.tours.show', $slug)->with('message', 'This tour has no open dates yet — send us a request and we will get back to you.');
        }

        return view('front.pages.tour-booking', [
            'tour' => $tour,
            'pickupPoints' => $this->pickupPoints($tour),
            'departuresPayload' => $tour->departures->map(fn (TourDeparture $d) => [
                'id' => $d->id,
                'label' => $d->departs_at->format('D, M j, Y'),
                'time' => $d->departs_at->format('g:i A'),
                'currency' => $d->currency,
                'seats' => $d->available_seats,
                'prices' => $d->prices->map(fn ($p) => ['code' => $p->code, 'label' => $p->label, 'price' => (float) $p->price, 'occupancy' => $p->occupancy])->values(),
                'basePrice' => (float) $d->price,
            ])->values(),
        ] + $this->layoutData());
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tour = $this->tour($slug);
        $pickupIds = $this->pickupPoints($tour)->pluck('id')->all();

        $data = $request->validate([
            'tour_departure_id' => ['required', 'integer'],
            'selections' => ['required', 'array'],
            'selections.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'passengers' => ['required', 'array', 'min:1', 'max:100'],
            'passengers.*.first_name' => ['required', 'string', 'max:100'],
            'passengers.*.last_name' => ['required', 'string', 'max:100'],
            'passengers.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'passengers.0.date_of_birth' => ['required', 'date', 'before:today'],
            'passengers.*.is_child' => ['nullable', 'boolean'],
            'roommate_request' => ['nullable', 'string', 'max:1000'],
            'pickup_point_id' => ['required', 'integer', 'in:'.implode(',', $pickupIds ?: [0])],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'meal_selection' => ['nullable', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'food_allergies' => ['nullable', 'string', 'max:1000'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:8192'],
            'accept_policies' => ['accepted'],
        ], [
            'passengers.0.date_of_birth.required' => 'Please enter the date of birth of the main passenger.',
            'pickup_point_id.in' => 'Please choose one of the pickup points for this tour.',
            'payment_proof.required' => 'Please upload a screenshot of your deposit payment.',
            'accept_policies.accepted' => 'Please confirm that you agree to the deposit, cancellation and refund policies.',
        ]);

        $phone = canadian_phone($data['phone']);
        $emergencyPhone = canadian_phone($data['emergency_contact_phone']) ?? $data['emergency_contact_phone'];
        if (!$phone) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid Canadian phone number, e.g. 416-555-0100.']);
        }

        $booking = DB::transaction(function () use ($request, $tour, $data, $phone, $emergencyPhone) {
            $departure = $tour->departures()->upcoming()->whereIn('status', ['available', 'limited'])
                ->with('prices')->lockForUpdate()->find($data['tour_departure_id']);
            if (!$departure) {
                throw ValidationException::withMessages(['tour_departure_id' => 'This departure is no longer available. Please choose another date.']);
            }

            $quote = BookingQuote::make($tour, $departure, $data['selections'], $data['passengers']);
            if (count($data['passengers']) !== $quote->passengerCount()) {
                throw ValidationException::withMessages(['passengers' => "Please enter the names of all {$quote->passengerCount()} passengers."]);
            }
            if ($departure->available_seats < $quote->passengerCount()) {
                throw ValidationException::withMessages(['selections' => "Only {$departure->available_seats} seats are left on this departure."]);
            }

            $lead = $data['passengers'][0];
            $booking = Booking::create([
                'tour_id' => $tour->id,
                'tour_departure_id' => $departure->id,
                'pickup_point_id' => $data['pickup_point_id'],
                'type' => $tour->is_cruise ? 'cruise' : 'tour',
                'status' => 'new',
                'first_name' => $lead['first_name'],
                'last_name' => $lead['last_name'],
                'date_of_birth' => $lead['date_of_birth'],
                'phone' => $phone,
                'email' => $data['email'],
                'emergency_contact_name' => $data['emergency_contact_name'],
                'emergency_contact_phone' => $emergencyPhone,
                'participant_count' => $quote->passengerCount(),
                'roommate_request' => $data['roommate_request'] ?? null,
                'meal_selection' => $data['meal_selection'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
                'food_allergies' => $data['food_allergies'] ?? null,
                'promo_code' => $data['promo_code'] ?? null,
                'currency' => $departure->currency,
                'total_amount' => $quote->total,
                'deposit_amount' => $quote->deposit,
                'payment_status' => 'proof_submitted',
                'payment_proof' => $request->file('payment_proof')->store('payment-proofs', 'local'),
                'policies_accepted_at' => now(),
            ]);

            foreach ($quote->slots as $i => $slot) {
                $passenger = $data['passengers'][$i];
                $booking->passengers()->create([
                    'first_name' => $passenger['first_name'],
                    'last_name' => $passenger['last_name'],
                    'date_of_birth' => $passenger['date_of_birth'] ?? null,
                    'category' => $slot['code'] === 'child' ? 'child' : ($slot['code'] === 'senior' ? 'senior' : 'adult'),
                    'price_code' => $slot['code'],
                    'price' => $slot['price'],
                    'room_number' => $slot['room'],
                    'pickup_point_id' => $data['pickup_point_id'],
                ]);
            }

            $departure->increment('reserved_seats', $quote->passengerCount());

            return $booking;
        });

        $this->sendConfirmation($booking);

        return redirect()->to(URL::temporarySignedRoute('front.bookings.thank-you', now()->addDays(30), ['booking' => $booking->reference]));
    }

    public function thankYou(string $booking): View
    {
        $booking = Booking::where('reference', $booking)->with(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers'])->firstOrFail();

        return view('front.pages.booking-thank-you', ['booking' => $booking] + $this->layoutData());
    }

    private function sendConfirmation(Booking $booking): void
    {
        $booking->load(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers']);

        try {
            Mail::to($booking->email)->send(new BookingReceived($booking));
            if ($office = setting('email')) {
                Mail::to($office)->send(new BookingReceived($booking, forAdmin: true));
            }
            $booking->forceFill(['confirmation_sent_at' => now()])->save();
        } catch (\Throwable $e) {
            // The booking is already saved; a mail outage must not lose it.
            Log::error('Booking confirmation email failed', ['booking' => $booking->reference, 'error' => $e->getMessage()]);
        }
    }

    private function tour(string $slug): Tour
    {
        return Tour::query()
            ->where('status', 'published')
            ->whereTranslation('slug', $slug)
            ->with([
                'translations', 'destination.translations', 'pickupPoints.translations',
                'departures' => fn ($q) => $q->upcoming()->whereIn('status', ['available', 'limited'])->with('prices')->orderBy('departs_at'),
            ])
            ->firstOrFail();
    }

    private function pickupPoints(Tour $tour)
    {
        return $tour->pickupPoints->where('is_active', true)->isNotEmpty()
            ? $tour->pickupPoints->where('is_active', true)->values()
            : PickupPoint::where('is_active', true)->with('translations')->orderBy('sort_order')->get();
    }
}
