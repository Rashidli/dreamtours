<?php

use App\Models\Word;
use Illuminate\Support\Facades\Cache;

if (!function_exists('word')) {
    function word(string $key, ?string $default = null): string
    {
        $locale = app()->getLocale();
        $cacheKey = 'words_' . $locale;

        $words = Cache::remember($cacheKey, 3600, function () {
            return Word::all()->mapWithKeys(function ($word) {
                return [$word->key => $word->title];
            })->toArray();
        });

        return $words[$key] ?? $default ?? $key;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }
}
