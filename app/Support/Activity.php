<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Writes the activity history. Works out who acted (signed-in staff, a website
 * visitor, or the system for console and queue work) and where from, so callers
 * only say what happened. Recording never breaks the action being recorded.
 */
class Activity
{
    /** Longest value kept per field; long descriptions are cut, not dropped. */
    private const MAX_VALUE = 2000;

    private static ?string $reason = null;

    /**
     * Record one event. $changes is {field: [old, new]} for edits, or any small
     * array of details for other events (e.g. the recipient of an email).
     */
    public static function record(string $action, ?Model $subject = null, array $changes = [], ?string $reason = null, ?string $label = null): void
    {
        try {
            $user = auth()->user();
            // Only real HTTP requests reach a route; queue workers, artisan and scheduled tasks act as the system.
            $console = !app()->bound('activity.http');
            ActivityLog::create([
                'actor_type' => $user ? 'user' : ($console ? 'system' : 'visitor'),
                'actor_id' => $user?->id,
                'actor_name' => $user?->name ?? ($console ? 'System' : 'Website visitor'),
                'actor_role' => $user?->getRoleNames()->implode(', ') ?: null,
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => str($label ?? self::label($subject))->limit(250)->toString() ?: null,
                'changes' => $changes ? self::trim($changes) : null,
                'reason' => $reason ?? self::$reason,
                'origin' => self::origin($console),
                'ip' => $console ? null : request()->ip(),
                'user_agent' => $console ? null : str((string) request()->userAgent())->limit(250)->toString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Activity could not be recorded', ['action' => $action, 'subject' => $subject ? $subject::class.'#'.$subject->getKey() : null, 'error' => $e->getMessage()]);
        }
    }

    /** Attach a reason (e.g. "Customer asked to move dates") to everything recorded inside $callback. */
    public static function because(?string $reason, callable $callback): mixed
    {
        [$previous, self::$reason] = [self::$reason, $reason ?: self::$reason];
        try {
            return $callback();
        } finally {
            self::$reason = $previous;
        }
    }

    /** A readable name for a record: its reference, name or title, else "Type #id". */
    public static function label(?Model $subject): ?string
    {
        if (!$subject) {
            return null;
        }
        if (method_exists($subject, 'activityLabel')) {
            return $subject->activityLabel();
        }
        foreach (['reference', 'name', 'title', 'key', 'full_name'] as $attribute) {
            try {
                if (filled($value = $subject->getAttribute($attribute))) {
                    return (string) $value;
                }
            } catch (\Throwable) {
                // Translatable models without a translation loaded can throw; fall through.
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    private static function origin(bool $console): string
    {
        if ($console) {
            return in_array('queue:work', $_SERVER['argv'] ?? [], true) ? 'queue' : 'console';
        }

        return request()->is('admin', 'admin/*') ? 'admin' : 'website';
    }

    private static function trim(array $changes): array
    {
        array_walk_recursive($changes, function (&$value) {
            if (is_string($value) && mb_strlen($value) > self::MAX_VALUE) {
                $value = mb_substr($value, 0, self::MAX_VALUE).'…';
            }
        });

        return $changes;
    }
}
