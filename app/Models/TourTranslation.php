<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TourTranslation extends Model { public $timestamps=false; protected $fillable=['name','slug','short_description','description','cancellation_policy','refund_policy','boarding_rules','baggage_rules','important_notes']; }
