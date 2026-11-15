<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A pipeline stage. `key` marks the stages automations move leads to (new,
 * assigned, contacted, awaiting_deposit, confirmed, lost, spam); `kind` says
 * whether a lead in it is still open, won, lost or spam.
 */
class LeadStage extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    public const KINDS = ['open' => 'Open', 'won' => 'Won', 'lost' => 'Lost', 'spam' => 'Spam'];

    /** The admin theme's colours (--bs-*), for stage dots on the board. */
    public const HEX = ['primary' => '#0f9cf3', 'secondary' => '#6c757d', 'success' => '#6fd088', 'danger' => '#f32f53', 'warning' => '#ffbb44', 'info' => '#0097a7', 'dark' => '#0a1832'];

    protected $fillable = ['key', 'name', 'color', 'kind', 'sort_order', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function leads() { return $this->hasMany(Lead::class); }

    public function scopeActive(Builder $query): Builder { return $query->whereNull('archived_at')->orderBy('sort_order')->orderBy('id'); }

    public static function byKey(string $key): self
    {
        return self::where('key', $key)->firstOrFail();
    }

    public function isClosed(): bool { return $this->kind !== 'open'; }
}
