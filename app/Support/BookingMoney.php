<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\BookingPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Every money figure of a booking, computed from the ledger (§7) — never typed:
 *
 *   price (total_amount)   what the trip costs before adjustments
 *   − discounts            active discounts and complimentary seats
 *   = total due            never below zero
 *   paid                   confirmed payments
 *   − refunds              confirmed refunds
 *   = net collected        stored in bookings.amount_paid
 *   outstanding            total due − net collected (0 for cancelled bookings)
 *   credit                 net collected above the total due (to refund or keep)
 *   pending                payments waiting for verification (not counted as paid)
 *
 * sync() writes the stored columns (amount_paid, discount_amount, refunded_amount,
 * pending_amount, outstanding_amount, payment_status) so lists and reports can
 * filter and add them up in SQL.
 */
class BookingMoney
{
    /** @return array<string, string|bool|Carbon|null> */
    public static function summary(Booking $booking): array
    {
        $payments = BookingPayment::where('booking_id', $booking->id)->get(['kind', 'status', 'amount']);
        $adjustments = BookingAdjustment::where(['booking_id' => $booking->id, 'status' => 'active'])->get(['kind', 'amount']);
        $sum = fn ($rows) => Money::add(...$rows->pluck('amount')->all());

        $price = Money::of($booking->total_amount);
        $discounts = $sum($adjustments);
        $due = Money::max0(Money::sub($price, $discounts));
        $paid = $sum($payments->where('kind', 'payment')->where('status', 'confirmed'));
        $refunds = $sum($payments->where('kind', 'refund')->where('status', 'confirmed'));
        $net = Money::sub($paid, $refunds);
        $cancelled = $booking->status === 'cancelled';
        $outstanding = $cancelled ? '0.00' : Money::max0(Money::sub($due, $net));
        $deposit = Money::min(Money::of($booking->deposit_amount), $due);

        return [
            'price' => $price,
            'discounts' => $discounts,
            'complimentary' => $sum($adjustments->where('kind', 'complimentary')),
            'due' => $due,
            'paid' => $paid,
            'refunds' => $refunds,
            'net' => $net,
            'outstanding' => $outstanding,
            'credit' => Money::max0(Money::sub($net, $cancelled ? '0' : $due)),
            'pending' => $sum($payments->where('kind', 'payment')->where('status', 'pending')),
            'deposit' => $deposit,
            'deposit_outstanding' => $cancelled ? '0.00' : Money::max0(Money::sub($deposit, $net)),
            'due_date' => $booking->balance_due_date,
            'overdue' => !$cancelled && Money::cmp($outstanding, 0) > 0 && $booking->balance_due_date && $booking->balance_due_date->endOfDay()->isPast(),
            'has_complimentary' => $adjustments->contains('kind', 'complimentary'),
        ];
    }

    /** Write the computed figures onto the booking (quietly: the ledger rows carry the history). */
    public static function sync(Booking $booking): array
    {
        $s = self::summary($booking);
        // Requests without a departure (contact, call-back…) carry no money; their payment status is left alone.
        $values = [
            'amount_paid' => $s['net'],
            'discount_amount' => $s['discounts'],
            'refunded_amount' => $s['refunds'],
            'pending_amount' => $s['pending'],
            'outstanding_amount' => $s['outstanding'],
            'balance_due_date' => ($booking->balance_due_date ?? self::defaultDueDate($booking))?->toDateString(),
            'payment_status' => $booking->tour_departure_id ? self::status($booking, $s) : ($booking->payment_status ?? 'unpaid'),
        ];
        // A plain query: no model events or dirty-checking (sync runs inside the booking's own created/updated events).
        Booking::whereKey($booking->id)->update($values);
        $booking->forceFill($values)->syncOriginalAttributes(array_keys($values));

        return $s;
    }

    public static function status(Booking $booking, array $s): string
    {
        return match (true) {
            $s['has_complimentary'] && Money::isZero($s['due']) => 'complimentary',
            Money::cmp($s['refunds'], 0) > 0 && Money::cmp($s['net'], 0) <= 0 => 'refunded',
            Money::cmp($s['due'], 0) > 0 && Money::cmp($s['net'], $s['due']) >= 0 => 'paid',
            Money::isZero($s['due']) && Money::cmp($s['net'], 0) >= 0 && Money::cmp($s['price'], 0) > 0 => 'paid', // fully discounted
            Money::cmp($s['net'], 0) > 0 && Money::cmp($s['deposit'], 0) > 0 && Money::cmp($s['net'], $s['deposit']) >= 0 => 'deposit_paid',
            Money::cmp($s['net'], 0) > 0 => 'partially_paid',
            Money::cmp($s['pending'], 0) > 0 => 'proof_submitted',
            default => 'unpaid',
        };
    }

    /** Balance due N days before departure (tour setting, else global, default 7); never before the booking was made. */
    public static function defaultDueDate(Booking $booking): ?Carbon
    {
        $departs = $booking->departure?->departs_at;
        if (!$departs) {
            return null;
        }
        $days = (int) ($booking->tour?->balance_due_days ?? setting('balance_due_days', 7));
        $due = $departs->copy()->startOfDay()->subDays($days);
        $made = ($booking->created_at ?? now())->copy()->startOfDay();

        return $due->lt($made) ? $made : $due;
    }

    /** Deposit for a price when the tour has no per-person deposit: the default percentage (setting). */
    public static function defaultDeposit(string|float $price): string
    {
        return Money::mul($price, bcdiv((string) min(100, max(0, (int) setting('default_deposit_percent', 100))), '100', 4));
    }

    /** Record money received. Staff who may confirm can record it as already confirmed. */
    public static function record(Booking $booking, array $data, ?User $by, bool $confirmed): BookingPayment
    {
        return DB::transaction(function () use ($booking, $data, $by, $confirmed) {
            $payment = BookingPayment::create([
                'booking_id' => $booking->id,
                'kind' => 'payment',
                'amount' => Money::of($data['amount']),
                'currency' => $booking->currency ?: 'CAD',
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_on' => $data['paid_on'] ?? now()->toDateString(),
                'status' => $confirmed ? 'confirmed' : 'pending',
                'proof_path' => $data['proof_path'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_by' => $by?->id,
                'confirmed_by' => $confirmed ? $by?->id : null,
                'confirmed_at' => $confirmed ? now() : null,
                'receipt_number' => $confirmed ? self::nextNumber('R') : null,
            ]);
            self::sync($booking);

            return $payment;
        });
    }

    public static function confirm(BookingPayment $payment, User $by): void
    {
        abort_unless($payment->status === 'pending' && !$payment->isRefund(), 422, 'Only pending payments can be confirmed.');
        DB::transaction(function () use ($payment, $by) {
            $payment->update(['status' => 'confirmed', 'confirmed_by' => $by->id, 'confirmed_at' => now(), 'receipt_number' => self::nextNumber('R')]);
            self::sync($payment->booking);
        });
    }

    public static function reject(BookingPayment $payment, string $reason): void
    {
        abort_unless($payment->status === 'pending', 422, 'Only pending payments can be rejected.');
        $payment->update(['status' => 'rejected', 'reason' => $reason]);
        self::sync($payment->booking);
    }

    /** Money back on a confirmed payment, up to what is left of it. */
    public static function refund(BookingPayment $payment, string|float $amount, string $method, string $reason, User $by): BookingPayment
    {
        return DB::transaction(function () use ($payment, $amount, $method, $reason, $by) {
            $payment = BookingPayment::lockForUpdate()->findOrFail($payment->id);
            abort_unless($payment->status === 'confirmed' && !$payment->isRefund(), 422, 'Only confirmed payments can be refunded.');
            abort_if(Money::cmp($amount, 0) <= 0 || Money::cmp($amount, $payment->refundable()) > 0, 422, 'The refund must be more than zero and at most '.money($payment->refundable(), $payment->currency).'.');
            $refund = BookingPayment::create([
                'booking_id' => $payment->booking_id, 'kind' => 'refund', 'refund_of_id' => $payment->id, 'amount' => Money::of($amount),
                'currency' => $payment->currency, 'method' => $method, 'paid_on' => now()->toDateString(), 'status' => 'confirmed', 'reason' => $reason,
                'recorded_by' => $by->id, 'confirmed_by' => $by->id, 'confirmed_at' => now(), 'receipt_number' => self::nextNumber('RF'),
            ]);
            self::sync($payment->booking);

            return $refund;
        });
    }

    /** Cancel a row entered by mistake: it stays in the ledger, crossed out, with the reason. */
    public static function void(BookingPayment $row, string $reason): void
    {
        abort_unless(in_array($row->status, ['pending', 'confirmed'], true), 422, 'This entry is already closed.');
        abort_if(!$row->isRefund() && $row->refunds()->where('status', 'confirmed')->exists(), 422, 'Void its refunds first.');
        $row->update(['status' => 'voided', 'reason' => $reason]);
        self::sync($row->booking);
    }

    public static function adjust(Booking $booking, string $kind, string|float $amount, string $reason, ?int $passengerId, User $by): BookingAdjustment
    {
        $adjustment = BookingAdjustment::create(['booking_id' => $booking->id, 'kind' => $kind, 'amount' => Money::of($amount), 'reason' => $reason, 'booking_passenger_id' => $passengerId, 'created_by' => $by->id]);
        self::sync($booking);

        return $adjustment;
    }

    public static function voidAdjustment(BookingAdjustment $adjustment, string $reason, User $by): void
    {
        abort_unless($adjustment->status === 'active', 422, 'Already removed.');
        $adjustment->update(['status' => 'voided', 'void_reason' => $reason, 'voided_by' => $by->id, 'voided_at' => now()]);
        self::sync($adjustment->booking);
    }

    /** Next receipt number for the year: R-2026-00001, RF-2026-00001 (refunds). Call inside a transaction. */
    private static function nextNumber(string $prefix): string
    {
        $base = $prefix.'-'.now()->year.'-';
        $last = BookingPayment::where('receipt_number', 'like', $base.'%')->lockForUpdate()->orderByDesc('receipt_number')->value('receipt_number');

        return $base.str_pad((string) ((int) substr((string) $last, strlen($base)) + 1), 5, '0', STR_PAD_LEFT);
    }
}
