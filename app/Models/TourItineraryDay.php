<?php
namespace App\Models;
use Astrotomic\Translatable\Contracts\Translatable as Contract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
class TourItineraryDay extends Model implements Contract { use Translatable; public array $translatedAttributes=['title','description']; protected $fillable=['tour_id','day_number','sort_order']; public function tour(){return $this->belongsTo(Tour::class);} public function items(){return $this->hasMany(TourItineraryItem::class)->orderBy('sort_order');} }
