<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Support\Activity;
use App\Support\BookingMoney;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Brings bookings made before the payment ledger into it, then recomputes every
 * booking's figures. A deposit screenshot becomes a pending payment for staff to
 * verify; an amount typed as "paid" before becomes a confirmed opening payment so
 * totals don't change. Bookings that already have ledger rows are only recomputed.
 * Safe to run again.
 */
class SyncPayments extends Command
{
    protected $signature = 'crm:sync-payments {--dry-run : Show what would happen without saving anything}';

    protected $description = 'Bring existing bookings into the payment ledger and recompute balances';

    public function handle(): int
    {
        $created = $synced = 0;
        DB::beginTransaction();
        try {
            $withLedger = BookingPayment::distinct()->pluck('booking_id')->flip();
            foreach (Booking::whereNotNull('tour_departure_id')->with(['departure', 'tour'])->oldest('id')->get() as $booking) {
                if (!isset($withLedger[$booking->id])) {
                    if (Money::cmp($booking->amount_paid, 0) > 0) {
                        $payment = BookingMoney::record($booking, ['amount' => $booking->amount_paid, 'method' => 'other', 'paid_on' => $booking->created_at->toDateString(), 'note' => 'Amount marked as paid before the payment ledger existed.'], null, confirmed: true);
                        $this->line("{$booking->reference}: opening payment {$payment->receipt_number} ".money($payment->amount, $payment->currency));
                        $created++;
                    } elseif ($booking->payment_proof && Money::cmp($booking->deposit_amount, 0) > 0) {
                        BookingMoney::record($booking, ['amount' => $booking->deposit_amount, 'method' => 'etransfer', 'paid_on' => $booking->created_at->toDateString(), 'proof_path' => $booking->payment_proof, 'note' => 'Screenshot uploaded by the customer with the booking.'], null, confirmed: false);
                        $this->line("{$booking->reference}: deposit screenshot → pending payment ".money($booking->deposit_amount, $booking->currency));
                        $created++;
                    }
                }
                $s = BookingMoney::sync($booking->refresh());
                $this->line("{$booking->reference}: due ".money($s['due'], $booking->currency).' · collected '.money($s['net'], $booking->currency).' · outstanding '.money($s['outstanding'], $booking->currency).' · '.$booking->payment_status.' · balance due '.($booking->balance_due_date?->toDateString() ?? '—'));
                $synced++;
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn("Dry run: {$created} ledger entries would be created, {$synced} bookings recomputed. Nothing was saved.");

                return self::SUCCESS;
            }
            Activity::record('payments_synced', null, ['ledger entries created' => $created, 'bookings recomputed' => $synced], label: 'Payment ledger');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $this->info("{$created} ledger entries created, {$synced} bookings recomputed.");

        return self::SUCCESS;
    }
}
