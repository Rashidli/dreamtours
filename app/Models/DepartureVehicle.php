<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One bus of one departure, with its own copy of a seating plan (CRM §5, §9). */
class DepartureVehicle extends Model
{
    use Concerns\RecordsActivity;

    /** Bumped on every seat change; not worth a history row of its own. */
    protected array $activityIgnore = ['seats_changed_at'];

    protected $fillable = ['tour_departure_id', 'vehicle_layout_id', 'label', 'note', 'layout', 'blocked', 'sort_order'];

    protected $casts = ['layout' => 'array', 'blocked' => 'array', 'seats_changed_at' => 'datetime', 'published_at' => 'datetime'];

    public function departure() { return $this->belongsTo(TourDeparture::class, 'tour_departure_id'); }

    public function template() { return $this->belongsTo(VehicleLayout::class, 'vehicle_layout_id')->withTrashed(); }

    public function passengers() { return $this->hasMany(BookingPassenger::class); }

    /** @return string[] */
    public function seatLabels(): array { return VehicleLayout::seatLabels($this->layout); }

    /** @return string[] */
    public function blockedSeats(): array { return array_values($this->blocked ?? []); }

    /** Seats passengers can take: every seat on the plan that is not blocked for this date. */
    public function openSeatLabels(): array { return array_values(array_diff($this->seatLabels(), $this->blockedSeats())); }

    /** Publishing clears seats_changed_at, so any seat change after it (even in the same second) makes a draft. */
    public function hasDraftChanges(): bool { return !$this->published_at || $this->seats_changed_at !== null; }

    public function activityLabel(): string { return $this->label.' — '.($this->departure?->activityLabel() ?? 'departure'); }
}
