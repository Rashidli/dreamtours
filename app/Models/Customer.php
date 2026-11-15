<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A person in the permanent CRM directory (§4). Bookings and requests point at
 * their customer, so one person never turns into a new contact per booking.
 */
class Customer extends Model
{
    use \App\Models\Concerns\RecordsActivity, SoftDeletes;

    public const SOURCES = ['website' => 'Website', 'manual' => 'Added by staff', 'import' => 'Import', 'referral' => 'Referral partner', 'visa' => 'Visa enquiry'];

    protected $fillable = ['first_name', 'last_name', 'preferred_name', 'email', 'phone', 'whatsapp', 'date_of_birth', 'city', 'language', 'customer_status_id', 'assigned_user_id', 'source', 'marketing_opt_in', 'marketing_opt_in_at', 'marketing_opt_in_source', 'notes', 'last_activity_at'];

    protected $casts = ['date_of_birth' => 'date', 'marketing_opt_in' => 'boolean', 'marketing_opt_in_at' => 'datetime', 'last_activity_at' => 'datetime'];

    /** Matching key, recomputed with the phone. */
    protected array $activityIgnore = ['phone_digits', 'last_activity_at'];

    protected static function booted(): void
    {
        static::saving(function (Customer $customer) {
            $customer->email = $customer->email ? mb_strtolower(trim($customer->email)) : null;
            $customer->phone_digits = self::phoneDigits($customer->phone ?: $customer->whatsapp);
        });
    }

    /** Last 10 digits, so "+1 (416) 555-0101" and "416-555-0101" match. */
    public static function phoneDigits(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 7 ? substr($digits, -10) : null;
    }

    public function bookings() { return $this->hasMany(Booking::class)->latest('id'); }
    public function visaEnquiries() { return $this->hasMany(VisaEnquiry::class)->latest('id'); }
    public function status() { return $this->belongsTo(CustomerStatus::class, 'customer_status_id'); }
    public function tags() { return $this->belongsToMany(CustomerTag::class)->orderBy('name'); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }

    public function getFullNameAttribute(): string { return trim("{$this->first_name} {$this->last_name}"); }
    public function getDisplayNameAttribute(): string { return $this->preferred_name ? "{$this->preferred_name} ({$this->full_name})" : $this->full_name; }
    public function activityLabel(): string { return $this->full_name ?: 'Customer #'.$this->id; }

    /** Promotional messages only with consent, and never while the status says Do Not Contact. */
    public function isMarketingEligible(): bool
    {
        return $this->marketing_opt_in && !$this->status?->blocks_marketing;
    }

    public function scopeMarketingEligible(Builder $query): Builder
    {
        return $query->where('marketing_opt_in', true)->whereDoesntHave('status', fn ($q) => $q->where('blocks_marketing', true));
    }

    /**
     * Adds trips_count, total_paid, outstanding and last_trip_at. Trips are bookings
     * on a departure that are not cancelled; requests without a departure don't count.
     */
    public function scopeWithStats(Builder $query): Builder
    {
        $trips = fn ($select) => Booking::query()->selectRaw($select)->whereColumn('bookings.customer_id', 'customers.id')->whereNotNull('bookings.tour_departure_id')->where('bookings.status', '!=', 'cancelled');

        return $query->addSelect([
            'trips_count' => $trips('count(*)'),
            'total_paid' => $trips('coalesce(sum(amount_paid), 0)'),
            'outstanding' => $trips('coalesce(sum(greatest(total_amount - amount_paid, 0)), 0)'),
            'last_trip_at' => $trips('max(tour_departures.departs_at)')->join('tour_departures', 'tour_departures.id', '=', 'bookings.tour_departure_id')->where('tour_departures.departs_at', '<', DB::raw('now()')),
        ]);
    }
}
