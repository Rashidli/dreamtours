<?php

namespace Tests\Feature;

use App\Mail\WaitlistSeatAvailable;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Customer;
use App\Models\StaffAlert;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Support\Waitlist;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §8 — waitlist with order and an alert when a seat becomes available. */
class WaitlistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tour $tour;
    private TourDeparture $full;
    private Booking $taken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'niagara-falls']]);
        $this->full = TourDeparture::create(['tour_id' => $this->tour->id, 'departs_at' => now()->addDays(12)->setTime(8, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 3, 'reserved_seats' => 0, 'status' => 'available']);
        $this->taken = $this->booking(2, 'Olga');
        $this->booking(1, 'Pat');
    }

    private function booking(int $pax, string $first): Booking
    {
        $b = Booking::create(['tour_id' => $this->tour->id, 'tour_departure_id' => $this->full->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => $first, 'last_name' => 'Test', 'email' => strtolower($first).'@example.com', 'phone' => '416-555-0101', 'participant_count' => $pax, 'seat_count' => $pax, 'currency' => 'CAD', 'total_amount' => (100 * $pax).'.00', 'deposit_amount' => '0.00']);
        foreach (range(1, $pax) as $i) {
            BookingPassenger::create(['booking_id' => $b->id, 'first_name' => $first.$i, 'last_name' => 'Test', 'category' => 'adult']);
        }

        return $b;
    }

    private function join(array $data = [])
    {
        return $this->post(route('front.waitlist.store', ['slug' => 'niagara-falls', 'departure' => $this->full->id]), $data + ['first_name' => 'Wendy', 'last_name' => 'Wait', 'email' => 'wendy@example.com', 'phone' => '647-555-0100', 'guests' => 2]);
    }

    public function test_guests_join_the_waitlist_of_a_full_date_from_the_tour_page(): void
    {
        $this->assertSame('sold_out', $this->full->fresh()->public_status);
        $this->get('/tours/niagara-falls')->assertOk()->assertSee('Sold out — join the waitlist');
        $this->get(route('front.waitlist.create', ['slug' => 'niagara-falls', 'departure' => $this->full->id]))->assertOk()->assertSee('Join the waitlist');

        $this->join()->assertRedirect()->assertSessionHas('waitlist_joined', 'Wendy');
        $entry = WaitlistEntry::sole();
        $this->assertSame(['waiting', 1, 2, 'website'], [$entry->status, $entry->position, $entry->guests, $entry->source]);
        $this->assertSame('wendy@example.com', Customer::findOrFail($entry->customer_id)->email);
        $this->assertSame('Waitlist: Wendy Wait (2)', StaffAlert::where('type', 'waitlist_joined')->value('title'));

        $this->join(['email' => 'WENDY@example.com'])->assertSessionHasErrors('email');
        $this->join(['email' => 'bot@example.com', 'website' => 'http://spam'])->assertRedirect();
        $this->assertSame(1, WaitlistEntry::count(), 'no duplicate, honeypot ignored');

        // A date with seats left has no waitlist: the page sends people to book instead.
        $open = TourDeparture::create(['tour_id' => $this->tour->id, 'departs_at' => now()->addDays(20), 'price' => 100, 'currency' => 'CAD', 'capacity' => 10, 'reserved_seats' => 0, 'status' => 'available']);
        $this->get(route('front.waitlist.create', ['slug' => 'niagara-falls', 'departure' => $open->id]))->assertRedirect(route('front.tours.show', 'niagara-falls'));
        $this->post(route('front.waitlist.store', ['slug' => 'niagara-falls', 'departure' => $open->id]), ['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.com', 'phone' => '1', 'guests' => 1])->assertRedirect(route('front.tours.show', 'niagara-falls'));
        $this->assertSame(1, WaitlistEntry::count());
    }

    public function test_freed_seats_alert_about_the_first_person_whose_party_fits_and_the_order_can_change(): void
    {
        $three = Waitlist::add($this->full, ['first_name' => 'Big', 'last_name' => 'Group', 'email' => 'big@example.com', 'guests' => 3], $this->admin);
        $one = Waitlist::add($this->full, ['first_name' => 'Solo', 'last_name' => 'Traveller', 'phone' => '416-555-0199', 'guests' => 1], $this->admin);
        $this->assertSame([1, 2], [$three->position, $one->position]);
        $this->assertSame(0, StaffAlert::where('type', 'waitlist_joined')->count(), 'staff entries do not alert');

        // Pat (1 seat) cancels: 1 seat free; Big Group needs 3, so Solo is next.
        $pat = Booking::where('first_name', 'Pat')->firstOrFail();
        $this->actingAs($this->admin)->put(route('bookings.update', $pat), ['status' => 'cancelled'])->assertRedirect();
        $alert = StaffAlert::where('type', 'waitlist_seat_free')->sole();
        $this->assertSame('Seat free for Solo Traveller (1) on the waitlist', $alert->title);
        $this->assertSame(route('waitlist.index', ['departure' => $this->full->id]), $alert->url);
        $this->get(route('waitlist.index'))->assertOk()->assertSeeInOrder(['Niagara Falls', '1 seat free now', 'Big Group', 'Solo Traveller']);

        $this->post(route('waitlist.move', $one), ['direction' => 'up'])->assertRedirect();
        $this->assertSame([$one->id, $three->id], WaitlistEntry::active()->pluck('id')->all());
    }

    public function test_offer_by_email_then_book_from_the_waitlist_closes_the_entry(): void
    {
        Mail::fake();
        $entry = Waitlist::add($this->full, ['first_name' => 'Wendy', 'last_name' => 'Wait', 'email' => 'wendy@example.com', 'phone' => '647-555-0100', 'guests' => 1], null);
        $this->actingAs($this->admin)->put(route('bookings.update', Booking::where('first_name', 'Pat')->firstOrFail()), ['status' => 'cancelled']);
        $this->assertSame(1, StaffAlert::open()->where('type', 'waitlist_seat_free')->count());

        $this->post(route('waitlist.offer', $entry), ['email' => 1])->assertRedirect();
        Mail::assertQueued(WaitlistSeatAvailable::class, fn ($m) => $m->hasTo('wendy@example.com'));
        $this->assertSame('offered', $entry->fresh()->status);
        $this->assertSame(0, StaffAlert::open()->where('type', 'waitlist_seat_free')->count(), 'offering closes the alert');

        $this->get(route('bookings.create', ['waitlist' => $entry->id]))->assertOk()->assertSee('from the waitlist')->assertSee('value="wendy@example.com"', false);
        $this->post(route('bookings.store'), [
            'waitlist_entry_id' => $entry->id, 'first_name' => 'Wendy', 'last_name' => 'Wait', 'email' => 'wendy@example.com', 'phone' => '647-555-0100',
            'tour_departure_id' => $this->full->id, 'status' => 'payment_pending', 'passengers' => [['first_name' => 'Wendy', 'last_name' => 'Wait', 'fare' => 'adult']],
        ])->assertSessionHasNoErrors();
        $booking = Booking::latest('id')->firstOrFail();
        $this->assertSame(['booked', $booking->id], [$entry->fresh()->status, $entry->fresh()->booking_id]);
        $this->assertSame($entry->customer_id, $booking->customer_id);
        $this->get(route('waitlist.index', ['show' => 'closed']))->assertSee($booking->reference);
    }

    public function test_removing_needs_a_reason_and_only_booking_staff_manage_the_waitlist(): void
    {
        $entry = Waitlist::add($this->full, ['first_name' => 'Wendy', 'last_name' => 'Wait', 'email' => 'wendy@example.com', 'guests' => 1], null);
        $this->actingAs($this->admin)->post(route('waitlist.remove', $entry), [])->assertSessionHasErrors('reason');
        $this->post(route('waitlist.remove', $entry), ['reason' => 'Found another tour'])->assertRedirect();
        $this->assertSame(['removed', 'Found another tour'], [$entry->fresh()->status, $entry->fresh()->removed_reason]);

        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant'); // sees bookings, cannot edit them
        $this->actingAs($accountant)->get(route('waitlist.index'))->assertOk()->assertDontSee('Add to waitlist');
        $this->post(route('waitlist.store'), ['tour_departure_id' => $this->full->id, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'guests' => 1])->assertForbidden();
        $content = User::factory()->create();
        $content->assignRole('Content Manager');
        $this->actingAs($content)->get(route('waitlist.index'))->assertForbidden();
    }
}
