<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Technical SEO: sitemap, robots, canonical URLs and structured data. */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function tour(): Tour
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Day Trip', 'slug' => 'niagara-day-trip', 'short_description' => 'Falls, boat and wine.']]);
        TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addMonth(), 'price' => 105, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'draft', 'en' => ['name' => 'Secret Draft', 'slug' => 'secret-draft']]);

        return $tour;
    }

    public function test_sitemap_lists_public_pages_and_published_tours_only(): void
    {
        $this->tour();
        $base = rtrim(config('app.url'), '/');

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locs = collect($xml->url)->map(fn ($u) => (string) $u->loc);

        $this->assertContains($base.'/', $locs);
        $this->assertContains($base.'/one-day-tours', $locs);
        $this->assertContains($base.'/tours/niagara-day-trip', $locs);
        $this->assertNotContains($base.'/tours/secret-draft', $locs);
        $this->assertNotContains($base.'/favorites', $locs);
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /', false)->assertDontSee('Sitemap:', false);
    }

    public function test_robots_points_to_the_sitemap_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml', false)
            ->assertSee('Disallow: /admin', false);
    }

    public function test_tour_page_has_canonical_and_trip_schema(): void
    {
        $this->tour();
        $canonical = rtrim(config('app.url'), '/').'/tours/niagara-day-trip';

        $html = $this->get('/tours/niagara-day-trip?utm_source=facebook')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.$canonical.'">', $html);
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $graph = collect(json_decode($m[1] ?? '{}', true)['@graph'] ?? []);
        $trip = $graph->first(fn ($b) => in_array('TouristTrip', (array) ($b['@type'] ?? []), true));
        $this->assertNotNull($trip);
        $this->assertSame('Niagara Day Trip', $trip['name']);
        $this->assertSame('105.00', $trip['offers']['price']);
        $this->assertSame('CAD', $trip['offers']['priceCurrency']);
        $this->assertNotNull($graph->firstWhere('@type', 'TravelAgency'));
        $this->assertNotNull($graph->firstWhere('@type', 'BreadcrumbList'));
    }

    public function test_home_page_has_canonical_without_query_string(): void
    {
        $html = $this->get('/?utm_campaign=x')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.rtrim(config('app.url'), '/').'/">', $html);
        $this->assertStringNotContainsString('"galleryAlbums"', $html);
    }
}
