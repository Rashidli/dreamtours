<?php

namespace App\Models\Concerns;

use App\Services\ImageUploadService;

/**
 * Media columns store either an external URL or the name of a file on the public
 * disk. Accessors on the models run the stored value through this resolver so the
 * frontend always receives something it can use directly as an `src`.
 */
trait ResolvesMediaUrls
{
    protected function mediaUrl(?string $value): ?string
    {
        return app(ImageUploadService::class)->url($value);
    }

    /**
     * @param  iterable<int, string|null>|null  $values
     * @return array<int, string>
     */
    protected function mediaUrls(?iterable $values): array
    {
        return collect($values ?? [])
            ->map(fn ($value) => $this->mediaUrl($value))
            ->filter()
            ->values()
            ->all();
    }
}
