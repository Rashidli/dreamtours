<?php

namespace Tests\Feature;

use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Tours → Duplicate: a Draft copy to change a few things and save as a new trip. */
class TourDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        $this->actingAs($user);

        return $user;
    }

    private function source(): Tour
    {
        $category = TourCategory::create(['type' => 'tour', 'is_active' => 1, 'en' => ['name' => 'One Day', 'slug' => 'one-day']]);
        $pickup = PickupPoint::create(['address' => 'Station', 'is_active' => 1, 'en' => ['name' => 'Square One']]);
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'show_on_home' => true, 'show_in_carousel' => true,
            'main_image' => 'cover.webp', 'deposit_per_person' => 50,
            'en' => ['name' => 'Prince Edward County Christmas', 'short_description' => 'Festive day.', 'refund_policy' => 'No refunds within 7 days.',
                'meta_title' => 'PEC Christmas tour', 'meta_description' => 'Festival of Trees and the Red Barn market.']]);
        $tour->categories()->sync([$category->id]);
        $tour->pickupPoints()->sync([$pickup->id => ['sort_order' => 1]]);

        $day = $tour->itineraryDays()->create(['day_number' => 1, 'en' => ['title' => 'Toronto – Picton – Toronto']]);
        $day->items()->create(['arrival_time' => '12:00', 'entrance_included' => true, 'en' => ['location_name' => 'Festival of Trees', 'description' => 'Decorated trees.']]);
        $tour->contentItems()->create(['type' => 'what_to_bring', 'icon' => '💧', 'en' => ['title' => 'Warm clothes']]);

        $future = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addMonth(), 'price' => 115, 'currency' => 'CAD', 'capacity' => 54, 'reserved_seats' => 20, 'held_seats' => 3, 'status' => 'available']);
        $future->prices()->create(['code' => 'child', 'price' => 105]);
        TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->subMonth(), 'price' => 110, 'currency' => 'CAD', 'capacity' => 54, 'reserved_seats' => 50, 'held_seats' => 0, 'status' => 'available']);

        return $tour;
    }

    public function test_copy_is_a_draft_with_its_own_address_and_everything_shared(): void
    {
        $this->admin();
        $source = $this->source();

        $response = $this->post(route('tours.duplicate', $source), ['name' => 'Prince Edward County Wine Tour', 'itinerary' => 1, 'content' => 1])
            ->assertSessionHasNoErrors();

        $copy = Tour::whereKeyNot($source->id)->with(['translations', 'categories', 'pickupPoints', 'itineraryDays.items', 'contentItems', 'departures'])->sole();
        $response->assertRedirect(route('tours.edit', $copy));
        $this->assertSame('draft', $copy->status);
        $this->assertFalse((bool) $copy->show_on_home);
        $this->assertFalse((bool) $copy->show_in_carousel);
        $this->assertSame('Prince Edward County Wine Tour', $copy->name);
        $this->assertSame('prince-edward-county-wine-tour', $copy->slug);
        $this->assertNull($copy->meta_title);
        $this->assertNull($copy->meta_description);
        $this->assertSame('Festive day.', $copy->short_description);
        $this->assertSame('No refunds within 7 days.', $copy->refund_policy);
        $this->assertSame('cover.webp', $copy->getRawOriginal('main_image'));
        $this->assertSame(50.0, (float) $copy->deposit_per_person);
        $this->assertSame($source->categories->modelKeys(), $copy->categories->modelKeys());
        $this->assertSame($source->pickupPoints->modelKeys(), $copy->pickupPoints->modelKeys());
        $this->assertSame('Toronto – Picton – Toronto', $copy->itineraryDays->first()->title);
        $this->assertSame('Festival of Trees', $copy->itineraryDays->first()->items->first()->location_name);
        $this->assertTrue((bool) $copy->itineraryDays->first()->items->first()->entrance_included);
        $this->assertSame('Warm clothes', $copy->contentItems->first()->title);
        $this->assertCount(0, $copy->departures, 'Dates are only copied when asked');

        // The original is untouched.
        $source->refresh();
        $this->assertSame('published', $source->status);
        $this->assertSame('prince-edward-county-christmas', $source->slug);
        $this->assertCount(1, $source->itineraryDays);
        $this->assertDatabaseHas('activity_logs', ['action' => 'duplicated', 'subject_id' => $copy->id]);
    }

    public function test_only_future_dates_are_copied_with_free_seats(): void
    {
        $this->admin();
        $source = $this->source();

        $this->post(route('tours.duplicate', $source), ['name' => 'PEC copy', 'dates' => 1])->assertSessionHasNoErrors();

        $copy = Tour::whereKeyNot($source->id)->with(['departures.prices', 'itineraryDays', 'contentItems'])->sole();
        $this->assertCount(1, $copy->departures);
        $date = $copy->departures->first();
        $this->assertTrue($date->departs_at->isFuture());
        $this->assertSame(0, $date->reserved_seats);
        $this->assertSame(0, $date->held_seats);
        $this->assertSame(54, $date->capacity);
        $this->assertSame(105.0, (float) $date->prices->first()->price);
        $this->assertCount(0, $copy->itineraryDays, 'Itinerary unticked');
        $this->assertCount(0, $copy->contentItems, 'Additional information unticked');
        $this->assertSame(2, $source->departures()->count());
    }

    public function test_copy_needs_a_name_and_the_create_permission(): void
    {
        $this->admin();
        $source = $this->source();
        $this->post(route('tours.duplicate', $source), ['name' => ''])->assertSessionHasErrors('name');

        $this->actingAs(User::factory()->create());
        $this->post(route('tours.duplicate', $source), ['name' => 'Nope'])->assertForbidden();
        $this->assertSame(1, Tour::count());
    }

    public function test_list_and_edit_page_offer_duplicate(): void
    {
        $this->admin();
        $source = $this->source();

        $this->get(route('tours.index'))->assertOk()->assertSee('data-duplicate-url="'.route('tours.duplicate', $source).'"', false)
            ->assertSee('data-duplicate-days="1"', false)->assertSee('data-duplicate-dates="1"', false)->assertSee('id="tour-duplicate"', false);
        $this->get(route('tours.edit', $source))->assertOk()->assertSee('Duplicate this tour')->assertSee('id="tour-duplicate"', false);
    }
}
