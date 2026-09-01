<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TourItineraryItemTranslation extends Model { public $timestamps=false; protected $fillable=['location_name','description','sights','activities','notes']; }
