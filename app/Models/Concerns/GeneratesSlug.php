<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Derives a translation row's slug from its title, so nobody types URLs by hand.
 *
 * The slug is only (re)built when it is missing or when the text it came from actually
 * changed, which keeps a published URL stable while an editor tweaks other fields.
 * Uniqueness is enforced per locale, matching the (locale, slug) indexes on these tables.
 *
 * Set $slugSource on the model when the source column is not "title".
 */
trait GeneratesSlug
{
    public static function bootGeneratesSlug(): void
    {
        static::saving(function ($translation): void {
            $translation->slug = $translation->resolveSlug();
        });
    }

    protected function slugSourceColumn(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'title';
    }

    protected function resolveSlug(): string
    {
        $column = $this->slugSourceColumn();
        $current = trim((string) $this->slug);

        if ($current !== '' && ! $this->isDirty($column)) {
            return $current;
        }

        $base = Str::slug((string) $this->{$column});

        if ($base === '') {
            $base = $current !== '' ? $current : Str::slug(class_basename($this)).'-'.uniqid();
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    protected function slugTaken(string $slug): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->where('locale', $this->locale)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }
}
