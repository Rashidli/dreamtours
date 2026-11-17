<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use App\Models\Page;
use App\Models\Tour;
use Illuminate\Http\Response;

/**
 * /sitemap.xml lists every public page and published tour; /robots.txt points to it.
 * Anything other than production asks crawlers to stay away (staging is noindex too).
 */
class SitemapController extends Controller
{
    /** Public listing/info pages from routes/web.php; favorites is per-device, so it is left out. */
    private const PAGES = [
        '' => 1.0, 'one-day-tours' => 0.9, 'multi-day-tours' => 0.9, 'international-tours' => 0.9,
        'cruises' => 0.8, 'private-tours' => 0.8, 'visa-support' => 0.7, 'gallery' => 0.6,
        'about' => 0.6, 'contact' => 0.6,
    ];

    private const LEGAL = ['privacy-policy', 'terms-and-conditions', 'refund-policy', 'waiver'];

    public function sitemap(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [];

        // Pages switched off in admin are 404 for visitors, so they stay out of the sitemap.
        $pages = Page::query()->get()->keyBy('key');
        foreach (self::PAGES as $path => $priority) {
            $page = $pages->get($path === '' ? 'home' : $path);
            if ($page && isset($page->is_active) && !$page->is_active) {
                continue;
            }
            $urls[] = ['loc' => $base.'/'.$path, 'lastmod' => $page?->updated_at, 'priority' => $priority];
        }

        $legal = LegalDocument::query()->whereIn('slug', self::LEGAL)->where('is_published', true)->get();
        foreach ($legal as $document) {
            $urls[] = ['loc' => $base.'/'.$document->slug, 'lastmod' => $document->updated_at, 'priority' => 0.3];
        }

        Tour::query()->where('status', 'published')->with('translations')->orderBy('sort_order')->get()
            ->each(function (Tour $tour) use (&$urls, $base) {
                if (filled($tour->slug)) {
                    $urls[] = ['loc' => $base.'/tours/'.$tour->slug, 'lastmod' => $tour->updated_at, 'priority' => 0.8];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>'
                .($url['lastmod'] ? '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>' : '')
                .'<priority>'.number_format($url['priority'], 1).'</priority></url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function robots(): Response
    {
        if (!app()->isProduction()) {
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $body = implode("\n", [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /booking/',
                'Disallow: /tours/*/book',
                'Disallow: /docs/',
                'Disallow: /favorites',
                '',
                'Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml',
            ])."\n";
        }

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
