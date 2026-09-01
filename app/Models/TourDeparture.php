<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TourDeparture extends Model { protected $fillable=['tour_id','departs_at','returns_at','price','old_price','currency','capacity','reserved_seats','status']; protected $casts=['departs_at'=>'datetime','returns_at'=>'datetime','price'=>'decimal:2','old_price'=>'decimal:2']; protected $appends=['available_seats']; public function tour(){return $this->belongsTo(Tour::class);} public function getAvailableSeatsAttribute(): int{return max(0,$this->capacity-$this->reserved_seats);} }
