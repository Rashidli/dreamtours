<?php

namespace App\Support;

use App\Mail\SeatAssignment;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class SeatNotices
{
    /** Why a booking cannot receive its seat numbers yet, or null when it can. */
    public static function blocker(Booking $booking): ?string
    {
        $booking->loadMissing('passengers');

        return match (true) {
            $booking->payment_status !== 'paid' => 'The booking is not fully paid yet.',
            $booking->passengers->isEmpty() => 'The booking has no passengers.',
            $booking->passengers->contains(fn ($p) => blank($p->seat_number)) => 'Every passenger needs a seat number first.',
            default => null,
        };
    }

    public static function send(Booking $booking): void
    {
        $booking->loadMissing(['tour.translations', 'tour.contentItems.translations', 'departure', 'pickupPoint.translations', 'passengers.pickupPoint.translations']);
        Mail::to($booking->email)->send(new SeatAssignment($booking));
        $booking->forceFill(['seat_notice_sent_at' => now()])->save();
    }
}
