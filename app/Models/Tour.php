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

    public array $translatedAttributes = ['name', 'slug', 'short_description', 'description', 'cancellation_policy', 'refund_policy', 'boarding_rules', 'baggage_rules', 'important_notes'];
    protected $fillable = ['destination_id', 'main_image', 'duration_days', 'duration_nights', 'age_restriction', 'activity_level', 'is_private', 'is_cruise', 'show_on_home', 'badge', 'status', 'sort_order'];
    protected $casts = ['is_private' => 'boolean', 'is_cruise' => 'boolean', 'show_on_home' => 'boolean'];

    public function destination() { return $this->belongsTo(Destination::class); }
    public function categories() { return $this->belongsToMany(TourCategory::class, 'tour_category'); }
    public function transportations() { return $this->belongsToMany(Transportation::class, 'tour_transportation'); }
    public function pickupPoints() { return $this->belongsToMany(PickupPoint::class, 'tour_pickup_point')->withPivot('sort_order')->orderByPivot('sort_order'); }
    public function departures() { return $this->hasMany(TourDeparture::class); }
    public function itineraryDays() { return $this->hasMany(TourItineraryDay::class)->orderBy('day_number'); }
    public function contentItems() { return $this->hasMany(TourContentItem::class)->orderBy('sort_order'); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function cruiseDetail() { return $this->hasOne(CruiseDetail::class); }
    public function galleryAlbums() { return $this->hasMany(GalleryAlbum::class); }

    public function getMainImageAttribute(?string $value): ?string { return $this->mediaUrl($value); }
}
