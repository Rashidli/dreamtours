<?php

namespace App\Models;
use App\Models\Concerns\ResolvesMediaUrls;

use Astrotomic\Translatable\Contracts\Translatable as Contract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model implements Contract
{
    use Translatable, SoftDeletes, ResolvesMediaUrls;

    public array $translatedAttributes = ['name', 'slug', 'short_description', 'description', 'cancellation_policy', 'refund_policy', 'deposit_policy', 'boarding_rules', 'baggage_rules', 'important_notes', 'meal_options', 'waiver_title', 'waiver'];
    protected $fillable = ['destination_id', 'main_image', 'duration_days', 'duration_nights', 'age_restriction', 'activity_level', 'deposit_per_person', 'tax_included', 'infant_max_age', 'child_max_age', 'infant_needs_seat', 'is_private', 'is_cruise', 'show_on_home', 'show_in_carousel', 'badge', 'status', 'sort_order'];
    protected $casts = ['deposit_per_person' => 'decimal:2', 'tax_included' => 'boolean', 'infant_max_age' => 'integer', 'child_max_age' => 'integer', 'infant_needs_seat' => 'boolean', 'is_private' => 'boolean', 'is_cruise' => 'boolean', 'show_on_home' => 'boolean', 'show_in_carousel' => 'boolean'];

    public function destination() { return $this->belongsTo(Destination::class); }
    public function categories() { return $this->belongsToMany(TourCategory::class, 'tour_category'); }
    public function transportations() { return $this->belongsToMany(Transportation::class, 'tour_transportation'); }
    public function pickupPoints() { return $this->belongsToMany(PickupPoint::class, 'tour_pickup_point')->withPivot('sort_order')->orderByPivot('sort_order'); }
    public function departures() { return $this->hasMany(TourDeparture::class); }
    public function filterOptions() { return $this->belongsToMany(TourFilterOption::class, 'tour_filter_option_tour'); }

    /** Meal choices offered to each passenger, one per line in admin (e.g. "Tuna sandwich"). */
    public function mealChoices(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->meal_options))));
    }

    /** Price label with this tour's age limits, e.g. "Child (2–11)", "Infant (0–1)". */
    public function priceLabel(string $code): string
    {
        $infant = (int) ($this->infant_max_age ?? 1);
        $child = max($infant + 1, (int) ($this->child_max_age ?? 11));

        return match ($code) {
            'infant' => "Infant (0–{$infant})",
            'child' => 'Child ('.($infant + 1)."–{$child})",
            'adult' => 'Adult ('.($child + 1).'+)',
            default => TourDeparturePrice::CODES[$code][0] ?? ucfirst($code),
        };
    }

    /** Age band for a date of birth on the travel date: 'infant', 'child' or 'adult'. */
    public function ageBand(\DateTimeInterface $birth, \DateTimeInterface $travel): string
    {
        $age = \Illuminate\Support\Carbon::instance($birth)->diffInYears($travel);

        return $age <= (int) $this->infant_max_age ? 'infant' : ($age <= (int) $this->child_max_age ? 'child' : 'adult');
    }

    /** Note shown after this tour's prices: empty when taxes are included, else the "+ TAX" setting. */
    public function taxNote(): string
    {
        return $this->tax_included ? '' : (string) setting('price_tax_note');
    }

    public function hasWaiver(): bool
    {
        return trim(strip_tags((string) $this->waiver)) !== '';
    }
    public function reviews() { return $this->hasMany(Review::class); }
    public function itineraryDays() { return $this->hasMany(TourItineraryDay::class)->orderBy('day_number'); }
    public function contentItems() { return $this->hasMany(TourContentItem::class)->orderBy('sort_order'); }
    /** Shared included / not-included lines from Tours & CRM → Inclusions. */
    public function inclusions() { return $this->belongsToMany(Inclusion::class, 'tour_inclusion')->orderBy('inclusions.sort_order')->orderBy('inclusions.id'); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function cruiseDetail() { return $this->hasOne(CruiseDetail::class); }
    public function galleryAlbums() { return $this->hasMany(GalleryAlbum::class); }

    public function getMainImageAttribute(?string $value): ?string { return $this->mediaUrl($value); }
}
