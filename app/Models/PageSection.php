<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrls;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageSection extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable, ResolvesMediaUrls;

    public array $translatedAttributes = ['eyebrow', 'title', 'subtitle', 'body', 'button_text'];
    protected $fillable = ['page_id', 'key', 'type', 'image', 'background_image', 'button_url', 'options', 'is_active', 'sort_order'];
    protected $casts = ['options' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getImageAttribute(?string $value): ?string
    {
        return $this->mediaUrl($value);
    }

    public function getBackgroundImageAttribute(?string $value): ?string
    {
        return $this->mediaUrl($value);
    }

    /** Extra photos some layouts use (the About and Private Tours hero collages): image_2, image_3. */
    public function extraImage(string $slot): ?string
    {
        return $this->mediaUrl($this->options['images'][$slot] ?? null);
    }

    /** Admin-entered alternative text for image, background_image, image_2 or image_3. */
    public function alt(string $slot, string $default = ''): string
    {
        return (string) ($this->options['alt'][$slot] ?? '') ?: $default;
    }
}
