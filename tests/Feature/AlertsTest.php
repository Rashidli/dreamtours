<?php

namespace Tests\Feature;

use App\Mail\SeatAssignment;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Setting;
use App\Models\StaffAlert;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\Alerts;
use App\Support\BookingMoney;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §15 — in-app staff alerts: raised once per event, linked, closed by hand or automatically, per permission. */
class AlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TourDeparture $trip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'niagara-'.uniqid()]]);
        $this->trip = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(10)->setTime(8, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 8, 'reserved_seats' => 0, 'status' => 'available']);
    }

    private function booking(int $pax = 1, array $extra = []): Booking
    {
        $b = Booking::create(['tour_id' => $this->trip->tour_id, 'tour_departure_id' => $this->trip->id, 'type' => 'tour', 'status' => 'new', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com', 'phone' => '416-555-0101', 'participant_count' => $pax, 'seat_count' => $pax, 'currency' => 'CAD', 'total_amount' => (string) (100 * $pax).'.00', 'deposit_amount' => '0.00'] + $extra);
        foreach (range(1, $pax) as $i) {
            BookingPassenger::create(['booking_id' => $b->id, 'first_name' => 'P'.$i, 'last_name' => 'Lopez', 'category' => 'adult']);
        }
        $this->trip->recountSeats();

        return $b->fresh();
    }

    private function open(string $type): int
    {
        return StaffAlert::open()->where('type', $type)->count();
    }

    public function test_website_inquiries_alert_once_and_close_when_the_lead_moves_on(): void
    {
        $new = LeadStage::where('kind', 'open')->orderBy('sort_order')->first();
        $lead = Lead::create(['lead_stage_id' => $new->id, 'first_name' => 'Web', 'last_name' => 'Visitor', 'email' => 'web@example.com', 'source' => 'website', 'type' => 'tour', 'tour_id' => $this->trip->tour_id, 'guests' => 3]);
        $alert = StaffAlert::where('type', 'new_inquiry')->sole();
        $this->assertSame('New inquiry: Web Visitor', $alert->title);
        $this->assertSame(route('leads.show', $lead), $alert->url);
        $this->assertStringContainsString('3 guests', $alert->body);

        // Staff typing in a lead are not alerted about their own entry.
        $this->actingAs($this->admin);
        Lead::create(['lead_stage_id' => $new->id, 'first_name' => 'Phone', 'last_name' => 'Call', 'email' => 'p@example.com', 'source' => 'phone', 'type' => 'tour']);
        $this->assertSame(1, $this->open('new_inquiry'));

        $lead->update(['lead_stage_id' => LeadStage::where('kind', 'open')->orderBy('sort_order')->skip(2)->first()->id]);
        $this->assertSame(0, $this->open('new_inquiry'));
        $this->assertSame('Moved to Contacted', $alert->fresh()->completed_note);
        $this->assertSame($this->admin->id, $alert->fresh()->completed_by);
    }

    public function test_a_pending_payment_alerts_and_confirming_it_closes_the_alert(): void
    {
        $b = $this->booking();
        $p = BookingMoney::record($b, ['amount' => '40.00', 'method' => 'etransfer'], null, false);
        $this->assertSame(['CA$40 to verify — '.$b->reference], StaffAlert::open()->where('type', 'payment_pending')->pluck('title')->all());
        Alerts::raise('payment_pending', "payment:{$p->id}", 'again');
        $this->assertSame(1, StaffAlert::where('type', 'payment_pending')->count(), 'same event, one alert');

        BookingMoney::confirm($p, $this->admin);
        $this->assertSame(0, $this->open('payment_pending'));
        $this->assertSame('Payment confirmed', StaffAlert::where('type', 'payment_pending')->value('completed_note'));
    }

    public function test_capacity_cancellations_contact_requests_and_failed_emails(): void
    {
        Setting::updateOrCreate(['key' => 'alert_almost_full_seats'], ['value' => '3']);
        Cache::forget('settings');
        $a = $this->booking(4);
        $this->assertSame(0, $this->open('tour_almost_full'), '4 of 8 left is above the threshold');
        $b = $this->booking(2);
        $this->assertSame(['Almost full: Niagara Falls · '.$this->trip->departs_at->format('D, M j')], StaffAlert::open()->where('type', 'tour_almost_full')->pluck('title')->all());
        $this->booking(2);
        $this->assertSame(1, $this->open('tour_full'));

        $this->actingAs($this->admin)->put(route('bookings.update', $b), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(1, $this->open('booking_cancelled'));
        $this->assertSame(0, $this->open('tour_full'), 'seats freed up');
        $this->assertSame('Seats freed up', StaffAlert::where('type', 'tour_full')->value('completed_note'));

        $c = Contact::create(['name' => 'Ola', 'phone' => '416-555-0000', 'message' => 'Do you go to Ottawa?', 'is_read' => false]);
        $this->assertSame(1, $this->open('support_request'));
        $c->update(['is_read' => true]);
        $this->assertSame(0, $this->open('support_request'));

        (new SeatAssignment($a))->failed(new \RuntimeException('Mailbox full'));
        $failed = StaffAlert::where('type', 'email_failed')->sole();
        $this->assertSame(route('bookings.show', $a), $failed->url);
        $this->assertStringContainsString('Mailbox full', $failed->body);
    }

    public function test_time_based_alerts_come_from_the_sweep_without_duplicates_and_close_when_paid(): void
    {
        $b = $this->booking();
        $b->forceFill(['balance_due_date' => now('America/Toronto')->addDays(2)->toDateString()])->saveQuietly();
        BookingMoney::sync($b->fresh());
        $soonTrip = TourDeparture::create(['tour_id' => $this->trip->tour_id, 'departs_at' => now()->addDay(), 'price' => 100, 'currency' => 'CAD', 'capacity' => 20, 'reserved_seats' => 0, 'status' => 'available']);
        Booking::create(['tour_id' => $soonTrip->tour_id, 'tour_departure_id' => $soonTrip->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Sam', 'last_name' => 'Soon', 'email' => 's@example.com', 'phone' => '1', 'participant_count' => 1, 'seat_count' => 1, 'currency' => 'CAD', 'total_amount' => '100.00', 'deposit_amount' => '0.00']);

        $this->artisan('crm:alerts')->assertSuccessful();
        Alerts::sweep();
        // Maria's balance is due in 2 days; Sam's trip leaves tomorrow, so his balance is due today.
        $this->assertSame(2, $this->open('balance_due_soon'));
        $this->assertSame(1, $this->open('upcoming_tour'));
        $this->assertStringStartsWith('Leaving tomorrow: Niagara Falls', StaffAlert::where('type', 'upcoming_tour')->value('title'));

        BookingMoney::record($b, ['amount' => '100.00', 'method' => 'cash'], $this->admin, true);
        Alerts::sweep();
        $this->assertSame(1, $this->open('balance_due_soon'));
        $this->assertSame('Paid or cancelled', StaffAlert::where('type', 'balance_due_soon')->where('subject_id', $b->id)->value('completed_note'));
    }

    public function test_people_see_and_close_only_the_alerts_they_can_act_on_and_types_can_be_switched_off(): void
    {
        Cache::put('staff-alerts-sweep', 1, 600); // time-based alerts are covered above; keep this test to event alerts
        $b = $this->booking();
        BookingMoney::record($b, ['amount' => '40.00', 'method' => 'etransfer'], null, false);
        Contact::create(['name' => 'Ola', 'phone' => '1', 'message' => 'Hi', 'is_read' => false]);

        $this->actingAs($this->admin)->get(route('home'))->assertOk()->assertSee('aria-label="Alerts (2 open)"', false);
        $this->get(route('alerts.dropdown'))->assertOk()->assertSee('CA$40 to verify')->assertSee('Contact request from Ola');
        $this->get(route('alerts.index'))->assertOk()->assertSee('CA$40 to verify')->assertSee('Contact request from Ola');

        $content = User::factory()->create();
        $content->assignRole('Content Manager');
        $this->actingAs($content)->get(route('alerts.index'))->assertOk()->assertDontSee('CA$40 to verify')->assertDontSee('Contact request from Ola');
        $payment = StaffAlert::where('type', 'payment_pending')->sole();
        $this->post(route('alerts.complete', $payment))->assertForbidden();
        $this->post(route('alerts.complete-all'))->assertRedirect();
        $this->assertSame(2, StaffAlert::open()->count(), 'mark-all only touches what you can see');
        $this->post(route('alerts.settings'), ['on' => []])->assertForbidden();

        $this->actingAs($this->admin)->post(route('alerts.complete', $payment))->assertRedirect();
        $this->assertSame($this->admin->id, $payment->fresh()->completed_by);
        $this->get(route('alerts.index', ['show' => 'done']))->assertSee('Marked done')->assertSee('by Jenn Manager');

        // Switch payment alerts off: new pending payments no longer alert.
        $this->post(route('alerts.settings'), ['on' => array_values(array_diff(array_keys(Alerts::TYPES), ['payment_pending']))])->assertRedirect();
        Cache::forget('settings');
        BookingMoney::record($b, ['amount' => '10.00', 'method' => 'cash'], null, false);
        $this->assertSame(1, StaffAlert::where('type', 'payment_pending')->count());
    }
}
