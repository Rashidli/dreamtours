<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** DreamTour Website Developer Change Request (11 Nov 2026). */
class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    private function bookableTour(array $tour = []): array
    {
        $model = Tour::create($tour + ['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Day Trip', 'slug' => 'niagara-day-trip']]);
        $departure = TourDeparture::create(['tour_id' => $model->id, 'departs_at' => now()->addMonth(), 'price' => 105, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);

        return [$model, $departure];
    }

    // 01 — the "Invoice Request" button is gone from the public booking panel.
    public function test_tour_page_has_no_invoice_request_button(): void
    {
        $this->bookableTour();

        $this->get('/tours/niagara-day-trip')
            ->assertOk()
            ->assertSee('Book Now')
            ->assertDontSee('Invoice Request')
            ->assertDontSee("show('invoice')", false);
    }

    // 02 — each fare row is labelled by its own code and the tour's age limits.
    public function test_departure_pricing_labels_infant_child_and_adult_rows(): void
    {
        [, $departure] = $this->bookableTour(['infant_max_age' => 1, 'child_max_age' => 11]);
        foreach ([['adult', 105], ['child', 95], ['infant', 0]] as $i => [$code, $price]) {
            $departure->prices()->create(['code' => $code, 'price' => $price, 'sort_order' => $i]);
        }

        $this->get('/tours/niagara-day-trip')
            ->assertOk()
            ->assertSeeInOrder(['Per <b class="text-ink-900">adult (12+)</b>', 'CA$105', 'Per <b class="text-ink-900">child (2–11)</b>', 'CA$95', 'Per <b class="text-ink-900">infant (0–1)</b>', 'CA$0'], false);
    }

    // 03 — "Show in homepage carousel" is its own switch, saved and editable later.
    public function test_admin_toggles_homepage_carousel_independently_of_show_on_home(): void
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));
        $payload = ['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'show_on_home' => 1, 'show_in_carousel' => 1, 'en' => ['name' => 'Carousel Tour', 'slug' => 'carousel-tour']];

        $this->post(route('tours.store'), $payload)->assertRedirect();
        $tour = Tour::first();
        $this->assertTrue($tour->show_in_carousel);
        $this->assertTrue($tour->show_on_home);

        $this->put(route('tours.update', $tour), array_diff_key($payload, ['show_in_carousel' => 1]))->assertRedirect();
        $this->assertFalse($tour->fresh()->show_in_carousel);
        $this->assertTrue($tour->fresh()->show_on_home);
    }

    // 03 — flagged published tours lead the hero carousel with their cover image and link.
    public function test_homepage_carousel_shows_flagged_published_tours_first(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$flagged] = $this->bookableTour(['main_image' => 'tours/flagged.jpg', 'show_in_carousel' => true]);
        Tour::create(['main_image' => 'tours/off.jpg', 'duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'show_on_home' => true, 'en' => ['name' => 'Not In Carousel', 'slug' => 'not-in-carousel']]);
        Tour::create(['main_image' => 'tours/draft.jpg', 'show_in_carousel' => true, 'duration_days' => 1, 'duration_nights' => 0, 'status' => 'draft', 'en' => ['name' => 'Draft Carousel Tour', 'slug' => 'draft-carousel-tour']]);

        $this->get('/')
            ->assertOk()
            ->assertSee('aria-label="1 of', false)
            ->assertSee(': Niagara Day Trip"', false)
            ->assertSee($flagged->main_image, false)
            ->assertSee('href="/tours/niagara-day-trip" class="inline-flex', false)
            ->assertDontSee(': Not In Carousel"', false)
            ->assertDontSee(': Draft Carousel Tour"', false);
        $this->assertMatchesRegularExpression('/aria-label="1 of \d+: Niagara Day Trip"/', $this->get('/')->getContent());
    }
}
