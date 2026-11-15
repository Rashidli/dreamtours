<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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

/** CRM §5, §12, §16 — staff per departure; AT-07: a leader of trip A cannot open trip B, even by URL. */
class DepartureStaffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $leader;
    private TourDeparture $tripA;
    private TourDeparture $tripB;
    private Booking $bookingA;
    private Booking $bookingB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->leader = User::factory()->create(['name' => 'Leo Leader', 'phone' => '647-555-0199']);
        $this->leader->assignRole('Tour Leader');
        [$this->tripA, $this->bookingA] = $this->trip('Niagara Falls', 'Ana', 'Alvarez');
        [$this->tripB, $this->bookingB] = $this->trip('Quebec City', 'Bruno', 'Barros');
    }

    /** @return array{0: TourDeparture, 1: Booking} */
    private function trip(string $name, string $first, string $last): array
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => $name, 'slug' => str($name)->slug().'-'.uniqid()]]);
        $departure = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(10)->setTime(7, 30), 'price' => 150, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $booking = Booking::create(['tour_id' => $tour->id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => $first, 'last_name' => $last, 'email' => strtolower($first).'@example.com', 'phone' => '416-555-0'.rand(100, 999), 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '150.00', 'deposit_amount' => '75.00']);
        BookingPassenger::create(['booking_id' => $booking->id, 'first_name' => $first, 'last_name' => $last, 'category' => 'adult', 'seat_number' => '12', 'meal_selection' => 'Vegetarian']);

        return [$departure, $booking];
    }

    private function assign(TourDeparture $departure, User $user, string $role = 'leader')
    {
        return $this->actingAs($this->admin)->post(route('departure-staff.store', $departure), ['user_id' => $user->id, 'role' => $role, 'note' => 'Bus 1']);
    }

    public function test_a_leader_sees_only_the_trip_assigned_to_them_even_by_direct_url(): void
    {
        $this->assign($this->tripA, $this->leader)->assertRedirect();
        $this->actingAs($this->leader);

        $this->get(route('home'))->assertRedirect(route('my-trips.index'));
        $this->get(route('my-trips.index'))->assertOk()->assertSee('Niagara Falls')->assertDontSee('Quebec City');
        $this->get(route('my-trips.show', $this->tripA))->assertOk()
            ->assertSee('Ana Alvarez')->assertSee('Vegetarian')->assertSee('Leo Leader')->assertSee('647-555-0199')
            ->assertDontSee('Bruno')->assertDontSee('CA$')->assertDontSee(route('bookings.show', $this->bookingA));

        $this->get(route('my-trips.show', $this->tripB))->assertForbidden();
        $this->get(route('tour-departures.passengers', $this->tripB))->assertForbidden();
        $this->get(route('tour-departures.passengers', $this->tripA))->assertForbidden();
        $this->get(route('bookings.show', $this->bookingB))->assertForbidden();
        $this->get(route('bookings.index'))->assertForbidden();
        $this->get(route('customers.index'))->assertForbidden();
        $this->get(route('payments.index'))->assertForbidden();
    }

    public function test_removing_the_assignment_revokes_access_immediately_and_both_changes_are_logged(): void
    {
        $this->assign($this->tripA, $this->leader);
        $this->actingAs($this->leader)->get(route('my-trips.show', $this->tripA))->assertOk();

        $assignment = DepartureStaff::where(['user_id' => $this->leader->id, 'tour_departure_id' => $this->tripA->id])->firstOrFail();
        $this->actingAs($this->admin)->delete(route('departure-staff.destroy', [$this->tripA, $assignment]))->assertRedirect();

        $this->actingAs($this->leader)->get(route('my-trips.show', $this->tripA))->assertForbidden();
        $this->get(route('my-trips.index'))->assertOk()->assertDontSee('Niagara Falls');
        $this->assertSame(['staff_assigned', 'staff_removed'], ActivityLog::whereIn('action', ['staff_assigned', 'staff_removed'])->orderBy('id')->pluck('action')->all());
        $this->assertSame($this->admin->id, ActivityLog::where('action', 'staff_removed')->value('actor_id'));
    }

    public function test_assigning_twice_keeps_one_row_and_a_person_can_hold_two_jobs(): void
    {
        $this->assign($this->tripA, $this->leader);
        $this->assign($this->tripA, $this->leader);
        $this->assign($this->tripA, $this->leader, 'driver');
        $this->assertSame(2, DepartureStaff::where('user_id', $this->leader->id)->count());
        $this->assertSame(1, ActivityLog::where('action', 'staff_assigned')->where('subject_id', $this->tripA->id)->whereJsonContains('changes->staff', [null, 'Leo Leader · Tour leader'])->count());

        // The trip appears once in My trips, with both jobs.
        $this->actingAs($this->leader)->get(route('my-trips.index'))->assertOk()->assertSeeInOrder(['Niagara Falls', 'Tour leader', 'Driver']);
    }

    public function test_only_people_with_assign_permission_can_change_staff_and_office_staff_can_preview_the_trip(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager');
        $this->actingAs($sales)->post(route('departure-staff.store', $this->tripA), ['user_id' => $sales->id, 'role' => 'leader'])->assertForbidden();
        $this->actingAs($this->leader)->post(route('departure-staff.store', $this->tripB), ['user_id' => $this->leader->id, 'role' => 'leader'])->assertForbidden();
        $this->assertSame(0, DepartureStaff::count());

        // Sales can see bookings, so it may open the trip page as the leader would see it.
        $this->actingAs($sales)->get(route('my-trips.show', $this->tripB))->assertOk()->assertSee('Bruno Barros')->assertSee('viewing this trip as office staff');
        $this->actingAs($this->admin)->post(route('departure-staff.store', $this->tripA), ['user_id' => $this->leader->id, 'role' => 'pilot'])->assertSessionHasErrors('role');
    }

    public function test_the_tour_page_shows_each_dates_team_and_the_assign_form(): void
    {
        $this->assign($this->tripA, $this->leader);
        $this->actingAs($this->admin)->get(route('tours.edit', $this->tripA->tour_id))->assertOk()
            ->assertSee('Leo Leader')->assertSee('Staff (1)')->assertSee('Trip staff ·', false)
            ->assertSee(route('departure-staff.store', $this->tripA), false);
        $this->actingAs($this->admin)->get(route('tours.edit', $this->tripB->tour_id))->assertOk()->assertSee('No leader or driver assigned');
    }
}
