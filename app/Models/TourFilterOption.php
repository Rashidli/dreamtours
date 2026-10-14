<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One value of a filter group, assigned to tours (tour_filter_option_tour). */
class TourFilterOption extends Model
{
    protected $fillable = ['tour_filter_id', 'name', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function filter() { return $this->belongsTo(TourFilter::class, 'tour_filter_id'); }
    public function tours() { return $this->belongsToMany(Tour::class, 'tour_filter_option_tour'); }
}
