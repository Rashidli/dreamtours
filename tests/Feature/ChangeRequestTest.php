<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    /** Three tours: Alpha (past only), Bravo (past + upcoming), Charlie (upcoming, draft). */
    private function adminWithDatedTours(): void
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));
        $dates = ['Alpha Lakes' => ['2025-03-14'], 'Bravo Falls' => ['2025-07-02', now()->addMonths(2)->format('Y-m-d')], 'Charlie Coast' => [now()->addMonths(4)->format('Y-m-d')]];
        foreach (array_keys($dates) as $i => $name) {
            $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => $name === 'Charlie Coast' ? 'draft' : 'published', 'sort_order' => 3 - $i, 'en' => ['name' => $name, 'slug' => str($name)->slug()]]);
            $tour->forceFill(['created_at' => now()->subDays(10 - $i)])->save();
            foreach ($dates[$name] as $date) {
                TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => $date.' 08:00', 'price' => 100, 'currency' => 'CAD', 'capacity' => 10, 'reserved_seats' => 0, 'status' => 'available']);
            }
        }
    }

    // 04 — month / year, exact date and All / Upcoming / Past find any tour; past tours stay visible.
    public function test_admin_tour_list_filters_by_departure_period(): void
    {
        $this->adminWithDatedTours();
        $list = fn (array $query) => $this->get(route('tours.index', $query))->assertOk();

        $list(['month' => 3, 'year' => 2025])->assertSee('Alpha Lakes')->assertDontSee('Bravo Falls')->assertDontSee('Charlie Coast')->assertSee('Mar 14, 2025');
        $list(['year' => 2025])->assertSee('Alpha Lakes')->assertSee('Bravo Falls')->assertDontSee('Charlie Coast');
        $list(['date' => '2025-07-02'])->assertSee('Bravo Falls')->assertDontSee('Alpha Lakes');
        $list(['when' => 'past'])->assertSee('Alpha Lakes')->assertSee('Bravo Falls')->assertDontSee('Charlie Coast');
        $list(['when' => 'upcoming'])->assertDontSee('Alpha Lakes')->assertSee('Bravo Falls')->assertSee('Charlie Coast');
        $list(['when' => 'upcoming', 'status' => 'published'])->assertSee('Bravo Falls')->assertDontSee('Charlie Coast');
        $list(['when' => 'all'])->assertSee('Alpha Lakes')->assertSee('Bravo Falls')->assertSee('Charlie Coast');
        $list(['q' => 'Bravo', 'year' => 2025])->assertSee('Bravo Falls')->assertDontSee('Alpha Lakes');
        $this->get(route('tours.index', ['month' => 13]))->assertSessionHasErrors('month');
    }

    // 04 — sorting by name, date added and departure date, combined with filters.
    public function test_admin_tour_list_sorts_by_name_date_added_and_departure(): void
    {
        $this->adminWithDatedTours();
        $order = fn (string $sort, array $query = []) => $this->get(route('tours.index', $query + ['sort' => $sort]))->assertOk();

        $order('name_asc')->assertSeeInOrder(['Alpha Lakes', 'Bravo Falls', 'Charlie Coast']);
        $order('name_desc')->assertSeeInOrder(['Charlie Coast', 'Bravo Falls', 'Alpha Lakes']);
        $order('created_desc')->assertSeeInOrder(['Charlie Coast', 'Bravo Falls', 'Alpha Lakes']);
        $order('created_asc')->assertSeeInOrder(['Alpha Lakes', 'Bravo Falls', 'Charlie Coast']);
        $order('departure_asc')->assertSeeInOrder(['Alpha Lakes', 'Bravo Falls', 'Charlie Coast']);
        $order('departure_desc')->assertSeeInOrder(['Charlie Coast', 'Bravo Falls', 'Alpha Lakes']);
        // Upcoming view sorts by the upcoming dates only: Bravo's next date comes before Charlie's.
        $order('departure_asc', ['when' => 'upcoming'])->assertSeeInOrder(['Bravo Falls', 'Charlie Coast'])->assertDontSee('Alpha Lakes');
        // Without a sort the admin's own sort_order still applies.
        $this->get(route('tours.index'))->assertOk()->assertSeeInOrder(['Charlie Coast', 'Bravo Falls', 'Alpha Lakes']);
    }

    private function adminWithAlbum(): GalleryAlbum
    {
        Storage::fake('public');
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));
        $album = GalleryAlbum::create(['cover_image' => 'albums/cover.webp', 'is_active' => true, 'en' => ['title' => 'Tobermory']]);
        $album->media()->create(['type' => 'image', 'path' => 'old-1.webp', 'sort_order' => 1]);
        $album->media()->create(['type' => 'image', 'path' => 'old-2.webp', 'sort_order' => 2]);

        return $album;
    }

    // 05 — the album pages offer the multi-file / drag-and-drop uploader.
    public function test_album_pages_show_bulk_uploader(): void
    {
        $album = $this->adminWithAlbum();

        $this->get(route('domain-content.edit', ['gallery-albums', $album]))->assertOk()->assertSee('Drop photos here or click to choose')->assertSee('multiple', false);
        $this->get(route('gallery-media.index', $album))->assertOk()->assertSee('Drop photos here or click to choose');
    }

    // 05 — a batch of 12 photos uploads file by file; existing media, order and cover stay intact.
    public function test_bulk_upload_adds_photos_in_order_and_keeps_existing_media(): void
    {
        $album = $this->adminWithAlbum();

        foreach (range(1, 12) as $i) {
            $this->postJson(route('gallery-media.store', $album), ['type' => 'image', 'file' => UploadedFile::fake()->image("photo-$i.jpg", 1200, 800)])
                ->assertCreated()->assertJsonStructure(['id', 'type', 'path', 'edit']);
        }

        $media = $album->media()->orderBy('sort_order')->get();
        $this->assertCount(14, $media);
        $this->assertSame(['old-1.webp', 'old-2.webp'], $media->take(2)->map->getRawOriginal('path')->all());
        $this->assertSame(range(1, 14), $media->pluck('sort_order')->all());
        $this->assertCount(12, Storage::disk('public')->allFiles());
        $this->assertSame('albums/cover.webp', $album->fresh()->getRawOriginal('cover_image'));
    }

    // 05 — an invalid file fails on its own with a readable message; the rest of the batch still uploads.
    public function test_bulk_upload_reports_invalid_files_without_stopping_the_batch(): void
    {
        $album = $this->adminWithAlbum();

        $this->postJson(route('gallery-media.store', $album), ['type' => 'image', 'file' => UploadedFile::fake()->image('good-1.jpg')])->assertCreated();
        $this->postJson(route('gallery-media.store', $album), ['type' => 'image', 'file' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson(route('gallery-media.store', $album), ['type' => 'image', 'file' => UploadedFile::fake()->image('good-2.jpg')])->assertCreated();

        $this->assertSame(4, $album->media()->count());
        // The single link form still redirects back as before.
        $this->post(route('gallery-media.store', $album), ['type' => 'video', 'path' => 'https://youtu.be/abc'])->assertRedirect()->assertSessionHas('message', 'Media added.');
    }
}
