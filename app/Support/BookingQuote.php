<?php

namespace App\Support;

use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourDeparturePrice;
use Illuminate\Validation\ValidationException;

/**
 * Turns a customer's price selection into passenger slots and totals. The browser shows
 * the same arithmetic live, but the amounts that are stored always come from here.
 *
 * Room prices are per person, so choosing 1 × Triple creates three passenger slots in
 * room 1. On a departure with room pricing, a child shares a room and is flagged per
 * passenger instead of being counted separately.
 */
class BookingQuote
{
    /** @var array<int, array{code: string, price: float, room: ?int, label: string}> */
    public array $slots = [];
    public float $total = 0;
    public float $deposit = 0;

    public function __construct(public readonly Tour $tour, public readonly TourDeparture $departure) {}

    /**
     * @param  array<string, int|string|null>  $selections  code => quantity (rooms or people)
     * @param  array<int, array<string, mixed>>  $passengers  submitted passengers, in slot order
     */
    public static function make(Tour $tour, TourDeparture $departure, array $selections, array $passengers = []): self
    {
        $quote = new self($tour, $departure);
        $prices = $departure->prices->keyBy('code');

        if ($prices->isEmpty()) {
            // A departure without a price list charges its base price per person.
            $prices = collect(['adult' => new TourDeparturePrice(['code' => 'adult', 'price' => $departure->price])]);
        }

        $roomPricing = $prices->contains(fn ($price) => $price->is_room);
        $room = 0;

        foreach ($prices as $code => $price) {
            $quantity = max(0, min(50, (int) ($selections[$code] ?? 0)));
            if ($roomPricing && !$price->is_room) {
                continue; // children are flagged per passenger inside a room
            }
            for ($n = 0; $n < $quantity; $n++) {
                $roomNumber = $price->is_room ? ++$room : null;
                for ($seat = 0; $seat < ($price->occupancy ?? 1); $seat++) {
                    $quote->slots[] = ['code' => $code, 'price' => (float) $price->price, 'room' => $roomNumber, 'label' => $price->label];
                }
            }
        }

        if ($roomPricing && $prices->has('child')) {
            foreach ($quote->slots as $i => $slot) {
                if (!empty($passengers[$i]['is_child'])) {
                    $quote->slots[$i] = ['code' => 'child', 'price' => (float) $prices['child']->price, 'room' => $slot['room'], 'label' => $prices['child']->label];
                }
            }
        }

        if ($quote->slots === []) {
            throw ValidationException::withMessages(['selections' => 'Please choose at least one passenger or room.']);
        }

        $quote->total = round(array_sum(array_column($quote->slots, 'price')), 2);
        $quote->deposit = $tour->deposit_per_person
            ? min($quote->total, round((float) $tour->deposit_per_person * count($quote->slots), 2))
            : $quote->total;

        return $quote;
    }

    public function passengerCount(): int
    {
        return count($this->slots);
    }
}
