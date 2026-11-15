<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One person working one departure. Access to the trip lasts exactly as long as this row. */
class DepartureStaff extends Model
{
    protected $table = 'departure_staff';

    /** role => [label, icon] */
    public const ROLES = [
        'leader' => ['Tour leader', 'ri-flag-line'],
        'driver' => ['Driver', 'ri-steering-2-line'],
        'guide' => ['Guide', 'ri-route-line'],
        'coordinator' => ['Coordinator', 'ri-customer-service-2-line'],
    ];

    protected $fillable = ['tour_departure_id', 'user_id', 'role', 'note', 'assigned_by'];

    public function departure() { return $this->belongsTo(TourDeparture::class, 'tour_departure_id'); }

    public function user() { return $this->belongsTo(User::class); }

    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }

    public function getRoleLabelAttribute(): string { return self::ROLES[$this->role][0] ?? ucfirst($this->role); }
}
