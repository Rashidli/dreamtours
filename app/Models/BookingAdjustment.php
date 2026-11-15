<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A discount or complimentary seat: lowers what is owed, but is not money received (§7). */
class BookingAdjustment extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    public const KINDS = ['discount' => 'Discount', 'complimentary' => 'Complimentary seat'];

    protected $fillable = ['booking_id', 'kind', 'amount', 'reason', 'booking_passenger_id', 'status', 'void_reason', 'created_by', 'voided_by', 'voided_at'];

    protected $casts = ['amount' => 'decimal:2', 'voided_at' => 'datetime'];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function passenger() { return $this->belongsTo(BookingPassenger::class, 'booking_passenger_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function activityLabel(): string { return (self::KINDS[$this->kind] ?? $this->kind).' · '.money($this->amount); }
}
