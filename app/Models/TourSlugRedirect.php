<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An old tour address (slug) that 301-redirects to the tour's current URL. */
class TourSlugRedirect extends Model
{
    protected $fillable = ['tour_id', 'locale', 'slug'];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /** A 301 to the tour's current URL when $slug is an old address and no live tour uses it. */
    public static function redirectFor(string $slug, string $route, string $locale = 'en'): ?\Illuminate\Http\RedirectResponse
    {
        if (Tour::query()->whereTranslation('slug', $slug, $locale)->exists()) {
            return null;
        }
        $tour = static::tourFor($slug, $locale);
        $current = $tour?->translate($locale)?->slug;
        if (!$current || $current === $slug) {
            return null;
        }
        $query = request()->getQueryString();

        return redirect()->to(route($route, $current).($query ? '?'.$query : ''), 301);
    }

    /** The published tour that used to live at this slug, if any. */
    public static function tourFor(string $slug, string $locale = 'en'): ?Tour
    {
        return static::query()->where('locale', $locale)->where('slug', $slug)->first()?->tour()
            ->where('status', 'published')->with('translations')->first();
    }
}
