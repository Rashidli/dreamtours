<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\DepartureStaff;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** A departure whose tour was soft-deleted stays readable (trip page, passenger list, exports) instead of a 500. */
class DeletedTourDepartureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $leader;
    private TourDeparture $departure;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->leader = User::factory()->create(['name' => 'Leo Leader']);
        $this->leader->assignRole('Tour Leader');

        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Archived Falls Trip', 'slug' => 'archived-falls-'.uniqid()]]);
        $this->departure = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->subDays(3)->setTime(7, 0), 'price' => 150, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $booking = Booking::create(['tour_id' => $tour->id, 'tour_departure_id' => $this->departure->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Ana', 'last_name' => 'Alvarez', 'email' => 'ana@example.com', 'phone' => '416-555-0101', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '150.00', 'deposit_amount' => '75.00']);
        BookingPassenger::create(['booking_id' => $booking->id, 'first_name' => 'Ana', 'last_name' => 'Alvarez', 'category' => 'adult']);
        DepartureStaff::create(['tour_departure_id' => $this->departure->id, 'user_id' => $this->leader->id, 'role' => 'leader']);

        $tour->delete();
        $this->assertSoftDeleted($tour);
    }

    public function test_office_staff_can_open_the_trip_page_of_a_deleted_tour(): void
    {
        $this->actingAs($this->admin)->get(route('my-trips.show', $this->departure))
            ->assertOk()->assertSee('Archived Falls Trip')->assertSee('Deleted tour')->assertSee('Ana');
    }

    public function test_the_assigned_leader_sees_the_trip_in_past_trips_and_can_open_it(): void
    {
        $this->actingAs($this->leader)->get(route('my-trips.index', ['show' => 'past']))
            ->assertOk()->assertSee('Archived Falls Trip');
        $this->actingAs($this->leader)->get(route('my-trips.show', $this->departure))
            ->assertOk()->assertSee('Ana');
    }

    public function test_the_office_passenger_list_and_its_export_work_for_a_deleted_tour(): void
    {
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', $this->departure))
            ->assertOk()->assertSee('Archived Falls Trip')->assertSee('Ana');
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->departure, 'export' => 'xlsx']))
            ->assertOk();
    }
}
