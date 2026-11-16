<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\BookingMoney;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §25 — permission-controlled CSV/Excel exports of the lead and booking lists, honouring their filters. */
class ListExportTest extends TestCase
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

    private function csv($response): array
    {
        $rows = array_map('str_getcsv', explode("\n", trim(str_replace("\xEF\xBB\xBF", '', $response->streamedContent()))));

        return array_map(fn ($r) => array_combine($rows[0], $r), array_slice($rows, 1));
    }

    public function test_lead_export_follows_the_list_filters(): void
    {
        $open = LeadStage::where('kind', 'open')->orderBy('sort_order')->first();
        Lead::create(['lead_stage_id' => $open->id, 'first_name' => 'Due', 'last_name' => 'Today', 'email' => 'due@example.com', 'phone' => '416-555-0101', 'source' => 'website', 'type' => 'tour', 'guests' => 3, 'utm_campaign' => 'fall-niagara', 'next_follow_up_at' => now()->subHour()]);
        Lead::create(['lead_stage_id' => $open->id, 'first_name' => 'Not', 'last_name' => 'Due', 'email' => 'notdue@example.com', 'source' => 'phone', 'type' => 'tour']);

        $rows = $this->csv($this->actingAs($this->admin)->get(route('leads.index', ['view' => 'list', 'due' => 1, 'export' => 'csv']))->assertOk());
        $this->assertCount(1, $rows);
        $this->assertSame(['Due', 'Today', 'due@example.com', '3', 'fall-niagara', $open->name], [$rows[0]['First name'], $rows[0]['Last name'], $rows[0]['Email'], $rows[0]['Guests'], $rows[0]['Campaign'], $rows[0]['Stage']]);
        $this->assertCount(2, $this->csv($this->get(route('leads.index', ['export' => 'csv']))));

        $xlsx = $this->get(route('leads.index', ['export' => 'xlsx']))->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
        $this->get(route('leads.index', ['export' => 'pdf']))->assertNotFound();
        $this->assertSame(3, ActivityLog::where('action', 'leads_exported')->count());

        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager'); // can see leads but not export them by default
        $this->actingAs($sales)->get(route('leads.index'))->assertOk()->assertDontSee('export=csv', false);
        $this->get(route('leads.index', ['export' => 'csv']))->assertForbidden();
    }

    public function test_booking_export_has_ledger_amounts_only_for_finance_and_follows_filters(): void
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'n-'.uniqid()]]);
        $d = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(20)->setTime(8, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        $paid = Booking::create(['tour_id' => $tour->id, 'tour_departure_id' => $d->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com', 'phone' => '416-555-0101', 'participant_count' => 2, 'currency' => 'CAD', 'total_amount' => '333.33', 'deposit_amount' => '0.00']);
        BookingMoney::adjust($paid, 'discount', '33.33', 'Loyalty', null, $this->admin);
        BookingMoney::record($paid, ['amount' => '120.50', 'method' => 'cash'], $this->admin, true);
        Booking::create(['tour_id' => $tour->id, 'tour_departure_id' => $d->id, 'type' => 'tour', 'status' => 'cancelled', 'first_name' => 'Carl', 'last_name' => 'Gone', 'email' => 'c@example.com', 'phone' => '1', 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => '100.00', 'deposit_amount' => '0.00']);

        $rows = $this->csv($this->actingAs($this->admin)->get(route('bookings.index', ['status' => 'confirmed', 'export' => 'csv']))->assertOk());
        $this->assertCount(1, $rows);
        $this->assertSame(['Maria', '2', '333.33', '33.33', '120.50', '179.50'], [$rows[0]['First name'], $rows[0]['Guests'], $rows[0]['Total (CAD)'], $rows[0]['Discounts (CAD)'], $rows[0]['Paid, net (CAD)'], $rows[0]['Outstanding (CAD)']]);
        $this->assertCount(2, $this->csv($this->get(route('bookings.index', ['export' => 'csv']))));
        $this->assertStringStartsWith('PK', $this->get(route('bookings.index', ['export' => 'xlsx']))->streamedContent());

        // Export permission without finance: same rows, no money columns.
        $role = Role::create(['name' => 'Office', 'guard_name' => 'web']);
        $role->givePermissionTo(['list-bookings', 'export-bookings']);
        $office = User::factory()->create();
        $office->assignRole('Office');
        $rows = $this->csv($this->actingAs($office)->get(route('bookings.index', ['export' => 'csv']))->assertOk());
        $this->assertArrayNotHasKey('Total (CAD)', $rows[0]);
        $this->assertArrayNotHasKey('Outstanding (CAD)', $rows[0]);

        $role->revokePermissionTo('export-bookings');
        $this->actingAs($office->fresh())->get(route('bookings.index', ['export' => 'csv']))->assertForbidden();
        $this->assertSame(4, ActivityLog::where('action', 'bookings_exported')->count());
    }
}
