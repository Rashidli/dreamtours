<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesFrontLayout;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\BookingQuote;
use App\Support\CountryCodes;
use App\Support\DepartureCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The step-by-step tour booking: date in a calendar and travellers → passenger names,
 * pickup points and meals → contact and WhatsApp → extras → deposit, payment proof,
 * policies and waiver → review → confirm. Nothing is saved until the final confirmation.
 */
class BookingPageController extends Controller
{
    use SharesFrontLayout;

    public function create(Request $request, string $slug): View|RedirectResponse
    {
        $tour = $this->tour($slug);

        if ($tour->is_private || !$tour->departures->contains(fn (TourDeparture $d) => $d->is_bookable)) {
            return redirect()->route('front.tours.show', $slug)->with('message', 'This tour has no open dates right now — ask us and we will tell you when booking opens.');
        }

        $pickupPoints = $tour->departures->flatMap(fn (TourDeparture $d) => $d->availablePickupPoints())->unique('id')->values();
        $requested = $tour->departures->first(fn (TourDeparture $d) => $d->id === $request->integer('departure') && $d->is_bookable);

        return view('front.pages.tour-booking', [
            'tour' => $tour,
            'pickupPoints' => $pickupPoints,
            'countries' => CountryCodes::all(),
            'calendarDepartures' => DepartureCalendar::payload($tour->departures),
            'selectedDepartureId' => $requested?->id ?? $tour->departures->first(fn (TourDeparture $d) => $d->is_bookable)?->id,
            'departuresPayload' => $tour->departures->filter(fn (TourDeparture $d) => $d->is_bookable)->map(fn (TourDeparture $d) => [
                'id' => $d->id,
                'label' => DepartureCalendar::range($d),
                'time' => $d->departs_at->format('g:i A'),
                'currency' => $d->currency,
                'seats' => $d->available_seats,
                'prices' => $d->prices->map(fn ($p) => ['code' => $p->code, 'label' => $p->label, 'price' => (float) $p->price, 'occupancy' => $p->occupancy])->values(),
                'basePrice' => (float) $d->price,
                'pickups' => $d->availablePickupPoints()->pluck('id')->map(fn ($id) => (string) $id)->values(),
            ])->values(),
            'termsUrl' => setting('terms_url', '/terms-and-conditions'),
            'privacyUrl' => setting('privacy_url', '/privacy-policy'),
        ] + $this->layoutData());
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tour = $this->tour($slug);

        // A second click or a resent form must not create a second booking.
        if ($request->filled('submission_token') && ($existing = Booking::where('submission_token', $request->input('submission_token'))->first())) {
            return $this->thankYouRedirect($existing);
        }

        $departure = $tour->departures->firstWhere('id', $request->integer('tour_departure_id'));
        $pickupIds = $departure ? $departure->availablePickupPoints()->pluck('id')->all() : [];
        $meals = $tour->mealChoices();

        $data = $request->validate([
            'submission_token' => ['required', 'string', 'max:64'],
            'tour_departure_id' => ['required', 'integer'],
            'selections' => ['required', 'array'],
            'selections.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'passengers' => ['required', 'array', 'min:1', 'max:100'],
            'passengers.*.first_name' => ['required', 'string', 'max:100'],
            'passengers.*.last_name' => ['required', 'string', 'max:100'],
            'passengers.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'passengers.0.date_of_birth' => ['required', 'date', 'before:today'],
            'passengers.*.is_child' => ['nullable', 'boolean'],
            'passengers.*.pickup_point_id' => ['required', 'integer', Rule::in($pickupIds ?: [0])],
            'passengers.*.meal_selection' => $meals ? ['required', 'string', Rule::in($meals)] : ['nullable', 'string', 'max:100'],
            'roommate_request' => ['nullable', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp_country' => ['required', 'string', 'size:2'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'meal_selection' => ['nullable', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'food_allergies' => ['nullable', 'string', 'max:1000'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
            'accept_waiver' => $tour->hasWaiver() ? ['accepted'] : ['nullable'],
            'marketing_consent' => ['nullable', 'boolean'],
        ], [
            'passengers.0.date_of_birth.required' => 'Please enter the date of birth of the main passenger.',
            'passengers.*.pickup_point_id.required' => 'Please choose a pickup point for every passenger.',
            'passengers.*.pickup_point_id.in' => 'Please choose one of the pickup points for this date.',
            'passengers.*.meal_selection.required' => 'Please choose a meal for every passenger.',
            'whatsapp.required' => 'Please enter your WhatsApp number.',
            'payment_proof.required' => 'Please upload a screenshot of your deposit payment.',
            'accept_terms.accepted' => 'Please accept the Terms and Conditions of Booking.',
            'accept_privacy.accepted' => 'Please confirm you have read the Privacy Policy.',
            'accept_waiver.accepted' => 'Please read and accept the waiver for this trip.',
        ]);

        $phone = canadian_phone($data['phone']);
        $whatsapp = international_phone(CountryCodes::dial($data['whatsapp_country']), $data['whatsapp']);
        $emergencyPhone = canadian_phone($data['emergency_contact_phone']) ?? $data['emergency_contact_phone'];
        if (!$phone) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid Canadian phone number, e.g. 416-555-0100.']);
        }
        if (!$whatsapp) {
            throw ValidationException::withMessages(['whatsapp' => 'Please enter a complete WhatsApp number for the selected country.']);
        }

        $booking = DB::transaction(function () use ($request, $tour, $data, $phone, $whatsapp, $emergencyPhone) {
            // Lock the date so two people booking the last seats at once cannot both get them.
            $departure = $tour->departures()->open()->with(['prices', 'pickupPoints', 'tour.pickupPoints'])->lockForUpdate()->find($data['tour_departure_id']);
            if (!$departure || !$departure->is_bookable) {
                throw ValidationException::withMessages(['tour_departure_id' => 'This date is no longer available. Please choose another date.']);
            }

            $quote = BookingQuote::make($tour, $departure, $data['selections'], $data['passengers']);
            if (count($data['passengers']) !== $quote->passengerCount()) {
                throw ValidationException::withMessages(['passengers' => "Please enter the names of all {$quote->passengerCount()} passengers."]);
            }
            if ($departure->available_seats < $quote->passengerCount()) {
                throw ValidationException::withMessages(['selections' => "Only {$departure->available_seats} seats are left on this date."]);
            }

            $lead = $data['passengers'][0];
            $now = now();
            $booking = Booking::create([
                'submission_token' => $data['submission_token'],
                'tour_id' => $tour->id,
                'tour_departure_id' => $departure->id,
                'pickup_point_id' => $lead['pickup_point_id'],
                'type' => $tour->is_cruise ? 'cruise' : 'tour',
                'status' => 'new',
                'first_name' => $lead['first_name'],
                'last_name' => $lead['last_name'],
                'date_of_birth' => $lead['date_of_birth'],
                'phone' => $phone,
                'whatsapp' => $whatsapp,
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
                'policies_accepted_at' => $now,
                'terms_accepted_at' => $now,
                'privacy_accepted_at' => $now,
                'marketing_consent' => $request->boolean('marketing_consent'),
                // Keep the exact waiver wording the customer agreed to.
                'waiver_accepted_at' => $tour->hasWaiver() ? $now : null,
                'waiver_text' => $tour->hasWaiver() ? trim(($tour->waiver_title ? $tour->waiver_title."\n\n" : '').$tour->waiver) : null,
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
                    'pickup_point_id' => $passenger['pickup_point_id'],
                    'meal_selection' => $passenger['meal_selection'] ?? null,
                ]);
            }

            return $booking;
        });

        $this->sendConfirmation($booking);

        return $this->thankYouRedirect($booking);
    }

    public function thankYou(string $booking): View
    {
        $booking = Booking::where('reference', $booking)->with(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers.pickupPoint.translations'])->firstOrFail();

        return view('front.pages.booking-thank-you', ['booking' => $booking] + $this->layoutData());
    }

    private function thankYouRedirect(Booking $booking): RedirectResponse
    {
        return redirect()->to(URL::temporarySignedRoute('front.bookings.thank-you', now()->addDays(30), ['booking' => $booking->reference]));
    }

    private function sendConfirmation(Booking $booking): void
    {
        $booking->load(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers.pickupPoint.translations']);

        try {
            Mail::to($booking->email)->send(new BookingReceived($booking));
            if ($office = setting('email')) {
                Mail::to($office)->send(new BookingReceived($booking, forAdmin: true));
            }
            $booking->forceFill(['confirmation_sent_at' => now()])->saveQuietly();
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
                'departures' => fn ($q) => $q->visible()->with(['prices', 'pickupPoints.translations'])->orderBy('departs_at'),
            ])
            ->firstOrFail();
    }

    /** One token per opened booking form, so a resubmission is recognised. */
    public static function newSubmissionToken(): string
    {
        return (string) Str::uuid();
    }
}
