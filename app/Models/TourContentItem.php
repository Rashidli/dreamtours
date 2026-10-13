<?php
namespace App\Models;use Astrotomic\Translatable\Contracts\Translatable as Contract;use Astrotomic\Translatable\Translatable;use Illuminate\Database\Eloquent\Model;
class TourContentItem extends Model implements Contract{use Translatable;public array $translatedAttributes=['title','description'];protected $fillable=['tour_id','type','icon','sort_order'];
 /** Icons offered in the admin for included / excluded lines. */
 public const ICONS=['🚌'=>'Transportation','🏨'=>'Accommodation','☕'=>'Breakfast','🍞'=>'Breakfast bun','🥪'=>'Sandwich / lunch box','🍽️'=>'Meal','💧'=>'Bottled water','🍿'=>'Snacks','🎟️'=>'Attraction ticket','🚢'=>'Cruise ticket','🚆'=>'Train ticket','🧑‍✈️'=>'Tour guide','🅿️'=>'Parking','🧾'=>'Taxes & fees','🛡️'=>'Insurance','💵'=>'Tips','🛍️'=>'Personal expenses','🍷'=>'Drinks','🛂'=>'Visa / passport','✈️'=>'Flights','📷'=>'Photo stop','ℹ️'=>'Information'];public function tour(){return $this->belongsTo(Tour::class);}}
