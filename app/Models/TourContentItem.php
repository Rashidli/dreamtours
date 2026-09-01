<?php
namespace App\Models;use Astrotomic\Translatable\Contracts\Translatable as Contract;use Astrotomic\Translatable\Translatable;use Illuminate\Database\Eloquent\Model;
class TourContentItem extends Model implements Contract{use Translatable;public array $translatedAttributes=['title','description'];protected $fillable=['tour_id','type','sort_order'];public function tour(){return $this->belongsTo(Tour::class);}}
