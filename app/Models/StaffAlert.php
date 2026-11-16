<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** An in-app alert for staff (CRM §15). Done for everyone once someone, or the system, completes it. */
class StaffAlert extends Model
{
    protected $fillable = ['type', 'dedupe_key', 'title', 'body', 'url', 'subject_type', 'subject_id', 'permission', 'assigned_user_id'];

    protected $casts = ['completed_at' => 'datetime'];

    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }

    public function completedBy() { return $this->belongsTo(User::class, 'completed_by'); }

    public function subject() { return $this->morphTo(); }

    public function scopeOpen(Builder $q): Builder { return $q->whereNull('completed_at'); }

    /** Alerts this person may see: those whose permission they hold. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        $allowed = collect(\App\Support\Alerts::TYPES)->pluck(3)->unique()->filter(fn ($p) => $user->can($p))->values()->all();

        return $q->whereIn('permission', $allowed ?: ['-']);
    }
}
