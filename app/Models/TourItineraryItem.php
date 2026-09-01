<?php
namespace App\Models;
use Astrotomic\Translatable\Contracts\Translatable as Contract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
class TourItineraryItem extends Model implements Contract { use Translatable; public array $translatedAttributes=['location_name','description','sights','activities','notes']; protected $fillable=['tour_itinerary_day_id','arrival_time','departure_time','duration_minutes','address','map_url','video_url','images','entrance_included','sort_order']; protected $casts=['images'=>'array','entrance_included'=>'boolean']; public function day(){return $this->belongsTo(TourItineraryDay::class,'tour_itinerary_day_id');} }
