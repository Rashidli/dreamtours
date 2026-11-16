<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourDeparturePrice;
use App\Models\User;
use App\Support\BookingMoney;
use App\Support\Roster;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §6 staff bookings, moving or cancelling one passenger; §8 / AT-05 capacity override. Every amount to the cent. */
class BookingControlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tour $tour;
    private TourDeparture $a;
    private TourDeparture $b;
    private PickupPoint $union;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'n-'.uniqid()]]);
        $this->a = $this->departure('+10 days', 4, ['adult' => 120.50, 'child' => 80, 'infant' => 0]);
        $this->b = $this->departure('+17 days', 10, ['adult' => 130, 'child' => 85, 'infant' => 0]);
        $this->union = PickupPoint::create(['address' => '65 Front St W', 'is_active' => true, 'en' => ['name' => 'Union Station']]);
        $this->tour->pickupPoints()->sync([$this->union->id]);
    }

    private function departure(string $when, int $capacity, array $fares): TourDeparture
    {
        $d = TourDeparture::create(['tour_id' => $this->tour->id, 'departs_at' => now()->modify($when)->setTime(8, 0), 'price' => $fares['adult'], 'currency' => 'CAD', 'capacity' => $capacity, 'reserved_seats' => 0, 'status' => 'available']);
        $i = 0;
        foreach ($fares as $code => $price) {
            TourDeparturePrice::create(['tour_departure_id' => $d->id, 'code' => $code, 'price' => $price, 'sort_order' => $i++]);
        }

        return $d;
    }

    private function newBooking(array $passengers, array $extra = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)->post(route('bookings.store'), $extra + [
            'first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'Maria@Example.com', 'phone' => '416-555-0101',
            'tour_departure_id' => $this->a->id, 'status' => 'payment_pending',
            'passengers' => array_map(fn ($p) => $p + ['last_name' => 'Lopez', 'pickup_point_id' => $this->union->id], $passengers),
        ]);
    }

    private function family(): Booking
    {
        $this->newBooking([['first_name' => 'Maria', 'fare' => 'adult'], ['first_name' => 'Leo', 'fare' => 'child'], ['first_name' => 'Ana', 'fare' => 'adult']])->assertSessionHasNoErrors();

        return Booking::latest('id')->firstOrFail();
    }

    public function test_staff_create_a_booking_priced_from_the_dates_fares_and_linked_to_the_existing_customer(): void
    {
        $existing = Customer::create(['first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com', 'phone' => '416-555-0101']);
        $this->actingAs($this->admin)->get(route('bookings.create', ['customer' => $existing->id]))->assertOk()->assertSee('Linked to customer')->assertSee('Niagara Falls');
        $this->getJson(route('bookings.customer-lookup', ['q' => 'lope']))->assertOk()->assertJsonPath('results.0.id', $existing->id);

        Mail::fake();
        $this->newBooking([['first_name' => 'Maria', 'fare' => 'adult'], ['first_name' => 'Leo', 'fare' => 'child'], ['first_name' => 'Baby', 'fare' => 'infant']], ['customer_id' => $existing->id, 'internal_notes' => 'Phoned in'])->assertRedirect();
        $b = Booking::latest('id')->firstOrFail();
        $this->assertSame([$existing->id, 'admin', 'payment_pending', '200.50', 3, 2], [$b->customer_id, $b->source, $b->status, (string) $b->total_amount, $b->participant_count, $b->seat_count], 'lap infant takes no seat');
        $this->assertSame(['adult' => '120.50', 'child' => '80.00', 'infant' => '0.00'], $b->passengers()->orderBy('id')->get()->mapWithKeys(fn ($p) => [$p->price_code => (string) $p->price])->all());
        $this->assertSame('200.50', (string) $b->fresh()->outstanding_amount);
        $this->assertSame(2, $this->a->fresh()->reserved_seats);
        $this->assertSame(0, Lead::count(), 'staff bookings are not website inquiries');
        Mail::assertNothingQueued();
        $this->assertSame(1, Customer::count());

        // Confirmation email only when asked.
        $this->newBooking([['first_name' => 'Sam', 'fare' => 'adult']], ['email' => 'sam@example.com', 'status' => 'confirmed', 'send_confirmation' => 1])->assertRedirect();
        Mail::assertQueued(\App\Mail\BookingConfirmed::class, 1);
    }

    public function test_bad_fares_and_pickups_are_refused_and_permissions_apply(): void
    {
        $other = PickupPoint::create(['address' => 'x', 'is_active' => true, 'en' => ['name' => 'Elsewhere']]);
        $this->newBooking([['first_name' => 'Maria', 'fare' => 'senior']])->assertSessionHasErrors('passengers.0.fare');
        $this->newBooking([['first_name' => 'Maria', 'fare' => 'adult', 'pickup_point_id' => $other->id]])->assertSessionHasErrors('passengers.0.pickup_point_id');
        $this->newBooking([['first_name' => 'Baby', 'fare' => 'infant']])->assertSessionHasErrors('passengers');
        $this->assertSame(0, Booking::count());

        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager');
        $this->newBooking([['first_name' => 'Maria', 'fare' => 'adult']], [], $sales)->assertSessionHasNoErrors();
        $content = User::factory()->create();
        $content->assignRole('Content Manager');
        $this->actingAs($content)->get(route('bookings.create'))->assertForbidden();
    }

    public function test_overbooking_is_blocked_unless_an_authorised_override_with_a_reason_is_recorded(): void
    {
        $sales = User::factory()->create(['name' => 'Sam Sales']);
        $sales->assignRole('Sales Manager');
        $five = array_map(fn ($n) => ['first_name' => "P$n", 'fare' => 'adult'], range(1, 5));

        $this->newBooking($five, ['override_reason' => 'please'], $sales)->assertSessionHasErrors('capacity');
        $this->newBooking($five)->assertSessionHasErrors(['capacity' => 'Only 4 seats left on this date; 5 needed. To book anyway, tick “Book over capacity” and give a reason.']);
        $this->assertSame(0, Booking::count());

        $this->newBooking($five, ['override_reason' => 'Second minivan booked'])->assertSessionHasNoErrors();
        $this->assertSame(5, $this->a->fresh()->reserved_seats);
        $log = ActivityLog::where('action', 'capacity_override')->sole();
        $this->assertSame(['Second minivan booked', $this->admin->id, ['4 left', '5 booked']], [$log->reason, $log->actor_id, $log->changes['seats']]);

        // Lead conversion follows the same rule (no hidden "allow_over" any more).
        $lead = Lead::create(['lead_stage_id' => LeadStage::where('kind', 'open')->orderBy('sort_order')->first()->id, 'first_name' => 'Walk', 'last_name' => 'In', 'email' => 'w@example.com', 'source' => 'phone', 'type' => 'tour']);
        $this->actingAs($sales)->post(route('leads.convert', $lead), ['tour_departure_id' => $this->a->id, 'guests' => 1, 'allow_over' => 1])->assertSessionHasErrors('capacity');
        $this->assertNull($lead->fresh()->booking_id);
    }

    public function test_moving_one_passenger_splits_them_onto_the_new_date_and_recalculates_both_bookings(): void
    {
        $family = $this->family(); // 120.50 + 80 + 120.50 = 321.00
        BookingMoney::record($family, ['amount' => '200.00', 'method' => 'cash'], $this->admin, true);
        $leo = $family->passengers()->where('first_name', 'Leo')->firstOrFail();

        $this->actingAs($this->admin)->post(route('booking-passengers.move', [$family, $leo]), ['tour_departure_id' => $this->b->id, 'reason' => 'School trip that day'])->assertRedirect(route('bookings.show', $family));
        $family->refresh();
        $new = Booking::where('split_from_booking_id', $family->id)->sole();

        $this->assertSame(['241.00', 2, 2, '200.00', '41.00'], [(string) $family->total_amount, $family->participant_count, $family->seat_count, (string) $family->amount_paid, (string) $family->outstanding_amount]);
        $this->assertSame([$this->b->id, 'payment_pending', '85.00', '85.00', 1, $family->customer_id], [$new->tour_departure_id, $new->status, (string) $new->total_amount, (string) $new->outstanding_amount, $new->participant_count, $new->customer_id]);
        $this->assertSame($new->id, $leo->fresh()->booking_id, 'same passenger record, now on the new booking');
        $this->assertSame([2, 1], [$this->a->fresh()->reserved_seats, $this->b->fresh()->reserved_seats]);
        $this->assertSame(['Maria Lopez', 'Ana Lopez'], Roster::all($this->a->fresh())->map(fn ($r) => $r['passenger']->first_name.' '.$r['passenger']->last_name)->values()->all());
        $this->assertSame('School trip that day', ActivityLog::where('action', 'passenger_moved')->value('reason'));
        $this->get(route('bookings.show', $family))->assertOk()->assertSee('Passengers split off')->assertSee($new->reference);
        $this->get(route('bookings.show', $new))->assertOk()->assertSee('Split from')->assertSee($family->reference);
    }

    public function test_a_single_passenger_booking_moves_as_a_whole_and_keeps_its_payments(): void
    {
        $this->newBooking([['first_name' => 'Solo', 'fare' => 'adult']])->assertSessionHasNoErrors();
        $solo = Booking::latest('id')->firstOrFail();
        BookingMoney::record($solo, ['amount' => '50.00', 'method' => 'cash'], $this->admin, true);
        $this->actingAs($this->admin)->post(route('booking-passengers.move', [$solo, $solo->passengers()->first()]), ['tour_departure_id' => $this->b->id, 'reason' => 'Rescheduled'])->assertRedirect();
        $solo->refresh();
        $this->assertSame([$this->b->id, '130.00', '50.00', '80.00'], [$solo->tour_departure_id, (string) $solo->total_amount, (string) $solo->amount_paid, (string) $solo->outstanding_amount]);
        $this->assertSame([0, 1], [$this->a->fresh()->reserved_seats, $this->b->fresh()->reserved_seats]);
        $this->assertSame(0, Booking::whereNotNull('split_from_booking_id')->count());
    }

    public function test_cancelling_one_passenger_keeps_the_others_and_the_record(): void
    {
        $family = $this->family();
        BookingMoney::record($family, ['amount' => '321.00', 'method' => 'cash'], $this->admin, true);
        $ana = $family->passengers()->where('first_name', 'Ana')->firstOrFail();

        $this->actingAs($this->admin)->post(route('booking-passengers.cancel', [$family, $ana]), ['reason' => 'Ill'])->assertRedirect();
        $family->refresh();
        $kept = Booking::where('split_from_booking_id', $family->id)->sole();
        $this->assertSame(['cancelled', '120.50', '0.00'], [$kept->status, (string) $kept->total_amount, (string) $kept->outstanding_amount]);
        $this->assertSame(['200.50', 2, '0.00', '120.50'], [(string) $family->total_amount, $family->participant_count, (string) $family->outstanding_amount, BookingMoney::summary($family)['credit']], 'overpaid by Ana\'s fare: a credit to refund');
        $this->assertSame(2, $this->a->fresh()->reserved_seats);
        $this->assertTrue(BookingPassenger::whereKey($ana->id)->exists(), 'nothing deleted');

        // The last passenger cannot be cancelled alone, and the reason is required.
        $this->post(route('booking-passengers.cancel', [$family, $family->passengers()->first()]), [])->assertSessionHasErrors('reason');
        $solo = Booking::create(['tour_id' => $this->tour->id, 'tour_departure_id' => $this->a->id, 'type' => 'tour', 'status' => 'new', 'first_name' => 'S', 'last_name' => 'Solo', 'email' => 's@example.com', 'phone' => '1', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '120.50', 'deposit_amount' => '0.00']);
        $p = BookingPassenger::create(['booking_id' => $solo->id, 'first_name' => 'S', 'last_name' => 'Solo', 'category' => 'adult']);
        $this->post(route('booking-passengers.cancel', [$solo, $p]), ['reason' => 'x'])->assertSessionHasErrors('cancel');

        // A move into a full date needs the override like any booking.
        $full = $this->departure('+20 days', 0, ['adult' => 100]);
        $leo = $family->passengers()->where('first_name', 'Leo')->firstOrFail();
        $this->post(route('booking-passengers.move', [$family, $leo]), ['tour_departure_id' => $full->id, 'reason' => 'x'])->assertSessionHasErrors('capacity');
        $this->assertSame($family->id, $leo->fresh()->booking_id);
    }
}
