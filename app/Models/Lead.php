<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** A request awaiting follow-up (§3): who asked, about what, from which source, and where it stands. */
class Lead extends Model
{
    use \App\Models\Concerns\RecordsActivity, SoftDeletes;

    public const TYPES = ['booking' => 'Booking request', 'tour' => 'Tour question', 'contact' => 'Contact message', 'callback' => 'Call-back', 'private_tour' => 'Private tour', 'cruise' => 'Cruise', 'invoice' => 'Invoice', 'visa' => 'Visa support', 'manual' => 'Added by staff'];

    public const SOURCES = ['website' => 'Website', 'phone' => 'Phone call', 'walk_in' => 'Walk-in', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'google' => 'Google', 'referral' => 'Referral', 'other' => 'Other'];

    protected $fillable = ['lead_stage_id', 'customer_id', 'assigned_user_id', 'type', 'request_booking_id', 'visa_enquiry_id', 'booking_id', 'tour_id', 'tour_departure_id', 'pickup_point_id', 'guests', 'first_name', 'last_name', 'email', 'phone', 'whatsapp', 'message', 'source', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page', 'referrer', 'lost_reason', 'next_follow_up_at', 'follow_up_note', 'last_contacted_at', 'contact_attempts'];

    protected $casts = ['next_follow_up_at' => 'datetime', 'last_contacted_at' => 'datetime', 'guests' => 'integer', 'contact_attempts' => 'integer'];

    /** Stage moves are recorded with names by App\Support\Leads; counters change with every note. */
    protected array $activityIgnore = ['lead_stage_id', 'contact_attempts', 'last_contacted_at'];

    protected static function booted(): void
    {
        static::creating(fn (Lead $lead) => $lead->reference ??= 'L-'.strtoupper(Str::random(6)));
    }

    public function stage() { return $this->belongsTo(LeadStage::class, 'lead_stage_id'); }
    public function customer() { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function booking() { return $this->belongsTo(Booking::class); }
    public function requestBooking() { return $this->belongsTo(Booking::class, 'request_booking_id'); }
    public function visaEnquiry() { return $this->belongsTo(VisaEnquiry::class); }
    public function tour() { return $this->belongsTo(Tour::class)->withTrashed(); }
    public function departure() { return $this->belongsTo(TourDeparture::class, 'tour_departure_id'); }
    public function pickupPoint() { return $this->belongsTo(PickupPoint::class)->withTrashed(); }
    public function notes() { return $this->hasMany(LeadNote::class)->latest('id'); }
    public function tags() { return $this->belongsToMany(CustomerTag::class, 'lead_tag')->orderBy('name'); }

    /** "Sam Sales" → "SS", for the assignee avatar on board cards. */
    public static function initials(?string $name): ?string
    {
        return $name ? collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') : null;
    }

    public function getFullNameAttribute(): string { return trim("{$this->first_name} {$this->last_name}"); }
    public function activityLabel(): string { return "{$this->reference} · {$this->full_name}"; }

    public function isFollowUpDue(): bool { return $this->next_follow_up_at && $this->next_follow_up_at->isPast() && !$this->stage?->isClosed(); }

    /** Where it came from, in one line: "Facebook / cpc · Halloween 2026". */
    public function sourceLabel(): string
    {
        $utm = collect([$this->utm_source, $this->utm_medium])->filter()->implode(' / ');

        return collect([self::SOURCES[$this->source] ?? ucfirst((string) $this->source), $utm ?: null, $this->utm_campaign])->filter()->unique()->implode(' · ');
    }

    public function scopeOpen(Builder $query): Builder { return $query->whereHas('stage', fn ($q) => $q->where('kind', 'open')); }

    public function scopeFollowUpDue(Builder $query): Builder { return $query->open()->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()); }
}
