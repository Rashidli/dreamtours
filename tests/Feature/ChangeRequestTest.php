<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Mail\EnquiryReceived;
use App\Models\Booking;
use App\Models\GalleryAlbum;
use App\Models\PickupPoint;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\BookingMail;
use Database\Seeders\CmsPermissionSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
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

    // 05 — iPhone HEIC photos are accepted and stored as WebP like every other photo.
    public function test_bulk_upload_accepts_iphone_heic_photos(): void
    {
        $album = $this->adminWithAlbum();
        $heic = new UploadedFile(base_path('tests/Fixtures/iphone-portrait.heic'), 'IMG_0042.HEIC', null, null, true);

        $this->get(route('domain-content.edit', ['gallery-albums', $album]))->assertSee('.heic,.heif', false);
        $response = $this->postJson(route('gallery-media.store', $album), ['type' => 'image', 'file' => $heic])->assertCreated();

        $stored = $album->media()->findOrFail($response->json('id'))->getRawOriginal('path');
        $this->assertStringEndsWith('.webp', $stored);
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($stored));
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame([2400, 3200], [$width, $height]);
    }

    // Photos straight from a phone (often 5–10 MB) are accepted for tours, reviews and other photo fields, up to 50 MB.
    public function test_photo_fields_accept_phone_sized_photos_up_to_50_mb(): void
    {
        Storage::fake('public');
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));
        [$tour] = $this->bookableTour();
        $payload = ['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Day Trip', 'slug' => 'niagara-day-trip']];

        $this->put(route('tours.update', $tour), $payload + ['main_image' => UploadedFile::fake()->image('phone.jpg', 4032, 3024)->size(9000)])->assertSessionHasNoErrors();
        $this->assertStringEndsWith('.webp', $tour->fresh()->getRawOriginal('main_image'));

        $this->put(route('tours.update', $tour), $payload + ['main_image' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size(52000)])->assertSessionHasErrors('main_image');
    }

    private function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings');
    }

    private function submitBooking(): Booking
    {
        [$tour, $departure] = $this->bookableTour(['deposit_per_person' => 65]);
        $pickup = PickupPoint::create(['address' => '100 Queen St W', 'is_active' => 1, 'en' => ['name' => 'Toronto City Hall']]);
        $this->post(route('front.bookings.store'), ['tour_id' => $tour->id, 'tour_departure_id' => $departure->id, 'pickup_point_id' => $pickup->id, 'type' => 'tour', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'phone' => '416-555-0101', 'email' => 'maria@example.com', 'participant_count' => 2, 'passengers' => [['first_name' => 'Maria', 'last_name' => 'Lopez', 'category' => 'adult'], ['first_name' => 'Leo', 'last_name' => 'Lopez', 'category' => 'child']]])->assertRedirect();

        $booking = Booking::firstOrFail();
        $booking->forceFill(['total_amount' => 200, 'deposit_amount' => 130])->saveQuietly();

        return $booking;
    }

    // 06 — a booking queues an acknowledgement for the customer and an alert for every office address.
    public function test_booking_queues_customer_acknowledgement_and_office_alert(): void
    {
        Mail::fake();
        $this->settings(['notification_email' => 'jenn@dreamtourpa.com, info@dreamtourpa.com, not-an-email', 'email' => 'info@dreamtourpa.com']);
        $booking = $this->submitBooking();

        Mail::assertQueued(BookingReceived::class, fn ($mail) => !$mail->forAdmin && $mail->hasTo('maria@example.com') && count($mail->to) === 1);
        Mail::assertQueued(BookingReceived::class, fn ($mail) => $mail->forAdmin && $mail->hasTo('jenn@dreamtourpa.com') && $mail->hasTo('info@dreamtourpa.com') && count($mail->to) === 2 && !$mail->hasTo('maria@example.com'));
        Mail::assertQueuedCount(2);
        $this->assertNotNull($booking->fresh()->confirmation_sent_at);
    }

    // 06 — the customer copy has the trip details and never claims the booking is confirmed.
    public function test_customer_booking_email_lists_trip_details_and_pending_status(): void
    {
        $booking = $this->submitBooking()->load(['tour', 'departure', 'pickupPoint', 'passengers']);
        $mail = new BookingReceived($booking);

        $mail->assertHasSubject("We received your booking request — {$booking->reference}");
        $mail->assertSeeInHtml('Niagara Day Trip');
        $mail->assertSeeInHtml($booking->departure->departs_at->format('g:i A'));
        $mail->assertSeeInHtml('Toronto City Hall');
        $mail->assertSeeInHtml('2 guests');
        $mail->assertSeeInHtml('CA$200');
        $mail->assertSeeInHtml('CA$130');
        $mail->assertSeeInHtml($booking->reference);
        $mail->assertSeeInHtml('pending confirmation');
        $mail->assertSeeInHtml('Child (2–11)');
        $mail->assertDontSeeInHtml('is confirmed');
        $this->assertSame(5, $mail->tries);
        $this->assertSame([60, 300, 900, 3600], $mail->backoff());
    }

    // 06 — the office copy carries the customer's contact details and a link to the booking in admin.
    public function test_office_booking_email_has_customer_contact_and_admin_link(): void
    {
        $booking = $this->submitBooking()->load(['tour', 'departure', 'pickupPoint', 'passengers']);
        $mail = new BookingReceived($booking, forAdmin: true);

        $mail->assertSeeInHtml('Maria Lopez');
        $mail->assertSeeInHtml('maria@example.com');
        $mail->assertSeeInHtml('416-555-0101');
        $mail->assertSeeInHtml(route('bookings.show', $booking));
        $mail->assertHasReplyTo('maria@example.com');
    }

    // 06 — setting the booking to Confirmed emails the customer once, however often the status is saved.
    public function test_confirming_a_booking_emails_the_customer_once(): void
    {
        $booking = $this->submitBooking();
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));
        Mail::fake();
        \App\Support\BookingMoney::record($booking, ['amount' => 130, 'method' => 'etransfer'], auth()->user(), confirmed: true);
        $update = fn (string $status) => $this->put(route('bookings.update', $booking), ['status' => $status, 'total_amount' => 200])->assertRedirect();

        $update('payment_pending');
        Mail::assertNothingQueued();
        $update('confirmed');
        $update('payment_pending');
        $update('confirmed');

        Mail::assertQueued(BookingConfirmed::class, 1);
        Mail::assertQueued(BookingConfirmed::class, fn ($mail) => $mail->hasTo('maria@example.com'));
        $this->assertNotNull($booking->fresh()->confirmed_notice_sent_at);
        (new BookingConfirmed($booking->fresh()->load(['tour', 'departure', 'pickupPoint', 'passengers'])))->assertSeeInHtml('Booking confirmed')->assertSeeInHtml('Balance remaining: CA$70');
    }

    // 06 — contact, call-back and private-tour requests reach the office and thank the customer.
    public function test_website_requests_notify_office_and_acknowledge_customer(): void
    {
        Mail::fake();
        $this->settings(['notification_email' => 'jenn@dreamtourpa.com']);
        foreach (['contact', 'callback', 'private_tour'] as $type) {
            $this->postJson(route('front.bookings.store'), ['type' => $type, 'first_name' => 'Sam', 'last_name' => 'Lee', 'phone' => '416-555-0199', 'email' => "sam+$type@example.com", 'participant_count' => 3, 'comments' => "Please help with $type"])->assertRedirect();
        }

        foreach (['contact' => 'Contact message', 'callback' => 'Call-back request', 'private_tour' => 'Private tour request'] as $type => $label) {
            Mail::assertQueued(EnquiryReceived::class, fn ($mail) => !$mail->forAdmin && $mail->booking->type === $type && $mail->hasTo("sam+$type@example.com"));
            Mail::assertQueued(EnquiryReceived::class, fn ($mail) => $mail->forAdmin && $mail->booking->type === $type && $mail->hasTo('jenn@dreamtourpa.com') && $mail->label() === $label);
        }
        Mail::assertQueuedCount(6);
        Mail::assertNotQueued(BookingReceived::class);

        $office = new EnquiryReceived(Booking::where('type', 'callback')->first(), forAdmin: true);
        $office->assertSeeInHtml('Sam Lee')->assertSeeInHtml('416-555-0199')->assertSeeInHtml('Please help with callback')->assertHasReplyTo('sam+callback@example.com');
        (new EnquiryReceived(Booking::where('type', 'callback')->first()))->assertSeeInHtml('call you back')->assertDontSeeInHtml('sam+callback@example.com');
    }

    // 06 — admins can preview every automatic email; people without booking access cannot.
    public function test_admin_can_preview_emails_without_sending_them(): void
    {
        $this->submitBooking();
        Mail::fake();
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $this->actingAs(User::factory()->create())->get(route('email-previews.index'))->assertForbidden();
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));
        $this->get(route('email-previews.index'))->assertOk()->assertSee('Booking request received')->assertSee('No sample yet');
        $this->get(route('email-previews.show', 'booking-received'))->assertOk()->assertSee('We received your booking request');
        $this->get(route('email-previews.show', 'booking-confirmed'))->assertOk()->assertSee('Booking confirmed');
        $this->get(route('email-previews.show', 'visa-enquiry'))->assertNotFound();
        $this->get(route('email-previews.show', 'nope'))->assertNotFound();
        Mail::assertNothingOutgoing();
    }

    private function ogTags(string $uri): array
    {
        preg_match_all('/<meta (?:property|name)="((?:og|twitter):[a-z:]+)" content="([^"]*)"/', $this->get($uri)->assertOk()->getContent(), $m);

        return array_combine($m[1], array_map('html_entity_decode', $m[2]));
    }

    // Link previews — pages show the logo card (a 1200×630 JPEG), not the favicon.
    public function test_pages_share_a_logo_card_as_link_preview(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put('logo.webp', (string) UploadedFile::fake()->image('logo.webp', 2361, 410)->getContent());
        $this->settings(['logo' => 'logo.webp', 'site_name' => 'DreamTour']);

        $og = $this->ogTags('/');
        $this->assertMatchesRegularExpression('#/storage/social/brand-[a-f0-9]{16}\.jpg$#', $og['og:image']);
        $this->assertSame(['1200', '630', 'image/jpeg'], [$og['og:image:width'], $og['og:image:height'], $og['og:image:type']]);
        $this->assertSame($og['og:image'], $og['twitter:image']);
        $this->assertSame('summary_large_image', $og['twitter:card']);
        $this->assertNotEmpty($og['og:title']);
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get(str($og['og:image'])->after('/storage/')));
        $this->assertSame([1200, 630, IMAGETYPE_JPEG], [$width, $height, $type]);

        // A new logo makes a new card.
        Storage::disk('public')->put('logo-2.png', (string) UploadedFile::fake()->image('logo-2.png', 800, 200)->getContent());
        $this->settings(['logo' => 'logo-2.png']);
        $this->assertNotSame($og['og:image'], $this->ogTags('/')['og:image']);
    }

    // Link previews — tour pages share the logo card too, with the tour's own title.
    // Since 2026-11-17 (technical SEO) a tour's link preview is its own photo; the logo card is the fallback.
    public function test_tour_pages_use_their_own_photo_card(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cover.webp', (string) UploadedFile::fake()->image('cover.webp', 2400, 1600)->getContent());
        Storage::disk('public')->put('logo.webp', (string) UploadedFile::fake()->image('logo.webp', 1200, 200)->getContent());
        $this->settings(['logo' => 'logo.webp']);
        $this->bookableTour(['main_image' => 'cover.webp']);

        $og = $this->ogTags('/tours/niagara-day-trip');
        $this->assertSame('Niagara Day Trip', $og['og:title']);
        $this->assertMatchesRegularExpression('#/storage/social/photo-[a-f0-9]{16}\.jpg$#', $og['og:image']);
        $this->assertSame(1, substr_count($this->get('/tours/niagara-day-trip')->getContent(), 'property="og:image"'));
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get(str_replace('/storage/', '', parse_url($og['og:image'], PHP_URL_PATH))));
        $this->assertSame([1200, 630], [$w, $h]);
    }

    // Security — nobody can open an admin account from the website; admins add users in Admin > Users.
    public function test_admin_registration_is_closed(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', ['name' => 'Intruder', 'email' => 'intruder@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123']);
        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
        $this->assertGuest();

        $user = User::factory()->create(['password' => bcrypt('right-password')]);
        $this->get('/admin')->assertOk();
        $this->post(route('login_submit'), ['email' => $user->email, 'password' => 'right-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    // 06 — without a notification email the main email still gets the alerts.
    public function test_office_alerts_fall_back_to_the_main_email(): void
    {
        $this->settings(['notification_email' => '', 'email' => 'info@dreamtourpa.com']);
        $this->assertSame(['info@dreamtourpa.com'], BookingMail::office());
        $this->settings(['notification_email' => 'jenn@dreamtourpa.com,  jenn@dreamtourpa.com ,ops@dreamtourpa.com']);
        $this->assertSame(['jenn@dreamtourpa.com', 'ops@dreamtourpa.com'], BookingMail::office());
    }
}
