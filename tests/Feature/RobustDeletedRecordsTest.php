<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Admin pages keep working (no 500) when the tour or pickup stop behind a trip was archived (soft-deleted). */
class RobustDeletedRecordsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tour $tour;
    private TourDeparture $departure;
    private PickupPoint $stop;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));

        $this->tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Archived Falls Trip', 'slug' => 'archived-falls-'.uniqid()]]);
        $this->stop = PickupPoint::create(['address' => '65 Front St W', 'is_active' => true, 'en' => ['name' => 'Old Union Stop']]);
        $this->departure = TourDeparture::create(['tour_id' => $this->tour->id, 'departs_at' => now()->subDays(3)->setTime(7, 0), 'price' => 150, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $this->booking = Booking::create(['tour_id' => $this->tour->id, 'tour_departure_id' => $this->departure->id, 'pickup_point_id' => $this->stop->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Ana', 'last_name' => 'Alvarez', 'email' => 'ana@example.com', 'phone' => '416-555-0101', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '150.00', 'deposit_amount' => '75.00']);
        BookingPassenger::create(['booking_id' => $this->booking->id, 'pickup_point_id' => $this->stop->id, 'first_name' => 'Ana', 'last_name' => 'Alvarez', 'category' => 'adult']);

        $this->tour->delete();
        $this->stop->delete();
    }

    public function test_the_passenger_list_and_exports_of_a_deleted_tour_still_open(): void
    {
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', $this->departure))
            ->assertOk()->assertSee('Archived Falls Trip')->assertSee('Ana')->assertSee('Old Union Stop');
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->departure, 'export' => 'xlsx']))->assertOk();
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->departure, 'export' => 'pdf']))->assertOk();
    }

    public function test_the_trip_page_of_a_deleted_tour_still_opens(): void
    {
        $this->actingAs($this->admin)->get(route('my-trips.show', $this->departure))
            ->assertOk()->assertSee('Archived Falls Trip')->assertSee('Old Union Stop');
    }

    public function test_a_booking_keeps_showing_its_archived_tour_and_pickup_stop(): void
    {
        $this->assertSame('Archived Falls Trip', $this->booking->fresh()->tour?->name);
        $this->assertSame('Old Union Stop', $this->booking->fresh()->pickupPoint?->name);
        $this->assertSame('Old Union Stop', $this->booking->passengers()->first()->pickupPoint?->name);
        $this->actingAs($this->admin)->get(route('bookings.show', $this->booking))
            ->assertOk()->assertSee('Archived Falls Trip');
    }

    public function test_saving_a_price_for_all_dates_of_a_deleted_tour_does_not_crash(): void
    {
        $this->actingAs($this->admin)->post(route('tour-departure-prices.store', $this->departure), ['code' => 'adult', 'price' => 160, 'apply_to_all' => 1])
            ->assertRedirect();
        $this->assertSame('160.00', (string) $this->departure->prices()->where('code', 'adult')->value('price'));
    }
}
