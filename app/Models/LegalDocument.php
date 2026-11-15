<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One central legal text (privacy, terms, refund, waiver). Editing the title or text
 * saves a new version; bookings keep the versions their customers accepted.
 */
class LegalDocument extends Model
{
    protected $fillable = ['title', 'heading', 'body', 'is_published', 'show_on_tours', 'require_acceptance'];

    protected $casts = ['is_published' => 'boolean', 'show_on_tours' => 'boolean', 'require_acceptance' => 'boolean'];

    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentVersion::class)->orderByDesc('version');
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(LegalDocumentVersion::class)->latestOfMany('version');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getUrlAttribute(): string
    {
        return '/'.$this->slug;
    }

    /** Published documents keyed by key ('privacy', 'terms', 'refund', 'waiver'). */
    public static function publishedByKey(): Collection
    {
        return static::published()->with('currentVersion')->orderBy('sort_order')->get()->keyBy('key');
    }

    /** Store a new version when the wording changed; settings-only saves keep the version. */
    public function saveWithVersion(array $attributes, ?int $userId): bool
    {
        $this->fill($attributes);
        $wordingChanged = $this->isDirty(['title', 'heading', 'body']);
        $this->save();

        if ($wordingChanged || !$this->versions()->exists()) {
            $this->versions()->create([
                'version' => (int) $this->versions()->max('version') + 1,
                'title' => $this->title,
                'heading' => $this->heading,
                'body' => $this->body,
                'created_by' => $userId,
            ]);
        }

        return $wordingChanged;
    }
}
