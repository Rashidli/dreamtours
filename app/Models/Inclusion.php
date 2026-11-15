<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as Contract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A shared "included" / "not included" line, ticked on every tour it applies to. */
class Inclusion extends Model implements Contract
{
    use \App\Models\Concerns\RecordsActivity;

    use Translatable;

    public const TYPES = ['included' => 'Included', 'excluded' => 'Not included'];

    public array $translatedAttributes = ['title', 'description'];

    protected $fillable = ['type', 'icon', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tour_inclusion');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
