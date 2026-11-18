<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourSlugRedirect;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Admin can set a tour's URL slug, SEO title and SEO description; old URLs keep working. */
class TourSeoAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        $this->actingAs($user);
    }

    private function payload(array $en = []): array
    {
        return ['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published',
            'en' => array_merge(['name' => 'Niagara Falls Day Trip', 'short_description' => 'Falls and boat ride.'], $en)];
    }

    private function tour(): Tour
    {
        return Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls Day Trip', 'short_description' => 'Falls and boat ride.']]);
    }

    public function test_add_and_edit_pages_show_every_section(): void
    {
        $this->admin();

        $create = $this->get(route('tours.create'))->assertOk();
        foreach (['id="basics"', 'id="seo-card"', 'id="placement"', 'id="details"', 'id="inclusions"', 'id="fares"', 'id="policies"', 'id="booking-form"', 'id="departures"', 'Create tour'] as $needle) {
            $create->assertSee($needle, false);
        }
        $create->assertDontSee('id="itinerary"', false);

        $tour = $this->tour();
        $edit = $this->get(route('tours.edit', $tour))->assertOk()->assertSee('Save changes')->assertSee('id="itinerary"', false)->assertSee('id="additional"', false);
        $edit->assertSee('href="'.route('front.tours.show', 'niagara-falls-day-trip').'"', false);
    }

    public function test_edit_form_shows_the_seo_fields(): void
    {
        $this->admin();
        $tour = $this->tour();

        $this->get(route('tours.edit', $tour))->assertOk()
            ->assertSee('name="en[slug]"', false)
            ->assertSee('name="en[meta_title]"', false)
            ->assertSee('name="en[meta_description]"', false)
            ->assertSee('value="niagara-falls-day-trip"', false);
    }

    public function test_slug_and_seo_texts_are_saved_and_used_on_the_page(): void
    {
        $this->admin();
        $tour = $this->tour();

        $this->put(route('tours.update', $tour), $this->payload([
            'slug' => '  Niagara Falls Tour From Toronto ',
            'meta_title' => 'Niagara Falls Tour from Toronto — Boat Cruise Included',
            'meta_description' => 'A full day at Niagara Falls with the boat cruise, wine tasting and pick-up across the GTA.',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('niagara-falls-tour-from-toronto', $tour->fresh()->translate('en')->slug);

        $this->get('/tours/niagara-falls-tour-from-toronto')->assertOk()
            ->assertSee('<title>Niagara Falls Tour from Toronto — Boat Cruise Included</title>', false)
            ->assertSee('<meta name="description" content="A full day at Niagara Falls with the boat cruise, wine tasting and pick-up across the GTA.">', false);
    }

    public function test_old_slug_redirects_permanently_to_the_new_one(): void
    {
        $this->admin();
        $tour = $this->tour();

        $this->put(route('tours.update', $tour), $this->payload(['slug' => 'niagara-tour']))->assertSessionHasNoErrors();
        $this->put(route('tours.update', $tour), $this->payload(['slug' => 'niagara-falls-tour']))->assertSessionHasNoErrors();

        $this->get('/tours/niagara-falls-day-trip?utm_source=fb')->assertStatus(301)->assertRedirect(route('front.tours.show', 'niagara-falls-tour').'?utm_source=fb');
        $this->get('/tours/niagara-tour')->assertStatus(301)->assertRedirect(route('front.tours.show', 'niagara-falls-tour'));
        $this->get('/tours/niagara-falls-tour')->assertOk();

        // Going back to an old slug makes it live again instead of a redirect loop.
        $this->put(route('tours.update', $tour), $this->payload(['slug' => 'niagara-tour']))->assertSessionHasNoErrors();
        $this->get('/tours/niagara-tour')->assertOk();
        $this->assertFalse(TourSlugRedirect::where('slug', 'niagara-tour')->exists());
        $this->get('/tours/niagara-falls-tour')->assertStatus(301);
    }

    public function test_renaming_a_tour_keeps_its_url(): void
    {
        $this->admin();
        $tour = $this->tour();

        $this->put(route('tours.update', $tour), $this->payload(['name' => 'Niagara Falls Winter Trip', 'slug' => 'niagara-falls-day-trip']))->assertSessionHasNoErrors();

        $this->assertSame('niagara-falls-day-trip', $tour->fresh()->translate('en')->slug);
        $this->assertSame('Niagara Falls Winter Trip', $tour->fresh()->translate('en')->name);
    }

    public function test_a_slug_used_by_another_tour_is_rejected(): void
    {
        $this->admin();
        $tour = $this->tour();
        Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Ottawa']]);

        $this->put(route('tours.update', $tour), $this->payload(['slug' => 'ottawa']))->assertSessionHasErrors('en.slug');
        $this->assertSame('niagara-falls-day-trip', $tour->fresh()->translate('en')->slug);
    }

    public function test_new_tour_without_a_slug_gets_one_from_its_name(): void
    {
        $this->admin();

        $this->post(route('tours.store'), $this->payload(['name' => 'Algonquin Fall Colours', 'slug' => '']))->assertSessionHasNoErrors();

        $this->assertSame('algonquin-fall-colours', Tour::first()->translate('en')->slug);
    }
}
