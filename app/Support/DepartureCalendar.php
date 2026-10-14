<?php

namespace App\Support;

use App\Models\TourDeparture;
use Illuminate\Support\Collection;

/** Shapes a tour's departures for the front-end calendar (resources/views/front/partials/departure_calendar). */
class DepartureCalendar
{
    /** @param Collection<int, TourDeparture> $departures */
    public static function payload(Collection $departures): array
    {
        return $departures->map(fn (TourDeparture $d) => [
            'id' => $d->id,
            'date' => $d->departs_at->format('Y-m-d'),
            'label' => self::range($d),
            'time' => $d->departs_at->format('g:i A'),
            'status' => $d->public_status,
            'statusLabel' => match ($d->public_status) { 'sold_out' => 'Sold out', 'closed' => 'Closed', 'limited' => 'Limited seats', default => 'Available' },
            'bookable' => $d->is_bookable,
            'seats' => $d->available_seats,
            'priceLabel' => money(self::adultFrom($d), $d->currency),
        ])->values()->all();
    }

    /** Lowest adult price of the date: a child fare must not be advertised as the "from" price. */
    public static function adultFrom(TourDeparture $d): float
    {
        $adult = $d->prices->where('code', '!=', 'child');

        return (float) ($adult->isNotEmpty() ? $adult->min('price') : $d->from_price);
    }

    /** "Sat, Oct 17, 2026" or, for a multi-day trip, "Oct 17 – Oct 19, 2026". */
    public static function range(TourDeparture $d): string
    {
        $start = $d->departs_at;
        $end = $d->returns_at;
        if (!$end || $end->isSameDay($start)) {
            return $start->format('D, M j, Y');
        }

        return $start->format('M j').' – '.$end->format($end->year === $start->year ? 'M j, Y' : 'M j, Y');
    }
}
