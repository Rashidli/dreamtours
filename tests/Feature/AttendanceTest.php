<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\DepartureStaff;
use App\Models\PassengerAttendanceEvent;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\Attendance;
use App\Support\Roster;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §11 — roster, check-in and no-shows; AT-08: totals move with every mark, no-shows stay counted. */
class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $leader;
    private TourDeparture $trip;
    private PickupPoint $union;
    private PickupPoint $mississauga;
    /** @var array<string, BookingPassenger> */
    private array $p = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->leader = User::factory()->create(['name' => 'Leo Leader']);
        $this->leader->assignRole('Tour Leader');

        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'niagara-'.uniqid()]]);
        $this->trip = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addHours(2), 'price' => 150, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $this->union = PickupPoint::create(['address' => '65 Front St W', 'is_active' => true, 'en' => ['name' => 'Union Station']]);
        $this->mississauga = PickupPoint::create(['address' => '100 City Centre Dr', 'is_active' => true, 'en' => ['name' => 'Square One']]);
        $this->trip->pickupPoints()->sync([$this->union->id => ['sort_order' => 1], $this->mississauga->id => ['sort_order' => 2]]);
        DepartureStaff::create(['tour_departure_id' => $this->trip->id, 'user_id' => $this->leader->id, 'role' => 'leader']);

        // 4 passengers on 2 bookings: 3 at Union Station, 1 at Square One; plus a cancelled booking.
        $family = $this->booking('Maria', 'Lopez', 'confirmed', '300.00');
        $this->p['maria'] = $this->passenger($family, 'Maria', 'Lopez', $this->union, '10', 'Vegetarian');
        $this->p['leo'] = $this->passenger($family, 'Leo', 'Lopez', $this->union, '11', null, 'child');
        $this->p['ana'] = $this->passenger($this->booking('Ana', 'Alvarez', 'new', '150.00'), 'Ana', 'Alvarez', $this->union, '2', 'Chicken');
        $this->p['sam'] = $this->passenger($this->booking('Sam', 'Singh', 'confirmed', '150.00'), 'Sam', 'Singh', $this->mississauga, '12', 'Chicken');
        $this->passenger($this->booking('Carl', 'Cancelled', 'cancelled', '150.00'), 'Carl', 'Cancelled', $this->union, '30', null);
    }

    private function booking(string $first, string $last, string $status, string $total): Booking
    {
        return Booking::create(['tour_id' => $this->trip->tour_id, 'tour_departure_id' => $this->trip->id, 'type' => 'tour', 'status' => $status, 'first_name' => $first, 'last_name' => $last, 'email' => strtolower($first).'@example.com', 'phone' => '416-555-01'.rand(10, 99), 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => $total, 'deposit_amount' => '0.00']);
    }

    private function passenger(Booking $b, string $first, string $last, PickupPoint $stop, string $seat, ?string $meal, string $category = 'adult'): BookingPassenger
    {
        return BookingPassenger::create(['booking_id' => $b->id, 'first_name' => $first, 'last_name' => $last, 'category' => $category, 'pickup_point_id' => $stop->id, 'seat_number' => $seat, 'meal_selection' => $meal]);
    }

    private function mark(string $who, string $status, ?User $as = null, ?string $note = null)
    {
        return $this->actingAs($as ?? $this->leader)->from(route('my-trips.show', $this->trip))
            ->post(route('passenger-attendance.update', [$this->trip, $this->p[$who]]), ['status' => $status, 'note' => $note]);
    }

    /** Counts per stop id from the roster helper, as the pages show them. */
    private function counts(?int $stop = null): array
    {
        $rows = Roster::all($this->trip->fresh());

        return Attendance::counts($rows->filter(fn ($r) => $stop === null || $r['passenger']->pickup_point_id === $stop)->pluck('passenger'));
    }

    public function test_check_in_moves_the_pickup_totals_at_once_and_no_shows_stay_counted(): void
    {
        $this->assertSame(['expected' => 4, 'arrived' => 0, 'boarded' => 0, 'missing' => 4, 'no_show' => 0], $this->counts(), 'cancelled booking is not on the list');

        $this->mark('maria', 'checked_in')->assertRedirect(route('my-trips.show', $this->trip).'#p-'.$this->p['maria']->id);
        $this->assertSame(['expected' => 3, 'arrived' => 1, 'boarded' => 0, 'missing' => 2, 'no_show' => 0], $this->counts($this->union->id));

        $this->mark('leo', 'boarded');
        $this->mark('ana', 'no_show');
        $this->mark('sam', 'not_arrived');
        $this->assertSame(['expected' => 3, 'arrived' => 2, 'boarded' => 1, 'missing' => 0, 'no_show' => 1], $this->counts($this->union->id));
        $this->assertSame(['expected' => 1, 'arrived' => 0, 'boarded' => 0, 'missing' => 1, 'no_show' => 0], $this->counts($this->mississauga->id));
        $this->assertSame(['expected' => 4, 'arrived' => 2, 'boarded' => 1, 'missing' => 1, 'no_show' => 1], $this->counts());

        // The page shows the same totals, keeps the no-show on the list and flags who is still missing (2 h before departure is outside the alert window).
        $page = $this->actingAs($this->leader)->get(route('my-trips.show', $this->trip))->assertOk();
        $page->assertSeeInOrder(['Union Station', '3', '2', '1', '0', '1'])->assertSee('Ana Alvarez')->assertSee('No-show')->assertDontSee('still missing');
        $this->travel(110)->minutes();
        $this->actingAs($this->leader)->get(route('my-trips.show', $this->trip))->assertSee('1 still missing');
        $this->actingAs($this->leader)->get(route('my-trips.show', [$this->trip, 'attendance' => 'missing']))->assertSee('Sam Singh')->assertDontSee('Maria Lopez');
    }

    public function test_every_mark_is_an_event_with_time_person_and_note_and_a_note_keeps_the_original_time(): void
    {
        $this->freezeSecond();
        $this->mark('maria', 'checked_in');
        $at = $this->p['maria']->fresh()->attendance_at;
        $this->travel(3)->minutes();
        $this->mark('maria', 'checked_in', note: 'Brought a wheelchair');
        $maria = $this->p['maria']->fresh();
        $this->assertSame('checked_in', $maria->attendance_status);
        $this->assertEquals($at, $maria->attendance_at);
        $this->assertSame('Brought a wheelchair', $maria->attendance_note);
        $this->assertSame($this->leader->id, $maria->attendance_by);

        $this->mark('maria', 'boarded');
        $events = PassengerAttendanceEvent::where('booking_passenger_id', $maria->id)->orderBy('id')->get();
        $this->assertSame([[null, 'checked_in'], ['checked_in', 'checked_in'], ['checked_in', 'boarded']], $events->map(fn ($e) => [$e->previous_status, $e->status])->all());
        $this->assertSame([$this->leader->id], $events->pluck('user_id')->unique()->values()->all());
        $this->assertSame($this->trip->id, $events->first()->tour_departure_id);
        $this->assertSame(0, ActivityLog::where('subject_type', BookingPassenger::class)->where('action', 'updated')->count(), 'no duplicate history rows');
    }

    public function test_undo_needs_permission_except_for_your_own_mark_within_ten_minutes(): void
    {
        $this->mark('maria', 'boarded');
        $this->mark('maria', 'checked_in')->assertSessionHasNoErrors(); // own mark, 0 minutes ago
        $this->assertSame('checked_in', $this->p['maria']->fresh()->attendance_status);

        $this->travel(11)->minutes();
        $this->mark('maria', 'expected')->assertSessionHasErrors('attendance');
        $this->assertSame('checked_in', $this->p['maria']->fresh()->attendance_status);
        $this->mark('maria', 'boarded')->assertSessionHasNoErrors(); // forward is always fine

        // A second leader cannot undo someone else's mark; an admin can.
        $other = User::factory()->create();
        $other->assignRole('Tour Leader');
        DepartureStaff::create(['tour_departure_id' => $this->trip->id, 'user_id' => $other->id, 'role' => 'guide']);
        $this->mark('maria', 'no_show', $other)->assertSessionHasErrors('attendance');
        $this->mark('maria', 'expected', $this->admin)->assertSessionHasNoErrors();
        $this->assertNull($this->p['maria']->fresh()->attendance_status);
        $this->assertNull($this->p['maria']->fresh()->attendance_at);
    }

    public function test_only_staff_on_this_trip_or_the_office_can_mark_and_only_passengers_of_this_trip(): void
    {
        $stranger = User::factory()->create();
        $stranger->assignRole('Tour Leader');
        $this->mark('maria', 'checked_in', $stranger)->assertForbidden();

        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager'); // sees bookings but has no check-in permission
        $this->mark('maria', 'checked_in', $sales)->assertForbidden();
        $this->assertNull($this->p['maria']->fresh()->attendance_status);

        $otherTrip = TourDeparture::create(['tour_id' => $this->trip->tour_id, 'departs_at' => now()->addDays(3), 'price' => 150, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $this->actingAs($this->admin)->post(route('passenger-attendance.update', [$otherTrip, $this->p['maria']]), ['status' => 'checked_in'])->assertNotFound();
        $cancelled = BookingPassenger::where('first_name', 'Carl')->firstOrFail();
        $this->actingAs($this->admin)->post(route('passenger-attendance.update', [$this->trip, $cancelled]), ['status' => 'checked_in'])->assertNotFound();
        $this->actingAs($this->admin)->post(route('passenger-attendance.update', [$this->trip, $this->p['maria']]), ['status' => 'teleported'])->assertSessionHasErrors('status');

        // Removing the leader from the trip ends their check-in rights too.
        DepartureStaff::where('user_id', $this->leader->id)->delete();
        $this->mark('maria', 'checked_in')->assertForbidden();
    }

    public function test_office_roster_filters_and_sorts(): void
    {
        $this->mark('ana', 'no_show');
        $this->actingAs($this->admin);
        $url = fn (array $q) => route('tour-departures.passengers', [$this->trip] + $q);

        $this->get($url([]))->assertOk()->assertSeeInOrder(['Ana Alvarez', 'Leo Lopez', 'Maria Lopez', 'Sam Singh'])->assertDontSee('Carl Cancelled</span>', false)->assertSee('CA$');
        $this->get($url(['sort' => 'seat']))->assertSeeInOrder(['Ana Alvarez', 'Maria Lopez', 'Leo Lopez', 'Sam Singh']); // 2, 10, 11, 12 — natural order
        $this->get($url(['pickup' => $this->mississauga->id]))->assertSee('Sam Singh')->assertDontSee('Maria Lopez');
        $this->get($url(['meal' => 'Chicken']))->assertSee('Ana Alvarez')->assertSee('Sam Singh')->assertDontSee('Maria Lopez');
        $this->get($url(['meal' => '—']))->assertSee('Leo Lopez')->assertDontSee('Sam Singh')->assertDontSee('Ana Alvarez'); // Maria still shows as Leo's contact
        $this->get($url(['attendance' => 'no_show']))->assertSee('Ana Alvarez')->assertDontSee('Sam Singh');
        $this->get($url(['booking_status' => 'new']))->assertSee('Ana Alvarez')->assertDontSee('Maria Lopez');
        $this->get($url(['q' => 'lopez']))->assertSee('Leo Lopez')->assertSee('Maria Lopez')->assertDontSee('Sam Singh');
    }

    public function test_exports_carry_only_what_the_recipient_needs_and_are_logged(): void
    {
        $this->mark('maria', 'checked_in');
        $office = $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->trip, 'export' => 'csv']));
        $office->assertOk();
        $csv = $office->streamedContent();
        $this->assertStringContainsString('Booking status', $csv);
        $this->assertStringContainsString('Booking balance (CAD)', $csv);
        $this->assertStringContainsString('"Maria Lopez"', str_replace('Maria Lopez', '"Maria Lopez"', $csv));
        $this->assertStringContainsString('Checked in', $csv);
        $this->assertSame(5, substr_count(trim($csv), "\n") + 1, 'header + 4 passengers, cancelled excluded');

        $leaderCsv = $this->actingAs($this->leader)->get(route('my-trips.show', [$this->trip, 'export' => 'csv']))->streamedContent();
        $this->assertStringNotContainsString('Booking status', $leaderCsv);
        $this->assertStringNotContainsString('balance', strtolower($leaderCsv));
        $this->assertStringNotContainsString('@example.com', $leaderCsv);

        $this->actingAs($this->leader)->get(route('my-trips.show', [$this->trip, 'export' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $xlsx = $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->trip, 'export' => 'xlsx']));
        $xlsx->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->trip, 'export' => 'exe']))->assertNotFound();
        $this->assertSame(4, ActivityLog::where('action', 'roster_exported')->count());
    }
}
