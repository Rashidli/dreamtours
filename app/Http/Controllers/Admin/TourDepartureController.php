<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * A tour's scheduled dates. One tour keeps its content once and gets any number of dates;
 * each date has its own capacity, price, status and pickup points.
 */
class TourDepartureController extends Controller
{
    /** Add one or more dates that share price, capacity, status and pickups. */
    public function store(Request $request, Tour $tour): RedirectResponse
    {
        abort_unless($request->user()->can('create-tour-departures'), 403);
        $rows = self::createMany($tour, $request->all());

        return redirect()->to(route('tours.edit', $tour).'#departures')->with('message', count($rows) === 1 ? 'Date added.' : count($rows).' dates added.');
    }

    public function update(Request $request, Tour $tour, TourDeparture $departure): RedirectResponse
    {
        abort_unless($request->user()->can('edit-tour-departures'), 403);
        abort_unless($departure->tour_id === $tour->id, 404);
        $data = $request->validate(self::rules() + [
            'departs_at' => ['required', 'date'],
            'returns_at' => ['nullable', 'date', 'after_or_equal:departs_at'],
        ]);
        $booked = $departure->bookedPassengers();
        if ($data['capacity'] < $booked + ($data['held_seats'] ?? 0)) {
            throw ValidationException::withMessages(["capacity.{$departure->id}" => "Capacity cannot be below the {$booked} booked passengers plus seats held offline."]);
        }
        $departure->update(collect($data)->except('pickup_point_ids')->all() + ['held_seats' => $data['held_seats'] ?? 0]);
        $departure->pickupPoints()->sync(self::pickupSync($data['pickup_point_ids'] ?? []));
        $departure->recountSeats();

        return redirect()->to(route('tours.edit', $tour).'#departure-'.$departure->id)->with('message', 'Date '.$departure->departs_at->format('M j, Y').' updated.');
    }

    public function destroy(Request $request, Tour $tour, TourDeparture $departure): RedirectResponse
    {
        abort_unless($request->user()->can('delete-tour-departures'), 403);
        abort_unless($departure->tour_id === $tour->id, 404);
        if ($departure->bookings()->exists()) {
            return redirect()->to(route('tours.edit', $tour).'#departures')->withErrors(['departure' => 'This date has bookings, so it cannot be deleted. Set its status to Closed, Hidden or Cancelled instead.']);
        }
        $departure->delete();

        return redirect()->to(route('tours.edit', $tour).'#departures')->with('message', 'Date deleted.');
    }

    /** Passenger list for one date: every traveller on a booking that is not cancelled. */
    public function passengers(Request $request, TourDeparture $departure): View
    {
        abort_unless($request->user()->can('list-bookings'), 403);
        $departure->load(['tour.translations', 'bookings' => fn ($q) => $q->orderBy('created_at'), 'bookings.passengers.pickupPoint.translations']);
        $passengers = $departure->bookings->where('status', '!=', 'cancelled')
            ->flatMap(fn ($booking) => $booking->passengers->map(fn ($p) => ['passenger' => $p, 'booking' => $booking]))
            ->when($request->filled('pickup'), fn ($rows) => $rows->filter(fn ($row) => (string) $row['passenger']->pickup_point_id === $request->pickup))
            ->values();

        return view('admin.tours.passengers', [
            'departure' => $departure,
            'passengers' => $passengers,
            'pickups' => $departure->bookings->flatMap->passengers->pluck('pickupPoint')->filter()->unique('id')->values(),
            'cancelled' => $departure->bookings->where('status', 'cancelled'),
        ]);
    }

    /** Shared by "Add dates" and by creating a tour with its first dates. @return TourDeparture[] */
    public static function createMany(Tour $tour, array $input): array
    {
        $data = validator($input, self::rules() + [
            'dates' => ['required', 'array', 'min:1', 'max:60'],
            'dates.*.departs_at' => ['required', 'date', 'after:now'],
            'dates.*.returns_at' => ['nullable', 'date'],
        ], ['dates.*.departs_at.after' => 'Departure dates must be in the future.'])->validate();
        foreach ($data['dates'] as $i => $date) {
            if (!empty($date['returns_at']) && $date['returns_at'] < $date['departs_at']) {
                throw ValidationException::withMessages(["dates.$i.returns_at" => 'A return date cannot be before its departure.']);
            }
        }
        $shared = collect($data)->except(['dates', 'pickup_point_ids'])->all();

        return collect($data['dates'])->map(function ($date) use ($tour, $shared, $data) {
            $departure = $tour->departures()->create($shared + ['departs_at' => $date['departs_at'], 'returns_at' => $date['returns_at'] ?? null, 'held_seats' => $shared['held_seats'] ?? 0]);
            $departure->pickupPoints()->sync(self::pickupSync($data['pickup_point_ids'] ?? []));
            $departure->recountSeats();

            return $departure;
        })->all();
    }

    private static function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'gte:price'],
            'currency' => ['required', 'string', 'size:3'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'held_seats' => ['nullable', 'integer', 'min:0', 'lte:capacity'],
            'status' => ['required', Rule::in(array_keys(TourDeparture::STATUSES))],
            'pickup_point_ids' => ['nullable', 'array'],
            'pickup_point_ids.*' => ['integer', 'exists:pickup_points,id'],
        ];
    }

    private static function pickupSync(array $ids): array
    {
        return collect(array_values($ids))->mapWithKeys(fn ($id, $i) => [(int) $id => ['sort_order' => $i]])->all();
    }
}
