<?php

namespace Tests\Feature;

use App\Models\{CompanyContent, Page, Setting, Tour, TourCategory, TourDeparture};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_pages_expose_every_phase_one_filter(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['/one-day-tours', '/multi-day-tours', '/international-tours'] as $uri) {
            $this->get($uri)->assertOk()
                ->assertSee('City or destination')
                ->assertSee('Specific date')
                ->assertSee('Minimum available seats')
                ->assertSee('Age category')
                ->assertSee('Availability')
                ->assertSee('Tour format')
                ->assertSee('Transportation');
        }
    }

    public function test_home_uses_section_visual_fields_and_dynamic_contact_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $hero = Page::where('key', 'home')->firstOrFail()->sections()->where('key', 'hero')->firstOrFail();
        $hero->update(['background_image' => 'https://cdn.example.com/hero.jpg', 'image' => 'https://cdn.example.com/card.jpg']);
        Setting::set('phone', '555-0000');
        Setting::set('phone_link', '+15550000');
        Setting::set('email', 'dynamic@example.com');

        $this->get('/')->assertOk()
            ->assertSee('https://cdn.example.com/hero.jpg', false)
            ->assertSee('https://cdn.example.com/card.jpg', false)
            ->assertSee('tel:+15550000', false)
            ->assertSee('dynamic@example.com');
    }

    public function test_each_inner_page_hero_is_rendered_from_its_page_section(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['about', 'contact', 'cruises', 'gallery', 'international-tours', 'multi-day-tours', 'one-day-tours', 'private-tours', 'visa-support'] as $pageKey) {
            $hero = Page::where('key', $pageKey)->firstOrFail()->sections()->where('key', 'hero')->firstOrFail();
            $hero->update(['background_image' => 'https://cdn.example.com/'.$pageKey.'.jpg', 'en' => ['eyebrow' => 'CMS eyebrow '.$pageKey, 'title' => 'CMS title '.$pageKey, 'subtitle' => 'CMS subtitle '.$pageKey]]);
            $this->get('/'.$pageKey)->assertOk()
                ->assertSee('CMS title '.$pageKey)
                ->assertSee('CMS subtitle '.$pageKey)
                ->assertSee('https://cdn.example.com/'.$pageKey.'.jpg', false);
        }
    }

    public function test_tour_payload_contains_complete_filter_and_card_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tour = Tour::where('status', 'published')->firstOrFail();
        TourDeparture::where('tour_id', $tour->id)->update(['capacity' => 25, 'reserved_seats' => 5, 'status' => 'limited']);

        $response = $this->get('/one-day-tours')->assertOk();
        foreach (['dateValue', 'country', 'categories', 'capacity', 'availability', 'ageRestriction', 'isPrivate', 'isCruise'] as $key) {
            $response->assertSee('"'.$key.'"', false);
        }
    }

    public function test_home_categories_and_service_steps_are_admin_managed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $category = TourCategory::whereTranslation('slug', 'one-day')->firstOrFail();
        $category->fill(['image' => 'https://cdn.example.com/category.jpg', 'en' => [
            'name' => 'Admin Category',
            'slug' => 'one-day',
            'description' => 'Changed from the category CRUD.',
        ]])->save();

        CompanyContent::where('type', 'private_step')->firstOrFail()->fill([
            'en' => ['title' => 'Admin Private Step', 'description' => 'Private step description.'],
        ])->save();
        CompanyContent::where('type', 'visa_step')->firstOrFail()->fill([
            'en' => ['title' => 'Admin Visa Step', 'description' => 'Visa step description.'],
        ])->save();

        $this->get('/')->assertOk()
            ->assertSee('Admin Category')
            ->assertSee('https://cdn.example.com/category.jpg', false);
        $this->get('/private-tours')->assertOk()->assertSee('Admin Private Step');
        $this->get('/visa-support')->assertOk()->assertSee('Admin Visa Step');
    }
}
