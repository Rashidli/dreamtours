<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Someone waiting for a seat on a full departure (CRM §8). */
class WaitlistEntry extends Model
{
    use Concerns\RecordsActivity;

    /** status => [label, colour] */
    public const STATUSES = [
        'waiting' => ['Waiting', 'warning'],
        'offered' => ['Seat offered', 'info'],
        'booked' => ['Booked', 'success'],
        'removed' => ['Removed', 'secondary'],
    ];

    protected $fillable = ['tour_departure_id', 'customer_id', 'first_name', 'last_name', 'email', 'phone', 'guests', 'note', 'source', 'position', 'created_by'];

    protected $casts = ['offered_at' => 'datetime'];

    public function departure() { return $this->belongsTo(TourDeparture::class, 'tour_departure_id'); }

    public function customer() { return $this->belongsTo(Customer::class); }

    public function booking() { return $this->belongsTo(Booking::class); }

    public function offeredBy() { return $this->belongsTo(User::class, 'offered_by'); }

    /** Still in the queue: waiting, or offered a seat and not yet booked. */
    public function scopeActive(Builder $q): Builder { return $q->whereIn('status', ['waiting', 'offered'])->orderBy('position')->orderBy('id'); }

    public function getFullNameAttribute(): string { return trim("{$this->first_name} {$this->last_name}"); }

    public function activityLabel(): string { return $this->full_name.' · waitlist '.($this->departure?->activityLabel() ?? ''); }
}
