<?php

namespace App\Support;

use App\Mail\SeatAssignment;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class SeatNotices
{
    /**
     * Why a booking cannot receive its seat numbers yet, or null when it can. On dates with buses
     * the seating must be published with no draft changes (CRM §9: editing a draft never emails guests).
     */
    public static function blocker(Booking $booking): ?string
    {
        $booking->loadMissing(['passengers', 'departure.vehicles']);
        $vehicles = $booking->departure?->vehicles ?? collect();

        return match (true) {
            !in_array($booking->payment_status, ['paid', 'complimentary'], true) => 'The booking is not fully paid yet.',
            $booking->passengers->isEmpty() => 'The booking has no passengers.',
            $booking->passengers->contains(fn ($p) => blank($p->seat_number) || ($vehicles->isNotEmpty() && !$p->departure_vehicle_id)) => 'Every passenger needs a seat number first.',
            $vehicles->contains(fn ($v) => $v->hasDraftChanges()) => 'The seating for this date is a draft. Publish it on the seating board first.',
            default => null,
        };
    }

    /** True when a passenger's seat differs from what the guest was last told. */
    public static function changed(Booking $booking): bool
    {
        $booking->loadMissing('passengers.departureVehicle');

        return $booking->passengers->contains(fn ($p) => Seating::label($p->departureVehicle, $p->seat_number) !== $p->seat_notified);
    }

    public static function send(Booking $booking): void
    {
        $booking->loadMissing(['tour.translations', 'tour.contentItems.translations', 'departure.vehicles', 'pickupPoint.translations', 'passengers.pickupPoint.translations', 'passengers.departureVehicle']);
        Mail::to($booking->email)->send(new SeatAssignment($booking));
        Activity::record('email_queued', $booking, ['to' => $booking->email, 'email' => 'SeatAssignment']);
        $booking->forceFill(['seat_notice_sent_at' => now()])->save();
        $booking->passengers->each(fn ($p) => $p->forceFill(['seat_notified' => Seating::label($p->departureVehicle, $p->seat_number)])->saveQuietly());
    }
}
