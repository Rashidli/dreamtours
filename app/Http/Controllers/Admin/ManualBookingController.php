<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\TourDeparture;
use App\Support\BookingMail;
use App\Support\BookingMoney;
use App\Support\Capacity;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Staff-made bookings (CRM §6): pick or create the customer, choose the date, fill each passenger.
 * Prices always come from the date's fares on the server; the form only previews them.
 */
class ManualBookingController extends Controller
{
    public const STATUSES = ['new', 'payment_pending', 'confirmed'];

    public function __construct()
    {
        $this->middleware('permission:create-bookings')->only(['create', 'store', 'customers']);
        $this->middleware('permission:edit-bookings')->only(['movePassenger', 'cancelPassenger']);
    }

    /** Move one passenger to another date (CRM §6); the booking page links to the result. */
    public function movePassenger(Request $request, Booking $booking, \App\Models\BookingPassenger $passenger): RedirectResponse
    {
        $d = $request->validate(['tour_departure_id' => ['required', 'integer', 'exists:tour_departures,id'], 'reason' => ['required', 'string', 'max:255']]);
        $result = \App\Support\BookingSplit::move($booking, $passenger, TourDeparture::findOrFail($d['tour_departure_id']), $request->user(), $d['reason'], $request->input('override_reason'));
        $name = trim("{$passenger->first_name} {$passenger->last_name}");

        return $result->is($booking)
            ? redirect()->route('bookings.show', $booking)->with('message', "{$name} moved to {$result->departure->departs_at->format('M j, Y')}; the booking price now follows that date.")
            : redirect()->route('bookings.show', $booking)->with('message', "{$name} moved to {$result->departure->departs_at->format('M j, Y')} as booking {$result->reference}. This booking was recalculated without them.");
    }

    /** Cancel one passenger and keep the others; the passenger is kept on a cancelled booking of their own. */
    public function cancelPassenger(Request $request, Booking $booking, \App\Models\BookingPassenger $passenger): RedirectResponse
    {
        $d = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $kept = \App\Support\BookingSplit::cancel($booking, $passenger, $request->user(), $d['reason']);

        return redirect()->route('bookings.show', $booking)->with('message', trim("{$passenger->first_name} {$passenger->last_name}")." cancelled (kept as {$kept->reference}). This booking was recalculated without them; refund any overpayment from Payments.");
    }

    public function create(Request $request): View
    {
        $customer = $request->filled('customer') ? Customer::find($request->integer('customer')) : null;
        // Booking someone off the waitlist: their details, date and party size are filled in.
        $entry = $request->filled('waitlist') ? \App\Models\WaitlistEntry::active()->find($request->integer('waitlist')) : null;
        $customer ??= $entry?->customer;

        return view('admin.bookings.create', [
            'departures' => self::departureOptions(), 'customer' => $customer, 'waitlist' => $entry,
            'prefill' => $entry ? array_merge([['first_name' => $entry->first_name, 'last_name' => $entry->last_name, 'phone' => $entry->phone]], array_fill(0, max(0, $entry->guests - 1), ['last_name' => $entry->last_name])) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $departures = self::departureOptions();
        $d = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'tour_departure_id' => ['required', 'integer', Rule::in(array_keys($departures))],
            'status' => ['required', Rule::in(self::STATUSES)],
            'passengers' => ['required', 'array', 'min:1', 'max:50'],
            'passengers.*.first_name' => ['required', 'string', 'max:100'],
            'passengers.*.last_name' => ['required', 'string', 'max:100'],
            'passengers.*.fare' => ['required', 'string', 'max:30'],
            'passengers.*.pickup_point_id' => ['nullable', 'integer'],
            'passengers.*.meal_selection' => ['nullable', 'string', 'max:100'],
            'passengers.*.phone' => ['nullable', 'string', 'max:30'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'send_confirmation' => ['nullable', 'boolean'],
            'waitlist_entry_id' => ['nullable', 'integer', 'exists:waitlist_entries,id'],
        ]);
        $option = $departures[$d['tour_departure_id']];
        $fares = collect($option['fares'])->keyBy('code');
        $pickups = collect($option['pickups'])->pluck('id')->all();
        foreach ($d['passengers'] as $i => $p) {
            if (!$fares->has($p['fare'])) {
                throw ValidationException::withMessages(["passengers.$i.fare" => 'Choose a fare offered on this date for passenger '.($i + 1).'.']);
            }
            if (!empty($p['pickup_point_id']) && !in_array((int) $p['pickup_point_id'], $pickups, true)) {
                throw ValidationException::withMessages(["passengers.$i.pickup_point_id" => 'That pickup point is not offered on this date.']);
            }
        }
        if (collect($d['passengers'])->every(fn ($p) => $p['fare'] === 'infant')) {
            throw ValidationException::withMessages(['passengers' => 'An infant must travel with an adult — add at least one adult.']);
        }

        $booking = DB::transaction(function () use ($d, $fares, $request) {
            $departure = TourDeparture::with('tour')->findOrFail($d['tour_departure_id']);
            $tour = $departure->tour;
            $lapInfants = $tour->infant_needs_seat ? 0 : collect($d['passengers'])->where('fare', 'infant')->count();
            $seats = count($d['passengers']) - $lapInfants;
            Capacity::claim($departure, $seats, $request->user(), $request->input('override_reason'), 'New booking for '.trim("{$d['first_name']} {$d['last_name']}"));

            $total = Money::add(...collect($d['passengers'])->map(fn ($p) => (string) $fares[$p['fare']]['price'])->all());
            $paying = collect($d['passengers'])->filter(fn ($p) => (float) $fares[$p['fare']]['price'] > 0)->count();
            $first = $d['passengers'][array_key_first($d['passengers'])];
            $booking = Booking::create([
                'tour_id' => $tour->id,
                'tour_departure_id' => $departure->id,
                'pickup_point_id' => $first['pickup_point_id'] ?? null,
                'type' => $tour->is_cruise ? 'cruise' : 'tour',
                'status' => $d['status'],
                'first_name' => $d['first_name'],
                'last_name' => $d['last_name'],
                'email' => mb_strtolower($d['email']),
                'phone' => $d['phone'],
                'whatsapp' => $d['whatsapp'] ?? null,
                'participant_count' => count($d['passengers']),
                'seat_count' => $seats,
                'comments' => $d['comments'] ?? null,
                'internal_notes' => $d['internal_notes'] ?? null,
                'source' => 'admin',
                'currency' => $departure->currency ?: 'CAD',
                'tax_included' => (bool) $tour->tax_included,
                'total_amount' => $total,
                'deposit_amount' => self::deposit(self::depositRule($tour), $total, $paying),
                'updated_by' => $request->user()->id,
            ] + (!empty($d['customer_id']) ? ['customer_id' => (int) $d['customer_id']] : []));
            foreach ($d['passengers'] as $p) {
                $booking->passengers()->create([
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'category' => in_array($p['fare'], ['child', 'infant', 'senior'], true) ? $p['fare'] : 'adult',
                    'price_code' => $p['fare'],
                    'price' => $fares[$p['fare']]['price'],
                    'pickup_point_id' => $p['pickup_point_id'] ?? null,
                    'meal_selection' => $p['meal_selection'] ?? null,
                    'phone' => $p['phone'] ?? null,
                ]);
            }

            return $booking;
        });
        if (!empty($d['waitlist_entry_id']) && ($entry = \App\Models\WaitlistEntry::active()->find($d['waitlist_entry_id']))) {
            \App\Support\Waitlist::booked($entry, $booking);
        }
        if ($booking->status === 'confirmed' && $request->boolean('send_confirmation')) {
            BookingMail::confirmed($booking);
        }

        return redirect()->route('bookings.show', $booking)->with('message', "Booking {$booking->reference} created for ".money($booking->total_amount, $booking->currency).'.');
    }

    /** Customer search for the form: name, email or phone. */
    public function customers(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('list-customers'), 403);
        $q = trim((string) $request->query('q'));
        $digits = preg_replace('/\D/', '', $q);
        $found = mb_strlen($q) < 2 ? collect() : Customer::query()
            ->where(fn ($x) => $x->whereRaw("concat_ws(' ', first_name, last_name, preferred_name) like ?", ["%{$q}%"])->orWhere('email', 'like', '%'.mb_strtolower($q).'%')
                ->when(strlen($digits) >= 3, fn ($y) => $y->orWhere('phone_digits', 'like', "%{$digits}%")))
            ->orderBy('first_name')->limit(10)->get();

        return response()->json(['results' => $found->map(fn (Customer $c) => [
            'id' => $c->id, 'text' => $c->full_name.' · '.collect([$c->email, $c->phone])->filter()->implode(' · '),
            'first_name' => $c->first_name, 'last_name' => $c->last_name, 'email' => $c->email, 'phone' => $c->phone, 'whatsapp' => $c->whatsapp,
        ])->values()]);
    }

    /**
     * How a tour's deposit is worked out: a fixed amount per paying passenger, or (when the tour has none)
     * the site-wide default percentage of the total. Shared by store() and the form preview.
     *
     * @return array{per_person: ?string, percent: int}
     */
    private static function depositRule(?\App\Models\Tour $tour): array
    {
        return [
            'per_person' => $tour?->deposit_per_person ? (string) $tour->deposit_per_person : null,
            'percent' => min(100, max(0, (int) setting('default_deposit_percent', 50))),
        ];
    }

    /** Deposit for a booking total with $paying passengers on a non-zero fare. */
    private static function deposit(array $rule, string $total, int $paying): mixed
    {
        return $rule['per_person'] ? min((float) $total, (float) Money::mul($rule['per_person'], $paying)) : BookingMoney::defaultDeposit($total);
    }

    /**
     * Upcoming dates that can take a booking, with what the form needs for each: fares, pickups,
     * meals and seats left.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function departureOptions(): array
    {
        return TourDeparture::with(['tour.translations', 'tour.pickupPoints.translations', 'pickupPoints.translations', 'prices'])
            ->where('departs_at', '>=', now())->whereNotIn('status', ['cancelled'])->orderBy('departs_at')->get()
            ->mapWithKeys(fn (TourDeparture $d) => [$d->id => [
                'id' => $d->id,
                'tour' => $d->tour?->name,
                'label' => $d->departs_at->format('D, M j, Y g:i A'),
                'left' => max(0, $d->available_seats),
                'status' => $d->status_label,
                'currency' => $d->currency ?: 'CAD',
                'fares' => $d->fares()->map(fn ($f) => ['code' => $f->code, 'label' => $d->tour?->priceLabel($f->code) ?? $f->label, 'price' => (string) $f->price])->values()->all(),
                'pickups' => $d->availablePickupPoints()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
                'meals' => $d->tour?->mealChoices() ?? [],
                'capacity' => (int) $d->capacity,
                'taken' => (int) $d->reserved_seats,
                'infant_needs_seat' => (bool) $d->tour?->infant_needs_seat,
                'deposit_per_person' => self::depositRule($d->tour)['per_person'],
                'deposit_percent' => self::depositRule($d->tour)['percent'],
            ]])->all();
    }
}
