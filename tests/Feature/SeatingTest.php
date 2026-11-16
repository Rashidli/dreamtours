<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\DepartureStaff;
use App\Models\DepartureVehicle;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\VehicleLayout;
use App\Support\Roster;
use App\Support\Seating;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Database\QueryException;
use App\Mail\SeatAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §9 — seating board; AT-03 one booking, four pickups and seats; AT-06 seat 10 → 12, never two in one seat. */
class SeatingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TourDeparture $trip;
    private DepartureVehicle $bus;
    private PickupPoint $union;
    private PickupPoint $square;
    /** @var array<string, BookingPassenger> */
    private array $p = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'niagara-'.uniqid()]]);
        $this->trip = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(5)->setTime(7, 30), 'price' => 150, 'currency' => 'CAD', 'capacity' => 56, 'reserved_seats' => 0, 'status' => 'available']);
        $this->union = PickupPoint::create(['address' => '65 Front St W', 'is_active' => true, 'en' => ['name' => 'Union Station']]);
        $this->square = PickupPoint::create(['address' => '100 City Centre Dr', 'is_active' => true, 'en' => ['name' => 'Square One']]);
        $this->trip->pickupPoints()->sync([$this->union->id => ['sort_order' => 1], $this->square->id => ['sort_order' => 2]]);
        $this->bus = Seating::addVehicle($this->trip, VehicleLayout::where('name', 'Coach (56 seats)')->firstOrFail());

        $family = $this->booking('Lopez', '2026-01-01 10:00');
        foreach (['Maria' => $this->square, 'Leo' => $this->union, 'Ana' => $this->square, 'Sam' => $this->union] as $first => $stop) {
            $this->p[$first] = BookingPassenger::create(['booking_id' => $family->id, 'first_name' => $first, 'last_name' => 'Lopez', 'category' => 'adult', 'pickup_point_id' => $stop->id]);
        }
        $this->p['Bruno'] = BookingPassenger::create(['booking_id' => $this->booking('Barros', '2026-01-02 10:00')->id, 'first_name' => 'Bruno', 'last_name' => 'Barros', 'category' => 'adult', 'pickup_point_id' => $this->union->id]);
    }

    private function booking(string $last, string $at): Booking
    {
        $b = Booking::create(['tour_id' => $this->trip->tour_id, 'tour_departure_id' => $this->trip->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Pat', 'last_name' => $last, 'email' => strtolower($last).'@example.com', 'phone' => '416-555-0101', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '150.00', 'deposit_amount' => '0.00']);
        $b->forceFill(['created_at' => $at])->saveQuietly();

        return $b;
    }

    private function place(array $placements, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)->postJson(route('seating.place', $this->trip), ['placements' => $placements]);
    }

    private function seat(string $who): ?string
    {
        $p = $this->p[$who]->fresh();

        return $p->departure_vehicle_id ? $p->seat_number : null;
    }

    public function test_moving_a_passenger_from_seat_10_to_12_updates_roster_and_leader_view_and_frees_10(): void
    {
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '10']])->assertOk()->assertJsonPath('state.passengers.0.seat', null);
        $this->assertSame('10', $this->seat('Maria'));
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '12']])->assertOk();
        $this->assertSame('12', $this->seat('Maria'));
        $this->assertSame(0, BookingPassenger::where(['departure_vehicle_id' => $this->bus->id, 'seat_number' => '10'])->count());

        // The same seat shows on the office roster, the leader's trip page and the board.
        $leader = User::factory()->create();
        $leader->assignRole('Tour Leader');
        DepartureStaff::create(['tour_departure_id' => $this->trip->id, 'user_id' => $leader->id, 'role' => 'leader']);
        $this->actingAs($this->admin)->get(route('tour-departures.passengers', [$this->trip, 'q' => 'maria']))->assertSee('Seat 12');
        $this->actingAs($leader)->get(route('my-trips.show', [$this->trip, 'q' => 'maria']))->assertSee('Seat 12');
        $board = $this->actingAs($leader)->get(route('seating.show', $this->trip))->assertOk()->viewData('state');
        $this->assertSame('12', collect($board['passengers'])->firstWhere('id', $this->p['Maria']->id)['seat']);
        $this->assertFalse($board['canEdit']);

        $this->assertSame([[null, 'Bus 1 · 10'], ['Bus 1 · 10', 'Bus 1 · 12']], ActivityLog::where('action', 'seat_changed')->orderBy('id')->pluck('changes')->map(fn ($c) => $c['seat'])->all());
    }

    public function test_two_passengers_can_never_hold_one_seat(): void
    {
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '12']])->assertOk();

        // Someone else seating Leo on 12 (e.g. a second browser with an old board) is refused and nothing changes.
        $this->place([['p' => $this->p['Leo']->id, 'v' => $this->bus->id, 's' => '12']])->assertStatus(422)->assertJsonPath('message', 'Seat 12 on Bus 1 was just taken by Maria Lopez. The board has been refreshed.');
        $this->assertNull($this->seat('Leo'));
        $this->place([['p' => $this->p['Leo']->id, 'v' => $this->bus->id, 's' => '3'], ['p' => $this->p['Ana']->id, 'v' => $this->bus->id, 's' => '3']])->assertStatus(422);

        // The database refuses it too, whatever code path tries.
        $this->expectException(QueryException::class);
        $this->p['Leo']->forceFill(['departure_vehicle_id' => $this->bus->id, 'seat_number' => '12'])->saveQuietly();
    }

    public function test_dropping_on_a_taken_seat_swaps_and_undo_puts_both_back(): void
    {
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '10'], ['p' => $this->p['Leo']->id, 'v' => $this->bus->id, 's' => '12']])->assertOk();
        // What the board sends for "drag Maria onto 12": Maria → 12, Leo → Maria's old seat.
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '12'], ['p' => $this->p['Leo']->id, 'v' => $this->bus->id, 's' => '10']])->assertOk();
        $this->assertSame(['12', '10'], [$this->seat('Maria'), $this->seat('Leo')]);
        // Undo sends the previous placements.
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '10'], ['p' => $this->p['Leo']->id, 'v' => $this->bus->id, 's' => '12']])->assertOk();
        $this->assertSame(['10', '12'], [$this->seat('Maria'), $this->seat('Leo')]);
        // Unseat.
        $this->place([['p' => $this->p['Leo']->id, 'v' => null, 's' => null]])->assertOk();
        $this->assertNull($this->seat('Leo'));
    }

    public function test_driver_aisle_unknown_and_blocked_seats_are_never_available(): void
    {
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => 'D']])->assertStatus(422)->assertJsonPath('message', 'Bus 1 has no seat D.');
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '57']])->assertStatus(422);
        $this->actingAs($this->admin)->postJson(route('seating.block', $this->trip), ['vehicle' => $this->bus->id, 'seat' => '1', 'blocked' => true])->assertOk();
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '1']])->assertStatus(422)->assertJsonPath('message', 'Seat 1 on Bus 1 is blocked.');

        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '2']])->assertOk();
        $this->postJson(route('seating.block', $this->trip), ['vehicle' => $this->bus->id, 'seat' => '2', 'blocked' => true])->assertStatus(422);
        $this->postJson(route('seating.block', $this->trip), ['vehicle' => $this->bus->id, 'seat' => '1', 'blocked' => false])->assertOk();
        $this->assertSame([], $this->bus->fresh()->blockedSeats());

        // A bus of another date cannot be used.
        $other = TourDeparture::create(['tour_id' => $this->trip->tour_id, 'departs_at' => now()->addDays(9), 'price' => 150, 'currency' => 'CAD', 'capacity' => 7, 'reserved_seats' => 0, 'status' => 'available']);
        $van = Seating::addVehicle($other, VehicleLayout::where('name', 'Minivan (7 seats)')->firstOrFail());
        $this->place([['p' => $this->p['Leo']->id, 'v' => $van->id, 's' => '1']])->assertStatus(422);
    }

    public function test_one_booking_of_four_can_have_different_pickups_and_seats_and_auto_seat_keeps_stops_and_bookings_together(): void
    {
        $this->actingAs($this->admin)->postJson(route('seating.block', $this->trip), ['vehicle' => $this->bus->id, 'seat' => '2', 'blocked' => true]);
        $this->postJson(route('seating.autofill', $this->trip), ['stop' => $this->union->id])->assertOk()->assertJsonPath('message', '3 passengers seated.');
        // Union Station first (stop order), the Lopez family before Bruno (booked earlier), blocked seat 2 skipped.
        $this->assertSame(['Leo' => '1', 'Sam' => '3', 'Bruno' => '4', 'Maria' => null, 'Ana' => null], collect(['Leo', 'Sam', 'Bruno', 'Maria', 'Ana'])->mapWithKeys(fn ($w) => [$w => $this->seat($w)])->all());
        $this->postJson(route('seating.autofill', $this->trip), [])->assertOk()->assertJsonPath('message', '2 passengers seated.');
        $this->assertSame(['5', '6'], [$this->seat('Maria'), $this->seat('Ana')]);

        // AT-03: one booking, four passengers, two pickup stops, four different seats.
        $family = $this->p['Maria']->booking->passengers()->get();
        $this->assertCount(4, $family);
        $this->assertCount(2, $family->pluck('pickup_point_id')->unique());
        $this->assertCount(4, $family->pluck('seat_number')->unique());
        $this->postJson(route('seating.autofill', $this->trip), [])->assertOk()->assertJsonPath('message', 'Nobody left to seat, or no free seats.');
    }

    public function test_cancelling_a_booking_gives_its_seats_back(): void
    {
        $this->place([['p' => $this->p['Bruno']->id, 'v' => $this->bus->id, 's' => '7']]);
        $bruno = $this->p['Bruno']->booking;
        $this->actingAs($this->admin)->put(route('bookings.update', $bruno), ['status' => 'cancelled'])->assertRedirect();
        $this->assertNull($this->seat('Bruno'));
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '7']])->assertOk();
        $this->assertNotContains($this->p['Bruno']->id, collect(Seating::state($this->trip, $this->admin)['passengers'])->pluck('id'));
    }

    public function test_who_can_see_and_who_can_change_the_board(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('Tour Leader');
        $this->actingAs($leader)->get(route('seating.show', $this->trip))->assertForbidden();
        DepartureStaff::create(['tour_departure_id' => $this->trip->id, 'user_id' => $leader->id, 'role' => 'leader']);
        $this->actingAs($leader)->get(route('seating.show', $this->trip))->assertOk()->assertDontSee('Add a bus to this date');
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '1']], $leader)->assertForbidden();

        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager');
        $this->actingAs($sales)->get(route('seating.show', $this->trip))->assertOk();
        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '1']], $sales)->assertForbidden();
        $this->assertNull($this->seat('Maria'));

        // The leader sees phones and meals but never payment details.
        $state = $this->actingAs($leader)->get(route('seating.show', $this->trip))->viewData('state');
        $this->assertArrayNotHasKey('payment', $state['passengers'][0]);
        $this->assertArrayNotHasKey('bookingUrl', $state['passengers'][0]);
        $this->assertArrayHasKey('payment', Seating::state($this->trip, $this->admin)['passengers'][0]);
    }

    public function test_buses_keep_their_own_copy_and_only_an_empty_bus_can_be_removed(): void
    {
        $coach = VehicleLayout::where('name', 'Coach (56 seats)')->firstOrFail();
        $coach->update(['layout' => VehicleLayout::generate(1, 1, 1), 'seat_count' => 2]);
        $this->assertCount(56, $this->bus->fresh()->seatLabels());

        $this->place([['p' => $this->p['Maria']->id, 'v' => $this->bus->id, 's' => '20']]);
        $this->actingAs($this->admin)->delete(route('departure-vehicles.destroy', [$this->trip, $this->bus]))->assertSessionHasErrors('vehicle');
        $this->assertModelExists($this->bus);
        $this->place([['p' => $this->p['Maria']->id, 'v' => null, 's' => null]]);
        $this->delete(route('departure-vehicles.destroy', [$this->trip, $this->bus]))->assertSessionHasNoErrors();
        $this->assertModelMissing($this->bus);
    }

    public function test_dates_with_buses_are_seated_on_the_board_and_others_still_refuse_duplicate_seat_numbers(): void
    {
        $booking = $this->p['Bruno']->booking;
        $this->actingAs($this->admin)->put(route('bookings.seats', $booking), ['seats' => [$this->p['Bruno']->id => '9']])->assertRedirect(route('seating.show', $this->trip));
        $this->assertNull($this->p['Bruno']->fresh()->seat_number);

        $this->bus->delete();
        $this->put(route('bookings.seats', $this->p['Maria']->booking), ['seats' => [$this->p['Maria']->id => '9', $this->p['Leo']->id => '9']])->assertSessionHasErrors('seats');
        $this->put(route('bookings.seats', $this->p['Maria']->booking), ['seats' => [$this->p['Maria']->id => '9']])->assertSessionHasNoErrors();
        $this->put(route('bookings.seats', $booking), ['seats' => [$this->p['Bruno']->id => '9']])->assertSessionHasErrors('seats');
        $this->assertSame('9', Roster::columns($this->admin, true)['Seat']($this->p['Maria']->fresh(), $this->p['Maria']->booking));
    }

    private function payInFull(Booking $booking): void
    {
        $booking->forceFill(['payment_status' => 'paid'])->saveQuietly();
    }

    public function test_draft_changes_never_email_guests_and_publishing_emails_only_new_or_changed_seats(): void
    {
        Mail::fake();
        $family = $this->p['Maria']->booking;
        $bruno = $this->p['Bruno']->booking;
        $this->payInFull($family);
        $this->actingAs($this->admin)->postJson(route('seating.autofill', $this->trip), [])->assertOk()->assertJsonPath('state.publish.draft', true)->assertJsonPath('state.publish.ready', 1);

        // Draft: neither the booking page button nor the daily job may send.
        $this->assertSame('The seating for this date is a draft. Publish it on the seating board first.', \App\Support\SeatNotices::blocker($family->fresh()));
        $this->post(route('bookings.seat-notice', $family))->assertSessionHasErrors('seats');
        $this->travelTo($this->trip->departs_at->copy()->subDay());
        $this->artisan('bookings:send-seat-notices')->assertSuccessful();
        Mail::assertNothingQueued();

        // Publishing without the box ticked tells nobody.
        $this->post(route('seating.publish', $this->trip))->assertRedirect()->assertSessionHas('message', 'Seating published. No emails were sent.');
        Mail::assertNothingQueued();
        $this->assertNotNull($this->bus->fresh()->published_at);

        // With the box: only the fully paid family; Bruno waits until he has paid.
        $this->post(route('seating.publish', $this->trip), ['notify' => 1])->assertSessionHas('message', 'Seating published. Seat numbers emailed to 1 booking. 1 will get theirs once fully paid and seated.');
        Mail::assertQueued(SeatAssignment::class, 1);
        Mail::assertQueued(SeatAssignment::class, fn ($m) => $m->hasTo($family->email));
        $this->assertSame('Bus 1 · 1', $this->p['Leo']->fresh()->seat_notified);

        // Publishing again with nothing changed sends nothing new; moving one family member makes them due again.
        $this->post(route('seating.publish', $this->trip), ['notify' => 1]);
        Mail::assertQueued(SeatAssignment::class, 1);
        $this->place([['p' => $this->p['Ana']->id, 'v' => $this->bus->id, 's' => '30']])->assertJsonPath('state.publish.draft', true);
        $this->payInFull($bruno);
        $this->post(route('seating.publish', $this->trip), ['notify' => 1])->assertSessionHas('message', 'Seating published. Seat numbers emailed to 2 bookings.');
        Mail::assertQueued(SeatAssignment::class, 3);
        $this->assertSame([null, '2'], ActivityLog::where('action', 'seating_published')->latest('id')->first()->changes['seat emails']);
    }

    public function test_the_seat_chart_exports_for_office_and_assigned_staff_only(): void
    {
        $this->actingAs($this->admin)->postJson(route('seating.autofill', $this->trip), []);
        $pdf = $this->get(route('seating.export', [$this->trip, 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $xlsx = $this->get(route('seating.export', [$this->trip, 'xlsx']))->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
        $this->get(route('seating.export', [$this->trip, 'doc']))->assertNotFound();

        $stranger = User::factory()->create();
        $stranger->assignRole('Tour Leader');
        $this->actingAs($stranger)->get(route('seating.export', [$this->trip, 'pdf']))->assertForbidden();
        DepartureStaff::create(['tour_departure_id' => $this->trip->id, 'user_id' => $stranger->id, 'role' => 'driver']);
        $this->actingAs($stranger)->get(route('seating.export', [$this->trip, 'pdf']))->assertOk();
        $this->assertSame(3, ActivityLog::where('action', 'seating_exported')->count());
    }

    public function test_vehicles_page_lists_upcoming_dates_by_what_needs_doing(): void
    {
        $mk = fn (string $when, string $status = 'available') => TourDeparture::create(['tour_id' => $this->trip->tour_id, 'departs_at' => now()->modify($when), 'price' => 150, 'currency' => 'CAD', 'capacity' => 10, 'reserved_seats' => 0, 'status' => $status]);
        $noBus = $mk('+10 days');
        $b = Booking::create(['tour_id' => $noBus->tour_id, 'tour_departure_id' => $noBus->id, 'type' => 'tour', 'status' => 'new', 'first_name' => 'Zoe', 'last_name' => 'Zed', 'email' => 'z@example.com', 'phone' => '416-555-0199', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '150.00', 'deposit_amount' => '0.00']);
        BookingPassenger::create(['booking_id' => $b->id, 'first_name' => 'Zoe', 'last_name' => 'Zed', 'category' => 'adult']);
        $empty = $mk('+20 days');
        $mk('-2 days');
        $mk('+3 days', 'cancelled');

        $page = $this->actingAs($this->admin)->get(route('vehicle-layouts.index'))->assertOk();
        $rows = $page->viewData('departures');
        $this->assertSame([$noBus->id, $this->trip->id], $rows->pluck('id')->all(), 'no bus first, then seats to assign; empty, past and cancelled dates hidden');
        $page->assertSeeInOrder(['0 / 1', 'No bus yet', '0 / 5', 'Seats to assign']); // table rows, after the summary tiles
        $this->assertSame([$noBus->id, $this->trip->id, $empty->id], $this->get(route('vehicle-layouts.index', ['all' => 1]))->viewData('departures')->pluck('id')->all());

        $this->postJson(route('seating.autofill', $this->trip), []);
        $this->get(route('vehicle-layouts.index'))->assertSeeInOrder(['0 / 1', 'No bus yet', '5 / 5', 'Draft']);
        $this->post(route('seating.publish', $this->trip));
        $this->get(route('vehicle-layouts.index'))->assertSeeInOrder(['0 / 1', 'No bus yet', '5 / 5', 'Published']);

        // People without booking access see the templates only.
        $content = User::factory()->create();
        $content->assignRole('Content Manager');
        $content->givePermissionTo('list-vehicle-layouts');
        $this->assertNull($this->actingAs($content)->get(route('vehicle-layouts.index'))->assertOk()->assertDontSee('Upcoming departures')->viewData('departures'));
    }
}
