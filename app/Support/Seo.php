<?php

namespace App\Support;

use App\Models\Tour;
use Illuminate\Support\Str;

/**
 * Technical SEO helpers shared by the public pages: one canonical address per page
 * (always APP_URL, never www., /index.php or tracking query strings) and the
 * schema.org JSON-LD blocks Google reads for rich results.
 */
class Seo
{
    /** The page's canonical URL: APP_URL + path, without the query string. */
    public static function canonical(): string
    {
        $path = trim(request()->path(), '/');

        return rtrim((string) config('app.url'), '/').($path === '' ? '/' : '/'.$path);
    }

    /** Absolute URL for a stored image path or a pasted URL. */
    public static function absolute(?string $value): ?string
    {
        $url = app(\App\Services\ImageUploadService::class)->url($value);
        if ($url === null) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }

    /** The company itself: shown on every page so Google can tie the pages to one brand. */
    public static function organization(): array
    {
        $site = (string) setting('site_name', 'DreamTour');
        $home = rtrim((string) config('app.url'), '/').'/';

        return array_filter([
            '@type' => 'TravelAgency',
            '@id' => $home.'#organization',
            'name' => $site,
            'url' => $home,
            'logo' => self::absolute(setting('logo')),
            'image' => SocialImage::brand(),
            'description' => setting('footer_description') ? Str::limit(strip_tags((string) setting('footer_description')), 300) : null,
            'telephone' => setting('phone_link') ?: setting('phone'),
            'email' => setting('email'),
            'address' => setting('address') ? strip_tags((string) setting('address')) : null,
            'areaServed' => 'Ontario, Canada',
        ]);
    }

    public static function website(): array
    {
        $home = rtrim((string) config('app.url'), '/').'/';

        return [
            '@type' => 'WebSite',
            '@id' => $home.'#website',
            'name' => (string) setting('site_name', 'DreamTour'),
            'url' => $home,
            'publisher' => ['@id' => $home.'#organization'],
        ];
    }

    /** @param array<int, array{0: string, 1: string}> $crumbs [name, absolute url] */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ])->all(),
        ];
    }

    /** A tour page: the trip, its upcoming departures as offers and real review stats. */
    public static function tour(Tour $tour, iterable $photos, ?float $rating, int $reviewCount): array
    {
        $home = rtrim((string) config('app.url'), '/').'/';
        $offers = collect($tour->departures)
            ->filter(fn ($d) => $d->departs_at?->isFuture() && $d->from_price > 0)
            ->take(20)
            ->map(fn ($d) => [
                '@type' => 'Offer',
                'price' => number_format((float) $d->from_price, 2, '.', ''),
                'priceCurrency' => $d->currency ?: 'CAD',
                'availability' => $d->is_bookable ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'validFrom' => $d->created_at?->toAtomString(),
                'url' => self::canonical(),
                'availabilityStarts' => $d->departs_at->toAtomString(),
            ])
            ->map(fn ($offer) => array_filter($offer))
            ->values();

        $trip = array_filter([
            '@type' => ['Product', 'TouristTrip'],
            'name' => $tour->name,
            'description' => Str::limit(strip_tags((string) ($tour->short_description ?: $tour->description)), 500),
            'url' => self::canonical(),
            'image' => collect($photos)->take(5)->map(fn ($p) => self::absolute($p))->filter()->values()->all() ?: null,
            'brand' => ['@id' => $home.'#organization'],
            'provider' => ['@id' => $home.'#organization'],
            'touristType' => $tour->categories->pluck('name')->filter()->values()->all() ?: null,
            'offers' => $offers->isEmpty() ? null : (count($offers) === 1 ? $offers->first() : [
                '@type' => 'AggregateOffer',
                'priceCurrency' => $offers->first()['priceCurrency'],
                'lowPrice' => $offers->min(fn ($o) => (float) $o['price']),
                'highPrice' => $offers->max(fn ($o) => (float) $o['price']),
                'offerCount' => $offers->count(),
                'offers' => $offers->all(),
            ]),
            'aggregateRating' => $rating && $reviewCount > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
            ] : null,
        ]);

        return $trip;
    }

    /** @param iterable<array{q: string, a: string}> $items */
    public static function faq(iterable $items): ?array
    {
        $questions = collect($items)
            ->filter(fn ($f) => filled($f['q'] ?? null) && filled($f['a'] ?? null))
            ->map(fn ($f) => [
                '@type' => 'Question',
                'name' => strip_tags((string) $f['q']),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $f['a'])],
            ])->values();

        return $questions->isEmpty() ? null : ['@type' => 'FAQPage', 'mainEntity' => $questions->all()];
    }

    /** One <script type="application/ld+json"> holding every block as a @graph. */
    public static function jsonLd(array $blocks): string
    {
        $graph = array_values(array_filter($blocks));
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}
