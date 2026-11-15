<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\CustomerTag;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\VisaEnquiry;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM requirements §4 — permanent customer directory. */
class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function departure(string $name = 'Niagara Day Trip', string $when = '+1 month'): TourDeparture
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => $name, 'slug' => str($name)->slug()]]);

        return TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->modify($when), 'price' => 100, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
    }

    private function book(TourDeparture $departure, array $contact = []): Booking
    {
        $contact += ['first_name' => 'Maria', 'last_name' => 'Lopez', 'phone' => '416-555-0101', 'email' => 'maria@example.com'];
        $this->post(route('front.bookings.store'), $contact + ['tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'participant_count' => 1])->assertRedirect();

        return Booking::latest('id')->firstOrFail();
    }

    /** A booking made directly (past departures, or fields the quick form doesn't take). */
    private function bookDirect(TourDeparture $departure, array $fields = []): Booking
    {
        return Booking::create($fields + ['tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'phone' => '416-555-0101', 'email' => 'maria@example.com', 'participant_count' => 1, 'currency' => 'CAD']);
    }

    private function admin(?array $permissions = null): User
    {
        $this->seed(CmsPermissionSeeder::class);
        $user = User::factory()->create(['name' => 'Jenn Manager']);
        if ($permissions === null) {
            $user->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        } else {
            $user->givePermissionTo($permissions);
        }
        $this->actingAs($user);

        return $user;
    }

    // AT-02 — one customer can have 3 bookings with different balances; no duplicated identity.
    public function test_one_customer_keeps_three_bookings_with_their_own_balances(): void
    {
        foreach ([[200, 200], [150, 50], [300, 0]] as $i => [$total, $paid]) {
            $booking = $this->book($this->departure("Trip $i"), ['email' => $i === 1 ? 'Maria@Example.COM' : 'maria@example.com']);
            $booking->update(['total_amount' => $total]);
            if ($paid) {
                \App\Support\BookingMoney::record($booking, ['amount' => $paid, 'method' => 'cash'], null, confirmed: true);
            }
        }

        $this->assertSame(1, Customer::count());
        $customer = Customer::withStats()->firstOrFail();
        $this->assertSame(3, (int) $customer->trips_count);
        $this->assertEquals(250, $customer->total_paid);
        $this->assertEquals(400, $customer->outstanding);
        $this->assertSame(3, Booking::where('customer_id', $customer->id)->count());
    }

    public function test_phone_matches_only_when_an_email_is_missing(): void
    {
        $departure = $this->departure();
        $first = $this->book($departure, ['email' => 'maria@example.com', 'phone' => '+1 (416) 555-0101']);
        $sameNoEmail = $this->book($departure, ['email' => '', 'phone' => '416 555 0101']);
        $partner = $this->book($departure, ['first_name' => 'Juan', 'email' => 'juan@example.com', 'phone' => '416-555-0101']);

        $this->assertSame($first->customer_id, $sameNoEmail->customer_id);
        $this->assertNotSame($first->customer_id, $partner->customer_id); // different email on a shared phone: staff decide
    }

    public function test_matching_fills_gaps_but_never_overwrites_staff_data_or_consent(): void
    {
        $dnc = CustomerStatus::where('name', 'Do Not Contact')->firstOrFail();
        $customer = Customer::create(['first_name' => 'Mary', 'last_name' => 'L.', 'email' => 'maria@example.com', 'city' => 'Toronto', 'customer_status_id' => $dnc->id]);

        $booking = $this->bookDirect($this->departure(), ['first_name' => 'Maria', 'whatsapp' => '416-555-0199', 'marketing_consent' => true]);

        $customer->refresh();
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertSame(['Mary', 'L.', 'Toronto', '416-555-0199'], [$customer->first_name, $customer->last_name, $customer->city, $customer->whatsapp]);
        $this->assertTrue($customer->marketing_opt_in);
        $this->assertSame('Booking '.$booking->reference, $customer->marketing_opt_in_source);
        $this->assertFalse($customer->isMarketingEligible(), 'Do Not Contact blocks promotions whatever the consent');
        $this->assertSame(0, Customer::marketingEligible()->count());
    }

    public function test_visa_enquiries_link_to_customers_too(): void
    {
        $enquiry = VisaEnquiry::create(['full_name' => 'Ana Maria Silva', 'email' => 'ana@example.com', 'phone' => '+14165550123', 'country' => 'USA', 'assistance' => ['visa'], 'applicants' => 1]);

        $this->assertSame(['Ana', 'Maria Silva', 'visa'], [$enquiry->customer->first_name, $enquiry->customer->last_name, $enquiry->customer->source]);
    }

    public function test_link_command_links_old_records_once_and_dry_run_saves_nothing(): void
    {
        $booking = $this->book($this->departure());
        Booking::whereKey($booking->id)->update(['customer_id' => null]);
        Customer::query()->forceDelete();

        $this->artisan('crm:link-customers --dry-run')->assertSuccessful();
        $this->assertSame(0, Customer::count());
        $this->artisan('crm:link-customers')->expectsOutputToContain('1 records linked, 1 customers created')->assertSuccessful();
        $this->artisan('crm:link-customers')->expectsOutputToContain('0 records linked, 0 customers created')->assertSuccessful();
        $this->assertNotNull($booking->fresh()->customer_id);
        $this->assertTrue(ActivityLog::where('action', 'customers_linked')->exists());
    }

    public function test_directory_filters_and_profile_history(): void
    {
        $past = $this->bookDirect($this->departure('Algonquin', '-2 months'), ['total_amount' => 200]);
        \App\Support\BookingMoney::record($past, ['amount' => 100, 'method' => 'cash'], null, confirmed: true);
        $past->passengers()->create(['first_name' => 'Leo', 'last_name' => 'Lopez', 'category' => 'child']);
        $other = $this->book($this->departure('Ottawa'), ['first_name' => 'Sam', 'last_name' => 'Lee', 'email' => 'sam@example.com', 'phone' => '647-555-0000']);
        $vip = CustomerTag::create(['name' => 'VIP bus front', 'color' => 'warning']);
        $other->customer->tags()->attach($vip);
        $other->customer->forceFill(['date_of_birth' => '1990-03-14'])->save();
        $this->admin();

        $list = fn (array $q) => $this->get(route('customers.index', $q))->assertOk();
        $list([])->assertSee('Maria Lopez')->assertSee('Sam Lee');
        $list(['q' => '647 555'])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
        $list(['tag' => $vip->id])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
        $list(['outstanding' => 1])->assertSee('Maria Lopez')->assertDontSee('Sam Lee');
        $list(['trip_from' => now()->subMonths(3)->format('Y-m-d')])->assertSee('Maria Lopez')->assertDontSee('Sam Lee');
        $list(['birthday_month' => 3])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
        $list(['sort' => 'outstanding'])->assertSeeInOrder(['Maria Lopez', 'Sam Lee']);

        $this->get(route('customers.show', $past->customer_id))->assertOk()
            ->assertSee($past->reference)->assertSee('Algonquin')->assertSee('Leo Lopez')->assertSee('CA$100')->assertSee('Activity history');
    }

    public function test_birthdays_are_hidden_without_permission(): void
    {
        $booking = $this->book($this->departure(), ['date_of_birth' => '1986-07-22']);
        $this->admin(['list-customers', 'export-customers']);

        $this->get(route('customers.show', $booking->customer_id))->assertOk()->assertDontSee('1986')->assertDontSee('Date of birth');
        $this->get(route('customers.index', ['birthday_month' => 7]))->assertOk()->assertSee('Maria Lopez'); // filter ignored, not leaked
        $csv = $this->get(route('customers.index', ['export' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Date of birth', $csv);
        $this->assertStringNotContainsString('1986', $csv);
    }

    public function test_export_keeps_columns_aligned_and_is_recorded(): void
    {
        $this->book($this->departure(), ['date_of_birth' => '1986-07-22']); // no preferred name, city or language
        $this->admin();

        $rows = array_map('str_getcsv', array_filter(explode("\n", $this->get(route('customers.index', ['export' => 'csv']))->assertOk()->streamedContent())));
        $this->assertCount(count($rows[0]), $rows[1]);
        $row = array_combine($rows[0], $rows[1]);
        $this->assertSame(['Maria', 'maria@example.com', '1986-07-22', ''], [$row['First name'], $row['Email'], $row['Date of birth'], $row['Preferred name']]);
        $this->assertTrue(ActivityLog::where('action', 'customers_exported')->exists());
    }

    public function test_staff_create_edit_tag_and_archive_customers(): void
    {
        $this->admin();
        $tag = CustomerTag::create(['name' => 'Seniors', 'color' => 'info']);

        $this->post(route('customers.store'), ['first_name' => 'Olga', 'email' => 'olga@example.com', 'marketing_opt_in' => 1, 'tag_ids' => [$tag->id]])->assertRedirect();
        $olga = Customer::where('email', 'olga@example.com')->firstOrFail();
        $this->assertSame(['manual', 'Recorded by Jenn Manager'], [$olga->source, $olga->marketing_opt_in_source]);
        $this->assertTrue(ActivityLog::where(['subject_id' => $olga->id, 'action' => 'tags_changed'])->exists());

        $this->post(route('customers.store'), ['first_name' => 'Olga again', 'email' => 'OLGA@example.com'])->assertSessionHasErrors('email');
        $this->post(route('customers.store'), ['first_name' => 'No contact'])->assertSessionHasErrors('phone');

        $this->put(route('customers.update', $olga), ['first_name' => 'Olga', 'email' => 'olga@example.com', 'marketing_opt_in' => 0])->assertRedirect();
        $this->assertSame('Withdrawn by Jenn Manager', $olga->fresh()->marketing_opt_in_source);

        $this->delete(route('customers.destroy', $olga))->assertRedirect();
        $this->assertSoftDeleted($olga);
        $this->get(route('customers.show', $olga->id))->assertOk()->assertSee('archived');
        $this->post(route('customers.restore', $olga->id))->assertRedirect();
        $this->assertNotSoftDeleted($olga);
    }

    public function test_labels_archive_instead_of_losing_history(): void
    {
        $this->admin();
        $booking = $this->book($this->departure());
        $issue = CustomerStatus::where('name', 'Service Issue')->firstOrFail();
        $booking->customer->update(['customer_status_id' => $issue->id]);

        $this->delete(route('customer-labels.destroy', ['status', $issue->id]))->assertSessionHasErrors('label');
        $this->post(route('customer-labels.archive', ['status', $issue->id]))->assertRedirect();
        $this->assertNotNull($issue->fresh()->archived_at);
        $this->assertSame($issue->id, $booking->customer->fresh()->customer_status_id);
        $this->get(route('customers.edit', $booking->customer_id))->assertOk()->assertSee('Service Issue (archived)');

        $this->post(route('customer-labels.store', 'tag'), ['name' => 'Wheelchair', 'color' => 'info'])->assertRedirect();
        $tag = CustomerTag::where('name', 'Wheelchair')->firstOrFail();
        $this->delete(route('customer-labels.destroy', ['tag', $tag->id]))->assertRedirect();
        $this->assertModelMissing($tag);
    }

    public function test_customer_pages_need_their_permissions(): void
    {
        $booking = $this->book($this->departure());
        $this->admin(['list-bookings']);

        $this->get(route('customers.index'))->assertForbidden();
        $this->get(route('customers.show', $booking->customer_id))->assertForbidden();
        $this->get(route('customer-labels.index'))->assertForbidden();

        $this->admin(['list-customers']);
        $this->get(route('customers.index'))->assertOk()->assertDontSee('Export CSV')->assertDontSee('New customer');
        $this->get(route('customers.index', ['export' => 'csv']))->assertForbidden();
        $this->get(route('customers.create'))->assertForbidden();
    }
}
