<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One attendance mark or undo, with who made it and when (CRM §11). Never edited. */
class PassengerAttendanceEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['booking_passenger_id', 'tour_departure_id', 'status', 'previous_status', 'note', 'user_id'];

    public function passenger() { return $this->belongsTo(BookingPassenger::class, 'booking_passenger_id'); }

    public function user() { return $this->belongsTo(User::class); }
}
