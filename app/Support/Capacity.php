<?php

namespace App\Support;

use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Seat limits for staff actions (CRM §8, AT-05). Overbooking is blocked; someone with
 * override-capacity may go ahead only with a reason, which is written to the activity history.
 * Call inside a transaction: the departure row stays locked until it commits, so two staff
 * cannot sell the last seat twice.
 */
class Capacity
{
    public static function claim(TourDeparture $departure, int $seats, ?User $by, ?string $overrideReason, string $what): TourDeparture
    {
        $d = TourDeparture::whereKey($departure->id)->lockForUpdate()->firstOrFail();
        $left = max(0, $d->available_seats);
        if ($seats <= 0 || $left >= $seats) {
            return $d;
        }
        $reason = trim((string) $overrideReason);
        if ($by?->can('override-capacity') && $reason !== '') {
            Activity::record('capacity_override', $d, ['seats' => ["{$left} left", "{$seats} booked"], 'for' => [null, $what]], reason: mb_substr($reason, 0, 255));

            return $d;
        }
        throw ValidationException::withMessages(['capacity' => "Only {$left} ".str('seat')->plural($left)." left on this date; {$seats} needed. "
            .($by?->can('override-capacity') ? 'To book anyway, tick “Book over capacity” and give a reason.' : 'Ask an administrator if this booking must go ahead.')]);
    }
}
