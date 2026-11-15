<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Money in (payment) or out (refund) on a booking. Payments start pending until
 * staff confirm the funds arrived (§7: an uploaded receipt is not proof); confirmed
 * rows are never deleted — a mistake is voided with a reason.
 */
class BookingPayment extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    public const METHODS = ['etransfer' => 'Interac e-Transfer', 'cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'bank_draft' => 'Bank draft', 'cheque' => 'Cheque', 'other' => 'Other'];

    public const STATUSES = ['pending' => ['Pending verification', 'warning'], 'confirmed' => ['Confirmed', 'success'], 'rejected' => ['Rejected', 'danger'], 'voided' => ['Voided', 'secondary']];

    protected $fillable = ['receipt_number', 'booking_id', 'kind', 'refund_of_id', 'amount', 'currency', 'method', 'reference', 'paid_on', 'status', 'proof_path', 'note', 'reason', 'recorded_by', 'confirmed_by', 'confirmed_at'];

    protected $casts = ['amount' => 'decimal:2', 'paid_on' => 'date', 'confirmed_at' => 'datetime'];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function refundOf() { return $this->belongsTo(self::class, 'refund_of_id'); }
    public function refunds() { return $this->hasMany(self::class, 'refund_of_id'); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
    public function confirmer() { return $this->belongsTo(User::class, 'confirmed_by'); }

    public function isRefund(): bool { return $this->kind === 'refund'; }

    public function activityLabel(): string
    {
        return ($this->isRefund() ? 'Refund' : 'Payment').' '.($this->receipt_number ?? '#'.$this->id).' · '.money($this->amount, $this->currency);
    }

    /** What is left to refund from this payment (confirmed refunds already taken off). */
    public function refundable(): string
    {
        return \App\Support\Money::max0(\App\Support\Money::sub($this->amount, $this->refunds()->where('status', 'confirmed')->sum('amount')));
    }

    public function scopeConfirmed(Builder $q): Builder { return $q->where('status', 'confirmed'); }
    public function scopePayments(Builder $q): Builder { return $q->where('kind', 'payment'); }
    public function scopeRefunds(Builder $q): Builder { return $q->where('kind', 'refund'); }
}
