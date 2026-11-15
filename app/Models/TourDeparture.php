<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One scheduled date of a tour. Seats taken = seats held offline by staff + passengers on
 * every booking that is not cancelled; recountSeats() keeps reserved_seats in step.
 */
class TourDeparture extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    /** Kept in step by recountSeats(); not worth a history entry of its own. */
    protected array $activityIgnore = ['reserved_seats'];

    public function activityLabel(): string { return trim(($this->tour?->name ?? 'Tour').' — '.$this->departs_at?->format('M j, Y g:i A')); }

    /** status => [admin label, shown to visitors, bookable] */
    public const STATUSES = [
        'available' => ['Open for booking', true, true],
        'limited' => ['Limited seats', true, true],
        'sold_out' => ['Sold out', true, false],
        'closed' => ['Closed — registration stopped', true, false],
        'hidden' => ['Hidden from the website', false, false],
        'cancelled' => ['Cancelled', false, false],
    ];

    protected $fillable = ['tour_id', 'departs_at', 'returns_at', 'price', 'old_price', 'currency', 'capacity', 'reserved_seats', 'held_seats', 'status'];
    protected $casts = ['departs_at' => 'datetime', 'returns_at' => 'datetime', 'price' => 'decimal:2', 'old_price' => 'decimal:2', 'capacity' => 'integer', 'reserved_seats' => 'integer', 'held_seats' => 'integer'];
    protected $appends = ['available_seats'];

    public function tour() { return $this->belongsTo(Tour::class); }
    public function prices() { return $this->hasMany(TourDeparturePrice::class)->orderBy('sort_order')->orderBy('id'); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function pickupPoints() { return $this->belongsToMany(PickupPoint::class, 'tour_departure_pickup_point')->withPivot('sort_order')->orderByPivot('sort_order'); }

    public function scopeUpcoming($query) { return $query->where('departs_at', '>=', now()); }

    /** Dates a visitor may see (bookable or not), in the future. */
    public function scopeVisible($query) { return $query->upcoming()->whereIn('status', self::statusesWhere(1)); }

    /** Dates whose status allows booking; seats are checked separately. */
    public function scopeOpen($query) { return $query->upcoming()->whereIn('status', self::statusesWhere(2)); }

    public static function statusesWhere(int $flag): array
    {
        return array_keys(array_filter(self::STATUSES, fn ($s) => $s[$flag]));
    }

    /** Child and infant fares never count as the advertised "from" price. */
    public const REDUCED_FARES = ['child', 'infant'];

    /**
     * The fares customers choose from. A date priced only by its base price, or whose
     * list holds only child / infant fares, still offers an adult fare at the base price.
     */
    public function fares(): \Illuminate\Support\Collection
    {
        $fares = $this->prices->sortBy('sort_order')->values();
        if ($fares->whereNotIn('code', self::REDUCED_FARES)->isEmpty()) {
            $adult = new TourDeparturePrice(['code' => 'adult', 'price' => $this->price, 'sort_order' => -1]);
            $adult->setRelation('departure', $this);
            $fares->prepend($adult);
        }

        return $fares;
    }

    public function getFromPriceAttribute(): float
    {
        if (!$this->relationLoaded('prices') || $this->prices->isEmpty()) return (float) $this->price;
        $full = $this->prices->whereNotIn('code', self::REDUCED_FARES);

        return (float) ($full->isNotEmpty() ? $full->min('price') : $this->price);
    }
    public function getHasRoomPricingAttribute(): bool { return $this->prices->contains(fn ($p) => $p->is_room); }
    public function getAvailableSeatsAttribute(): int { return max(0, $this->capacity - $this->reserved_seats); }

    /** Status as visitors see it: an open date with no seats left is sold out. */
    public function getPublicStatusAttribute(): string
    {
        return in_array($this->status, self::statusesWhere(2), true) && $this->available_seats <= 0 ? 'sold_out' : $this->status;
    }

    public function getIsBookableAttribute(): bool
    {
        return $this->departs_at?->isFuture() && in_array($this->public_status, self::statusesWhere(2), true);
    }

    public function getStatusLabelAttribute(): string { return self::STATUSES[$this->public_status][0] ?? ucfirst($this->status); }

    /** Pickup points for this date: its own list, else the tour's, else every active point. */
    public function availablePickupPoints()
    {
        $own = $this->pickupPoints->where('is_active', true);
        if ($own->isNotEmpty()) return $own->values();
        $tour = $this->tour?->pickupPoints?->where('is_active', true) ?? collect();
        return $tour->isNotEmpty() ? $tour->values() : PickupPoint::where('is_active', true)->with('translations')->orderBy('sort_order')->get();
    }

    /** Seats taken by live bookings (a lap infant is a passenger without a seat). */
    public function bookedSeats(): int
    {
        return (int) $this->bookings()->where('status', '!=', 'cancelled')->sum('seat_count');
    }

    /** Recompute reserved seats from live bookings; call after any booking change on this date. */
    public function recountSeats(): void
    {
        $this->forceFill(['reserved_seats' => $this->held_seats + $this->bookedSeats()])->saveQuietly();
    }
}
