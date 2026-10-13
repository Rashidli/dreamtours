<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a departure's price list. Room codes are a per-person price for sharing a
 * room of that size, so a double room costs 2 × price.
 */
class TourDeparturePrice extends Model
{
    /** code => [label, occupancy (null for a per-person price)] */
    public const CODES = [
        'adult' => ['Adult', null],
        'child' => ['Child (under 12)', null],
        'senior' => ['Senior (65+)', null],
        'single' => ['Single room', 1],
        'double' => ['Double / Twin room', 2],
        'triple' => ['Triple room', 3],
        'quad' => ['Quad room', 4],
    ];

    protected $fillable = ['tour_departure_id', 'code', 'price', 'sort_order'];
    protected $casts = ['price' => 'decimal:2'];

    public function departure() { return $this->belongsTo(TourDeparture::class, 'tour_departure_id'); }

    public function getLabelAttribute(): string { return self::CODES[$this->code][0] ?? ucfirst($this->code); }

    public function getOccupancyAttribute(): ?int { return self::CODES[$this->code][1] ?? null; }

    public function getIsRoomAttribute(): bool { return $this->occupancy !== null; }
}
