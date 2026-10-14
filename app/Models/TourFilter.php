<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A filter group shown on the One Day and/or Multi Day listing, e.g. "Theme". */
class TourFilter extends Model
{
    public const LISTINGS = ['both' => 'One Day and Multi Day tours', 'one-day' => 'One Day tours only', 'multi-day' => 'Multi Day tours only'];

    protected $fillable = ['name', 'listing', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function options() { return $this->hasMany(TourFilterOption::class)->orderBy('sort_order')->orderBy('id'); }
}
