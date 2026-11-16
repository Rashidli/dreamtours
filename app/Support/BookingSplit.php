<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Move one passenger to another date, or cancel one passenger and keep the rest (CRM §6).
 * The passenger moves into a booking of their own, linked to the original (split_from), so
 * nothing is deleted: the original keeps its payments and is recalculated without them; the new
 * booking is priced at the new date's fare (a move) or kept as a cancelled record (a cancel).
 * A single-passenger booking is simply moved; cancelling it is cancelling the booking.
 */
class BookingSplit
{
    public static function move(Booking $booking, BookingPassenger $passenger, TourDeparture $to, User $by, string $reason, ?string $overrideReason = null): Booking
    {
        return DB::transaction(function () use ($booking, $passenger, $to, $by, $reason, $overrideReason) {
            [$booking, $passenger] = self::lock($booking, $passenger);
            if ($to->id === $booking->tour_departure_id) {
                throw ValidationException::withMessages(['move' => 'Choose a different date.']);
            }
            $to = TourDeparture::with('tour')->findOrFail($to->id);
            $needsSeat = self::needsSeat($passenger, $to);
            Capacity::claim($to, $needsSeat ? 1 : 0, $by, $overrideReason, 'Moving '.self::name($passenger).' from '.$booking->reference);
            $newPrice = self::fare($passenger, $to);
            $from = $booking->departure?->activityLabel();

            if ($booking->passengers()->count() === 1) {
                // The whole booking moves; its price follows the new date's fare.
                self::clearTripState($passenger);
                $booking->forceFill(['tour_id' => $to->tour_id, 'tour_departure_id' => $to->id, 'total_amount' => $newPrice, 'balance_due_date' => null, 'updated_by' => $by->id])->save();
                $passenger->forceFill(['price' => $newPrice])->saveQuietly();
                BookingMoney::sync($booking->fresh());
                Activity::record('booking_moved', $booking, ['departure' => [$from, $to->activityLabel()], 'total' => [null, money($newPrice, $booking->currency)]], reason: $reason);

                return $booking->fresh();
            }

            $new = self::splitOff($booking, $passenger, $to, $booking->status, $newPrice, $by, "Moved from {$booking->reference}: {$reason}");
            Activity::record('passenger_moved', $booking, ['passenger' => [self::name($passenger), null], 'moved to' => [null, $new->reference.' · '.$to->activityLabel()]], reason: $reason);

            return $new;
        });
    }

    public static function cancel(Booking $booking, BookingPassenger $passenger, User $by, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $passenger, $by, $reason) {
            [$booking, $passenger] = self::lock($booking, $passenger);
            if ($booking->passengers()->count() === 1) {
                throw ValidationException::withMessages(['cancel' => 'This is the only passenger — cancel the whole booking instead (set its status to Cancelled).']);
            }
            $new = self::splitOff($booking, $passenger, $booking->departure, 'cancelled', self::fare($passenger, $booking->departure, keepPrice: true), $by, "Passenger cancelled from {$booking->reference}: {$reason}");
            Activity::record('passenger_cancelled', $booking, ['passenger' => [self::name($passenger), null], 'kept as' => [null, $new->reference.' (cancelled)']], reason: $reason);

            return $new;
        });
    }

    /** @return array{0: Booking, 1: BookingPassenger} */
    private static function lock(Booking $booking, BookingPassenger $passenger): array
    {
        $booking = Booking::whereKey($booking->id)->lockForUpdate()->with('departure.tour')->firstOrFail();
        $passenger = BookingPassenger::whereKey($passenger->id)->lockForUpdate()->firstOrFail();
        if ($passenger->booking_id !== $booking->id) {
            abort(404);
        }
        if ($booking->status === 'cancelled' || !$booking->departure) {
            throw ValidationException::withMessages(['move' => 'Only passengers of an active tour booking can be moved or cancelled.']);
        }

        return [$booking, $passenger];
    }

    /** New booking for one passenger (same contact and customer), original recalculated without them. */
    private static function splitOff(Booking $from, BookingPassenger $p, TourDeparture $to, string $status, string $price, User $by, string $note): Booking
    {
        $oldPrice = self::fare($p, $from->departure, keepPrice: true);
        $copy = collect($from->only(['first_name', 'last_name', 'email', 'phone', 'whatsapp', 'emergency_contact_name', 'emergency_contact_phone', 'currency', 'tax_included']))->all();
        $new = Booking::create($copy + [
            'customer_id' => $from->customer_id,
            'tour_id' => $to->tour_id,
            'tour_departure_id' => $to->id,
            'pickup_point_id' => $p->pickup_point_id,
            'type' => $from->type,
            'status' => $status,
            'participant_count' => 1,
            'seat_count' => self::needsSeat($p, $to) ? 1 : 0,
            'source' => 'split',
            'total_amount' => $price,
            'deposit_amount' => $status === 'cancelled' ? '0.00' : BookingMoney::defaultDeposit($price),
            'internal_notes' => $note,
            'updated_by' => $by->id,
        ]);
        $new->forceFill(['split_from_booking_id' => $from->id])->saveQuietly();

        self::clearTripState($p);
        $p->forceFill(['booking_id' => $new->id, 'price' => $price])->saveQuietly();

        $left = $from->passengers()->get();
        $lapInfants = $from->departure->tour?->infant_needs_seat ? 0 : $left->where('category', 'infant')->count();
        $from->forceFill([
            'participant_count' => $left->count(),
            'seat_count' => $left->count() - $lapInfants,
            'total_amount' => Money::max0(Money::sub((string) $from->total_amount, $oldPrice)),
            'pickup_point_id' => $left->first()?->pickup_point_id ?? $from->pickup_point_id,
            'updated_by' => $by->id,
        ])->save();
        BookingMoney::sync($from->fresh());
        BookingMoney::sync($new->fresh());

        return $new->fresh();
    }

    /** What the passenger pays on a date: their own price when staying, else that date's fare for their fare code. */
    private static function fare(BookingPassenger $p, TourDeparture $d, bool $keepPrice = false): string
    {
        if ($keepPrice && $p->price !== null) {
            return Money::of($p->price);
        }
        $fare = $d->fares()->firstWhere('code', $p->price_code ?: ($p->category ?: 'adult')) ?? $d->fares()->firstWhere('code', 'adult');

        return Money::of($fare?->price ?? $d->price);
    }

    private static function needsSeat(BookingPassenger $p, TourDeparture $d): bool
    {
        return $p->category !== 'infant' || (bool) $d->tour?->infant_needs_seat;
    }

    /** A seat, attendance mark or seat notice belongs to the old trip. */
    private static function clearTripState(BookingPassenger $p): void
    {
        $p->forceFill(['departure_vehicle_id' => null, 'seat_number' => null, 'seat_notified' => null, 'attendance_status' => null, 'attendance_at' => null, 'attendance_by' => null, 'attendance_note' => null])->saveQuietly();
    }

    private static function name(BookingPassenger $p): string
    {
        return trim("{$p->first_name} {$p->last_name}");
    }
}
