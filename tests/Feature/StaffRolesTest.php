<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\BookingMoney;
use App\Support\StaffRoles;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §16 — default roles, the role editor and "View financial information" (AT-14). */
class StaffRolesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** One booking of CA$333.33 with CA$120.50 paid, on a customer record. */
    private function booking(): Booking
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'niagara-'.uniqid()]]);
        $departure = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(20)->setTime(8, 0), 'price' => 333.33, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $booking = Booking::create(['tour_id' => $tour->id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'status' => 'new', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com', 'phone' => '416-555-0101', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '333.33', 'deposit_amount' => '120.50']);
        BookingMoney::record($booking, ['amount' => '120.50', 'method' => 'etransfer'], $this->admin, true);

        return $booking->fresh();
    }

    public function test_default_roles_exist_with_the_access_section_16_describes(): void
    {
        foreach (array_keys(StaffRoles::DEFAULTS) as $name) {
            $this->assertTrue(Role::where('name', $name)->exists(), "$name exists");
        }
        $this->assertEqualsCanonicalizing(['list-assigned-departures', 'check-in-passengers'], Role::findByName('Tour Leader')->permissions->pluck('name')->all());

        $has = fn (string $role) => Role::findByName($role)->permissions->pluck('name');
        $content = Role::findByName('Content Manager');
        $this->assertTrue($has('Content Manager')->contains('edit-tours'));
        foreach (['list-bookings', 'list-payments', 'view-financial-info', 'list-customers', 'edit-settings', 'list-users'] as $denied) {
            $this->assertFalse($has('Content Manager')->contains($denied), "Content Manager must not have $denied");
        }
        $this->assertTrue($has('Accountant')->contains('refund-payments'));
        $this->assertFalse($has('Accountant')->contains('edit-users'));

        // Running the installer again never re-adds a permission the team removed.
        $content->givePermissionTo('list-bookings');
        Role::findByName('Sales Manager')->revokePermissionTo('list-leads');
        $this->assertSame([], StaffRoles::install());
        $this->assertFalse(Role::findByName('Sales Manager')->fresh()->hasPermissionTo('list-leads'));
    }

    public function test_content_manager_cannot_reach_accounting_even_by_direct_url(): void
    {
        $booking = $this->booking();
        $payment = $booking->payments()->first();
        $this->actingAs($this->staff('Content Manager'));

        $this->get(route('tours.index'))->assertOk();
        $this->get(route('payments.index'))->assertForbidden();
        $this->get(route('payments.index', ['tab' => 'ledger', 'export' => 'csv']))->assertForbidden();
        $this->get(route('bookings.show', $booking))->assertForbidden();
        $this->get(route('receipts.payment', [$booking, $payment]))->assertForbidden();
        $this->get(route('customers.index'))->assertForbidden();
        $this->post(route('booking-payments.store', $booking), ['amount' => '10', 'method' => 'cash'])->assertForbidden();
        $this->assertSame(1, $booking->payments()->count());
    }

    public function test_without_view_financial_info_amounts_are_hidden_everywhere_and_cannot_be_filtered_or_exported(): void
    {
        $booking = $this->booking();
        $customer = Customer::findOrFail($booking->customer_id);
        $role = Role::create(['name' => 'Front desk', 'guard_name' => 'web']);
        $role->givePermissionTo(['list-bookings', 'list-customers', 'export-customers']);
        $this->actingAs($this->staff('Front desk'));

        foreach ([route('bookings.show', $booking), route('bookings.index'), route('customers.index'), route('customers.show', $customer), route('tour-departures.passengers', $booking->tour_departure_id)] as $url) {
            $this->get($url)->assertOk()->assertDontSee('CA$120.50')->assertDontSee('CA$212.83')->assertDontSee('CA$333.33');
        }
        $this->get(route('receipts.statement', $booking))->assertForbidden();

        // A money filter or sort would reveal amounts by elimination, so it is ignored.
        $this->get(route('customers.index', ['min_spent' => 1000]))->assertSee('Maria Lopez');
        $this->get(route('customers.index', ['outstanding' => 1, 'sort' => 'outstanding']))->assertOk()->assertSee('Maria Lopez')->assertDontSee('Outstanding (highest)');

        $csv = $this->get(route('customers.index', ['export' => 'csv']))->streamedContent();
        $this->assertStringNotContainsString('Total paid', $csv);
        $this->assertStringNotContainsString('120.50', $csv);

        // The same pages with the permission show the ledger's figures, to the cent.
        $role->givePermissionTo('view-financial-info');
        $this->get(route('customers.show', $customer))->assertSeeInOrder(['CA$333.33', 'CA$120.50', 'CA$212.83']);
        $this->get(route('bookings.show', $booking))->assertSee('CA$212.83');
        $csv = $this->get(route('customers.index', ['export' => 'csv']))->streamedContent();
        $this->assertStringContainsString('120.50', $csv);
        $this->assertStringContainsString('212.83', $csv);
    }

    public function test_customer_page_balance_comes_from_the_ledger_including_discounts(): void
    {
        $booking = $this->booking();
        BookingMoney::adjust($booking, 'discount', '33.33', 'Loyalty', null, $this->admin);
        $this->actingAs($this->admin)->get(route('customers.show', $booking->customer_id))
            ->assertSeeInOrder(['CA$300', 'CA$120.50', 'CA$179.50']); // 333.33 − 33.33 discount − 120.50 paid
    }

    public function test_role_editor_shows_plain_language_and_saves_the_grid(): void
    {
        $this->actingAs($this->admin);
        $role = Role::findByName('Accountant');
        $this->get(route('roles.index'))->assertOk()->assertSee('Accountant')->assertSee('Payments, refunds, discounts and financial reports');
        $this->get(route('roles.edit', $role))->assertOk()
            ->assertSee('View financial information')->assertSee('Departure dates')->assertSee('Use default permissions')
            ->assertSee('value="view-financial-info"', false)->assertDontSee('>list-bookings<', false);

        $this->put(route('roles.update', $role), ['name' => 'Accountant', 'permission' => ['list-bookings', 'list-payments']])->assertRedirect();
        $this->assertEqualsCanonicalizing(['list-bookings', 'list-payments'], $role->fresh()->permissions->pluck('name')->all());
    }
}
