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

if (!function_exists('setting_image')) {
    /** A setting that stores an uploaded file or a pasted URL, resolved to a usable src. */
    function setting_image(string $key): ?string
    {
        return app(\App\Services\ImageUploadService::class)->url(\App\Models\Setting::get($key));
    }
}

if (!function_exists('tour_badge_class')) {
    /** Colour for a tour's badge pill; mirrors badgeClass() in the front-end bundle. */
    function tour_badge_class(?string $label): string
    {
        return [
            'New Tour' => 'bg-emerald-500 text-white',
            'Popular' => 'bg-brand-600 text-white',
            'Limited Seats' => 'bg-amber-400 text-ink-900',
            'Last Seats' => 'bg-orange-500 text-white',
            'Sold Out' => 'bg-ink-700 text-white',
            'Coming Soon' => 'bg-sky-500 text-white',
            'Special Price' => 'bg-violet-500 text-white',
        ][$label] ?? 'bg-ink-700 text-white';
    }
}

if (!function_exists('tour_seats_label')) {
    /** Availability wording for a tour card; mirrors seatsLabel() in the front-end bundle. */
    function tour_seats_label(int $seatsLeft): string
    {
        return match (true) {
            $seatsLeft === 0 => 'Sold out',
            $seatsLeft <= 5 => "Only $seatsLeft seats left",
            default => "$seatsLeft seats available",
        };
    }
}

if (!function_exists('cruise_cabins_label')) {
    /** Cabin availability wording for a cruise card; mirrors cabinsLabel() in the front-end bundle. */
    function cruise_cabins_label(int $cabinsLeft): string
    {
        return $cabinsLeft <= 10
            ? "Only $cabinsLeft cabins left"
            : "$cabinsLeft cabins available";
    }
}

if (!function_exists('money')) {
    /** "CA$1,299" / "US$45.50" — whole amounts drop the cents. */
    function money($amount, ?string $currency = 'CAD'): string
    {
        $symbol = ['CAD' => 'CA$', 'USD' => 'US$', 'EUR' => '€', 'GBP' => '£'][strtoupper((string) $currency)] ?? strtoupper((string) $currency).' ';
        $amount = (float) $amount;

        return $symbol.number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }
}

if (!function_exists('canadian_phone')) {
    /** Normalise a North American number to "+1 416-555-0100", or null when it is not one. */
    function canadian_phone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === 10 && $digits[0] >= '2'
            ? sprintf('+1 %s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6))
            : null;
    }
}
