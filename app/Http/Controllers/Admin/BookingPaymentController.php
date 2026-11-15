<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\BookingPayment;
use App\Support\BookingMoney;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** Payments, refunds, discounts and payment terms on one booking (§7). */
class BookingPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:record-payments')->only('store');
        $this->middleware('permission:confirm-payments')->only(['confirm', 'reject']);
        $this->middleware('permission:refund-payments')->only(['refund', 'void']);
        $this->middleware('permission:apply-discounts')->only(['adjust', 'voidAdjustment']);
        $this->middleware('permission:edit-bookings')->only('terms');
        $this->middleware('permission:list-bookings')->only('proof');
    }

    public function store(Request $r, Booking $booking): RedirectResponse
    {
        abort_unless($booking->tour_departure_id, 422, 'Payments are recorded on bookings with a departure.');
        $d = $r->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'method' => ['required', Rule::in(array_keys(BookingPayment::METHODS))],
            'paid_on' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
            'proof' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,heic,heif,pdf'],
            'status' => ['required', Rule::in(['pending', 'confirmed'])],
        ]);
        // Only people who may verify money can record it as already confirmed.
        $confirmed = $d['status'] === 'confirmed' && $r->user()->can('confirm-payments');
        $d['proof_path'] = $r->file('proof')?->store('payment-proofs', 'local');
        $outstanding = $booking->outstanding_amount;
        $payment = BookingMoney::record($booking, $d, $r->user(), $confirmed);

        $message = $confirmed ? "Payment {$payment->receipt_number} of ".money($payment->amount, $payment->currency).' recorded.' : 'Payment of '.money($payment->amount, $payment->currency).' recorded as pending verification.';
        if ($confirmed && Money::cmp($payment->amount, $outstanding) > 0) {
            $message .= ' It is more than was owed — the booking now shows a credit.';
        }

        return back()->with('message', $message);
    }

    public function confirm(Request $r, Booking $booking, BookingPayment $payment): RedirectResponse
    {
        $this->belongs($booking, $payment);
        BookingMoney::confirm($payment, $r->user());

        return back()->with('message', "Payment confirmed — receipt {$payment->fresh()->receipt_number}.");
    }

    public function reject(Request $r, Booking $booking, BookingPayment $payment): RedirectResponse
    {
        $this->belongs($booking, $payment);
        BookingMoney::reject($payment, $r->validate(['reason' => ['required', 'string', 'max:255']])['reason']);

        return back()->with('message', 'Payment rejected. It no longer counts as pending.');
    }

    public function refund(Request $r, Booking $booking, BookingPayment $payment): RedirectResponse
    {
        $this->belongs($booking, $payment);
        $d = $r->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$payment->refundable()],
            'method' => ['required', Rule::in(array_keys(BookingPayment::METHODS))],
            'reason' => ['required', 'string', 'max:255'],
        ], ['amount.max' => 'At most '.money($payment->refundable(), $payment->currency).' can be refunded from this payment.']);
        $refund = BookingMoney::refund($payment, $d['amount'], $d['method'], $d['reason'], $r->user());

        return back()->with('message', "Refund {$refund->receipt_number} of ".money($refund->amount, $refund->currency).' recorded.');
    }

    public function void(Request $r, Booking $booking, BookingPayment $payment): RedirectResponse
    {
        $this->belongs($booking, $payment);
        BookingMoney::void($payment, $r->validate(['reason' => ['required', 'string', 'max:255']])['reason']);

        return back()->with('message', ($payment->isRefund() ? 'Refund' : 'Payment').' voided. It stays in the ledger, crossed out.');
    }

    public function adjust(Request $r, Booking $booking): RedirectResponse
    {
        $due = BookingMoney::summary($booking)['due'];
        $d = $r->validate([
            'kind' => ['required', Rule::in(array_keys(BookingAdjustment::KINDS))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$due],
            'reason' => ['required', 'string', 'max:255'],
            'booking_passenger_id' => ['nullable', Rule::exists('booking_passengers', 'id')->where('booking_id', $booking->id)],
        ], ['amount.max' => 'The adjustment cannot be more than what is still due ('.money($due, $booking->currency).').']);
        BookingMoney::adjust($booking, $d['kind'], $d['amount'], $d['reason'], $d['booking_passenger_id'] ?? null, $r->user());

        return back()->with('message', BookingAdjustment::KINDS[$d['kind']].' of '.money($d['amount'], $booking->currency).' applied.');
    }

    public function voidAdjustment(Request $r, Booking $booking, BookingAdjustment $adjustment): RedirectResponse
    {
        abort_unless($adjustment->booking_id === $booking->id, 404);
        BookingMoney::voidAdjustment($adjustment, $r->validate(['reason' => ['required', 'string', 'max:255']])['reason'], $r->user());

        return back()->with('message', BookingAdjustment::KINDS[$adjustment->kind].' removed.');
    }

    /** Deposit and balance due date for this booking only (an exception to the defaults). */
    public function terms(Request $r, Booking $booking): RedirectResponse
    {
        $d = $r->validate([
            'deposit_amount' => ['required', 'numeric', 'min:0', 'max:'.max(0, (float) $booking->total_amount)],
            'balance_due_date' => ['required', 'date'],
            'payment_terms_note' => ['nullable', 'string', 'max:255'],
        ], ['deposit_amount.max' => 'The deposit cannot be more than the price.']);
        $booking->update($d);

        return back()->with('message', 'Payment terms saved.');
    }

    public function proof(Booking $booking, BookingPayment $payment): Response
    {
        $this->belongs($booking, $payment);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    private function belongs(Booking $booking, BookingPayment $payment): void
    {
        abort_unless($payment->booking_id === $booking->id, 404);
    }
}
