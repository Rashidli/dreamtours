<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\PickupPoint;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\Activity;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM requirements §22 — activity history, audit log and accountability. */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(string $name = 'Jenn Manager'): User
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('superadmin');
        $this->actingAs($user);

        return $user;
    }

    private function departure(): TourDeparture
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Day Trip', 'slug' => 'niagara-day-trip']]);

        return TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addMonth(), 'price' => 105, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
    }

    private function bookingFromWebsite(TourDeparture $departure): Booking
    {
        $this->post(route('front.bookings.store'), ['tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'phone' => '416-555-0101', 'email' => 'maria@example.com', 'participant_count' => 2, 'passengers' => [['first_name' => 'Maria', 'last_name' => 'Lopez', 'category' => 'adult'], ['first_name' => 'Leo', 'last_name' => 'Lopez', 'category' => 'child']]])->assertRedirect();

        return Booking::firstOrFail();
    }

    public function test_price_change_records_actor_role_old_and_new_value(): void
    {
        $departure = $this->departure();
        $this->superadmin();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7']);
        $this->put(route('tour-departures.update', [$departure->tour_id, $departure]), ['departs_at' => $departure->departs_at->format('Y-m-d H:i'), 'price' => 129, 'currency' => 'CAD', 'capacity' => 50, 'status' => 'available']);

        $log = ActivityLog::where(['subject_type' => TourDeparture::class, 'action' => 'updated'])->latest('id')->firstOrFail();
        $this->assertSame(['105.00', '129'], array_map('strval', $log->changes['price']));
        $this->assertSame(['Jenn Manager', 'superadmin', 'user', 'admin'], [$log->actor_name, $log->actor_role, $log->actor_type, $log->origin]);
        $this->assertStringStartsWith('Niagara Day Trip — ', $log->subject_label);
        $this->assertArrayNotHasKey('updated_at', $log->changes);
    }

    public function test_website_booking_is_recorded_as_a_visitor_with_its_passengers(): void
    {
        $booking = $this->bookingFromWebsite($this->departure());

        $created = ActivityLog::where(['subject_type' => Booking::class, 'subject_id' => $booking->id, 'action' => 'created'])->firstOrFail();
        $this->assertSame(['visitor', 'Website visitor', 'website'], [$created->actor_type, $created->actor_name, $created->origin]);
        $this->assertSame([null, 'maria@example.com'], $created->changes['email']);
        $this->assertArrayNotHasKey('submission_token', $created->changes);
        $this->assertSame(2, ActivityLog::where('subject_type', \App\Models\BookingPassenger::class)->where('subject_label', 'like', '% · '.$booking->reference)->count());
    }

    public function test_booking_status_change_and_emails_show_on_the_booking_page(): void
    {
        $booking = $this->bookingFromWebsite($this->departure());
        $this->superadmin();
        $this->put(route('bookings.update', $booking), ['status' => 'confirmed', 'total_amount' => 210])->assertRedirect();

        $status = ActivityLog::where(['subject_type' => Booking::class, 'subject_id' => $booking->id, 'action' => 'updated'])->latest('id')->firstOrFail();
        $this->assertSame(['new', 'confirmed'], $status->changes['status']);
        $this->assertEquals([null, 210], $status->changes['total_amount']);
        // Queued and actually sent are separate steps (queue runs inline in tests, mail goes to the array mailer).
        $this->assertTrue(ActivityLog::where(['subject_id' => $booking->id, 'action' => 'email_queued'])->where('changes->email', 'BookingConfirmed')->exists());
        $this->assertTrue(ActivityLog::where(['subject_id' => $booking->id, 'action' => 'email_sent'])->where('changes->to', 'maria@example.com')->exists());

        $this->get(route('bookings.show', $booking))->assertOk()
            ->assertSee('Activity history')->assertSee('Jenn Manager')->assertSee('email sent')->assertSee('Leo Lopez');
    }

    public function test_translated_fields_are_recorded_against_their_record(): void
    {
        $departure = $this->departure();
        $this->superadmin();
        $tour = $departure->tour;
        $tour->translate('en')->name = 'Niagara Falls Day Trip';
        $tour->save();

        $log = ActivityLog::where(['subject_type' => Tour::class, 'subject_id' => $tour->id, 'action' => 'updated'])->latest('id')->firstOrFail();
        $this->assertSame(['Niagara Day Trip', 'Niagara Falls Day Trip'], $log->changes['name (en)']);
    }

    public function test_passwords_are_never_stored_and_role_changes_are_recorded(): void
    {
        $this->superadmin();
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor = User::factory()->create(['name' => 'Sam Sales', 'email' => 'sam@dreamtourpa.com']);
        $this->put(route('users.update', $editor), ['name' => 'Sam Sales', 'email' => 'sam@dreamtourpa.com', 'password' => 'new-secret-123', 'role' => 'editor'])->assertRedirect();

        $this->assertTrue(ActivityLog::where(['subject_type' => User::class, 'subject_id' => $editor->id, 'action' => 'password_changed'])->exists());
        $this->assertSame([null, 'editor'], ActivityLog::where(['subject_id' => $editor->id, 'action' => 'role_changed'])->firstOrFail()->changes['role']);
        $this->assertFalse(ActivityLog::where('changes', 'like', '%new-secret%')->orWhere('changes', 'like', '%password%')->exists());
    }

    public function test_history_cannot_be_edited_or_deleted(): void
    {
        Activity::record('created', null, ['note' => [null, 'x']]);
        $log = ActivityLog::firstOrFail();

        $this->assertThrows(fn () => $log->update(['actor_name' => 'Someone else']), \LogicException::class);
        $this->assertThrows(fn () => $log->delete(), \LogicException::class);
        $this->assertSame('System', $log->fresh()->actor_name);
    }

    public function test_history_page_is_restricted_and_filterable(): void
    {
        $departure = $this->departure();
        PickupPoint::create(['address' => '100 Queen St W', 'is_active' => 1, 'en' => ['name' => 'Toronto City Hall']]);
        $admin = $this->superadmin();
        Activity::because('Fuel surcharge', fn () => $departure->update(['price' => 140]));

        $this->get(route('activity-log.index'))->assertOk()->assertSee('Activity history')->assertSee('Toronto City Hall')->assertSee('Fuel surcharge');
        $this->get(route('activity-log.index', ['module' => 'Departures']))->assertOk()->assertSee('Fuel surcharge')->assertDontSee('Toronto City Hall');
        $this->get(route('activity-log.index', ['actor' => $admin->id, 'action' => 'updated']))->assertOk()->assertSee('Fuel surcharge');
        $this->get(route('activity-log.index', ['actor' => 'visitor']))->assertOk()->assertDontSee('Fuel surcharge');
        $this->get(route('activity-log.index', ['q' => 'Fuel']))->assertOk()->assertSee('140');

        $this->actingAs(User::factory()->create())->get(route('activity-log.index'))->assertForbidden();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('admin'))->get(route('activity-log.index'))->assertForbidden();
    }
}
