<?php

namespace App\Models\Concerns;

use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Records creation, edits, deletion and restoring of a model in the activity
 * history, with old and new values for every changed field. Translation models
 * (e.g. TourTranslation) record against their parent, as "name (en)".
 *
 * Models may list fields to leave out in $activityIgnore (derived counters).
 * Saves made with saveQuietly() are not recorded.
 */
trait RecordsActivity
{
    /** Never stored in the history. */
    private static array $activitySecret = ['password', 'remember_token', 'submission_token'];

    public static function bootRecordsActivity(): void
    {
        static::created(fn (Model $model) => $model->recordActivity('created', $model->activityValues(fn ($value) => [null, $value])));
        static::updated(function (Model $model) {
            $changes = $model->activityValues(fn ($value, $field) => [$model->getRawOriginal($field), $value], array_keys($model->getChanges()));
            if ($changes) {
                $model->recordActivity('updated', $changes);
            }
        });
        static::deleted(fn (Model $model) => $model->recordActivity(
            in_array(SoftDeletes::class, class_uses_recursive($model), true) && !$model->isForceDeleting() ? 'archived' : 'deleted',
            $model->activityValues(fn ($value) => [$value, null]),
        ));
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn (Model $model) => $model->recordActivity('restored'));
        }
    }

    /** {field: [old, new]} for the given (or all) attributes, minus timestamps, secrets and ignored fields. */
    protected function activityValues(callable $pair, ?array $fields = null): array
    {
        $skip = array_merge(['id', 'created_at', 'updated_at', 'deleted_at'], self::$activitySecret, $this->activityIgnore ?? []);
        $values = [];
        foreach ($fields ?? array_keys($this->getAttributes()) as $field) {
            if (in_array($field, $skip, true) || ($this->isTranslationModel() && in_array($field, [$this->parentKeyName(), 'locale'], true))) {
                continue;
            }
            $values[$this->isTranslationModel() ? "{$field} ({$this->getAttribute('locale')})" : $field] = $pair($this->getAttributes()[$field] ?? null, $field);
        }

        return array_filter($values, fn ($pair) => $pair[0] != $pair[1]);
    }

    protected function recordActivity(string $action, array $changes = []): void
    {
        if ($this->isTranslationModel()) {
            $parentClass = substr(static::class, 0, -strlen('Translation'));
            $parent = class_exists($parentClass) ? $parentClass::find($this->getAttribute($this->parentKeyName())) : null;
            // A new or removed translation is an edit of the parent record.
            Activity::record('updated', $parent ?? $this, $changes);

            return;
        }
        Activity::record($action, $this, $changes);
    }

    private function isTranslationModel(): bool
    {
        return str_ends_with(static::class, 'Translation');
    }

    private function parentKeyName(): string
    {
        return str(class_basename(substr(static::class, 0, -strlen('Translation'))))->snake()->append('_id')->toString();
    }
}
