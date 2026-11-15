<?php

namespace App\Support;

use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Validation\ValidationException;

/**
 * Turns a customer's price selection into passenger slots and totals. The browser shows
 * the same arithmetic live, but the amounts that are stored always come from here.
 *
 * Room prices are per person, so choosing 1 × Triple creates three passenger slots in
 * room 1. On a departure with room pricing, a child shares a room and is flagged per
 * passenger instead of being counted separately; infants are always counted on their own.
 * An infant takes a seat only when the tour says so.
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
        // Includes an adult fare at the base price when the date has no full fare of its own.
        $prices = $departure->fares()->keyBy('code');

        $roomPricing = $prices->contains(fn ($price) => $price->is_room);
        $room = 0;

        foreach ($prices as $code => $price) {
            $quantity = max(0, min(50, (int) ($selections[$code] ?? 0)));
            if ($roomPricing && !$price->is_room && $code !== 'infant') {
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
        // The per-person deposit applies to paying passengers only (a free infant pays none).
        $paying = count(array_filter($quote->slots, fn ($slot) => $slot['price'] > 0));
        // Without one, the default deposit percentage applies (setting; 100 = full price).
        $quote->deposit = $tour->deposit_per_person
            ? min($quote->total, round((float) $tour->deposit_per_person * $paying, 2))
            : (float) \App\Support\BookingMoney::defaultDeposit($quote->total);

        return $quote;
    }

    public function passengerCount(): int
    {
        return count($this->slots);
    }

    /** Seats this booking occupies: every passenger except infants on a lap. */
    public function seatCount(): int
    {
        return count(array_filter($this->slots, fn ($slot) => $slot['code'] !== 'infant' || $this->tour->infant_needs_seat));
    }
}
