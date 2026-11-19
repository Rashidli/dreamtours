<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\BookingMoney;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CRM §7 / §2 — payment reports, filters and receipts. One fixed scenario, every
 * expected figure worked out by hand (see the comments), compared to the cent.
 *
 *   A  Alpha +10d  price 300.00  maria   150.00 e-Transfer 3 Nov · 50.00 cash 10 Nov · refund 20.00 12 Nov
 *                                         pending 80.00 14 Nov · voided 999.00 cash 5 Nov          → owes 120.00
 *   B  Beta  +40d  price 200.50  maria   100.25 bank transfer 28 Oct · 0.25 cash 1 Nov · rejected 50.00 2 Nov → owes 100.00
 *   C  Alpha +5d   price 120.00  sam     nothing paid, balance was due yesterday                  → owes 120.00 (overdue)
 *   D  Beta  +40d  price 100.00  olga    100.00 e-Transfer 4 Nov, then cancelled                  → owes 0
 *   E  Beta  +40d  price 150.00  ben     discount 50.00, 100.00 cheque 6 Nov                     → owes 0
 */
class PaymentReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $b = [];
    private array $p = [];
    private array $tours = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-15 17:00:00', 'UTC')); // noon in Toronto
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));

        $alpha = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Alpha Tour', 'slug' => 'alpha']]);
        $beta = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Beta Tour', 'slug' => 'beta']]);
        $dep = fn (Tour $t, int $days) => TourDeparture::create(['tour_id' => $t->id, 'departs_at' => now()->addDays($days)->setTime(13, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
        [$alpha10, $alpha5, $beta40] = [$dep($alpha, 10), $dep($alpha, 5), $dep($beta, 40)];
        $this->tours = ['alpha' => $alpha->id, 'beta' => $beta->id];
        $book = fn (string $key, TourDeparture $d, string $price, string $first, string $email) => $this->b[$key] = Booking::create(['tour_id' => $d->tour_id, 'tour_departure_id' => $d->id, 'type' => 'tour', 'status' => 'new', 'first_name' => $first, 'last_name' => 'Test', 'email' => $email, 'phone' => '416-555-0'.rand(100, 999), 'participant_count' => 1, 'currency' => 'CAD', 'total_amount' => $price, 'deposit_amount' => '50.00']);
        $book('A', $alpha10, '300.00', 'Maria', 'maria@example.com');
        $book('B', $beta40, '200.50', 'Maria', 'maria@example.com');
        $book('C', $alpha5, '120.00', 'Sam', 'sam@example.com');
        $book('D', $beta40, '100.00', 'Olga', 'olga@example.com');
        $book('E', $beta40, '150.00', 'Ben', 'ben@example.com');

        $pay = fn (string $key, string $amount, string $method, string $on, bool $confirmed = true) => BookingMoney::record($this->b[$key], ['amount' => $amount, 'method' => $method, 'paid_on' => $on], $this->admin, $confirmed);
        $this->p['A1'] = $pay('A', '150.00', 'etransfer', '2026-11-03');
        $pay('A', '50.00', 'cash', '2026-11-10');
        BookingMoney::refund($this->p['A1'], '20.00', 'etransfer', 'Missed a stop', $this->admin)->update(['paid_on' => '2026-11-12']);
        $this->p['pending'] = $pay('A', '80.00', 'etransfer', '2026-11-14', confirmed: false);
        BookingMoney::void($pay('A', '999.00', 'cash', '2026-11-05'), 'Typo');
        $pay('B', '100.25', 'bank_transfer', '2026-10-28');
        $pay('B', '0.25', 'cash', '2026-11-01');
        BookingMoney::reject($pay('B', '50.00', 'etransfer', '2026-11-02', confirmed: false), 'Not received');
        $this->b['C']->update(['balance_due_date' => '2026-11-14']);
        $pay('D', '100.00', 'etransfer', '2026-11-04');
        $this->b['D']->update(['status' => 'cancelled']);
        BookingMoney::adjust($this->b['E'], 'discount', '50.00', 'Promo', null, $this->admin);
        $pay('E', '100.00', 'cheque', '2026-11-06');
        BookingMoney::sync($this->b['C']->fresh());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function report(array $query)
    {
        return $this->actingAs($this->admin)->get(route('payments.index', $query))->assertOk();
    }

    public function test_stored_balances_match_the_scenario(): void
    {
        $owes = collect($this->b)->map(fn ($b) => (string) $b->fresh()->outstanding_amount)->all();
        $this->assertSame(['A' => '120.00', 'B' => '100.00', 'C' => '120.00', 'D' => '0.00', 'E' => '0.00'], $owes);
        $this->assertSame(['deposit_paid', 'deposit_paid', 'unpaid', 'paid', 'paid'], collect($this->b)->map(fn ($b) => $b->fresh()->payment_status)->values()->all());
    }

    public function test_overview_totals_for_november(): void
    {
        $page = $this->report(['from' => '2026-11-01', 'to' => '2026-11-30']);
        // Received in Nov: 150 + 50 (A) + 0.25 (B) + 100 (D) + 100 (E) = 400.25 (5); B's 100.25 is October.
        // Refunded: 20.00 (1). Net: 380.25. Pending (all dates): 80.00 (1). Voided 999 and rejected 50 never count.
        $page->assertSeeInOrder(['Received', 'CA$400.25', '5 payments', 'Refunded', 'CA$20', '1 refund', 'Net collected', 'CA$380.25', 'Waiting for verification', 'CA$80', '1 payment'], false);
        // Owed today: A 120 + B 100 + C 120 = 340.00 on 3 bookings; overdue: C 120.00.
        $page->assertSeeInOrder(['Outstanding on active bookings', '3 bookings', 'CA$340', 'Of which overdue', '1 booking', 'CA$120'], false);
        // By method: e-Transfer 150 + 100 received, 20 refunded → net 230; cash 50 + 0.25 = 50.25; cheque 100.
        $page->assertSeeInOrder(['Interac e-Transfer', 'CA$250', 'CA$20', 'CA$230'], false)->assertSee('CA$50.25')->assertSee('Cheque');
    }

    public function test_ledger_filters_and_their_totals(): void
    {
        $totals = fn (array $q) => $this->report(['tab' => 'ledger'] + $q)->viewData('totals');
        $nov = ['from' => '2026-11-01', 'to' => '2026-11-30'];

        // 9 rows in November: A ×5, B ×2, D, E.
        $this->assertSame(['rows' => 9, 'received' => '400.25', 'received_count' => 5, 'refunded' => '20.00', 'refunded_count' => 1, 'net' => '380.25', 'pending' => '80.00', 'pending_count' => 1], $this->norm($totals($nov)));
        // Cash: 50 (A) + voided 999 (A) + 0.25 (B) → 3 rows, 50.25 counted.
        $this->assertSame(['rows' => 3, 'received' => '50.25', 'net' => '50.25'], array_intersect_key($this->norm($totals($nov + ['method' => 'cash'])), array_flip(['rows', 'received', 'net'])));
        $this->assertSame(['rows' => 1, 'refunded' => '20.00', 'net' => '-20.00'], array_intersect_key($this->norm($totals($nov + ['kind' => 'refund'])), array_flip(['rows', 'refunded', 'net'])));
        $this->assertSame(['rows' => 1, 'received' => '0.00', 'pending' => '80.00'], array_intersect_key($this->norm($totals($nov + ['status' => 'pending'])), array_flip(['rows', 'received', 'pending'])));
        // Beta tour in November: 0.25 (B) + rejected 50 + 100 (D) + 100 (E) → 4 rows, 200.25 received.
        $beta = $this->tours['beta'];
        $this->assertSame(['rows' => 4, 'received' => '200.25'], array_intersect_key($this->norm($totals($nov + ['tour' => $beta])), array_flip(['rows', 'received'])));
        // October only has B's bank transfer.
        $this->assertSame(['rows' => 1, 'received' => '100.25'], array_intersect_key($this->norm($totals(['from' => '2026-10-01', 'to' => '2026-10-31'])), array_flip(['rows', 'received'])));
        // Search by receipt number and by customer name.
        $this->assertSame(1, $totals($nov + ['q' => $this->p['A1']->receipt_number])['rows']);
        $this->assertSame(1, $totals($nov + ['q' => 'Olga'])['rows']);

        $this->report(['tab' => 'ledger'] + $nov)->assertSee($this->p['A1']->receipt_number)->assertSee('Voided')->assertSee('Rejected')->assertSeeInOrder(['9 entries', 'net confirmed', 'CA$380.25'], false);
    }

    public function test_outstanding_by_trip_and_by_customer_add_up(): void
    {
        // Total: due 300 + 200.50 + 120 = 620.50, paid 180 + 100.50 + 0 = 280.50, outstanding 340.00.
        $byTrip = $this->report(['tab' => 'outstanding']);
        $t = $byTrip->viewData('totals');
        $this->assertSame(['3', '620.50', '280.50', '340.00'], [(string) $t->bookings_count, (string) $t->due, (string) $t->paid, (string) $t->outstanding]);
        // Grouped by trip, earliest departure first: C (+5d) 120, A (+10d) 120, B (+40d) 100.
        $this->assertSame([['120.00', 1], ['120.00', 1], ['100.00', 1]], $byTrip->viewData('groups')->map(fn ($g) => [(string) $g->outstanding, (int) $g->bookings_count])->all());
        $this->assertSame($this->b['C']->tour_departure_id, (int) $byTrip->viewData('groups')->first()->group_id);

        // By customer, largest first: Maria (A + B) 220.00, Sam 120.00.
        $byCustomer = $this->report(['tab' => 'outstanding', 'group' => 'customer'])->viewData('groups');
        $this->assertSame([[$this->b['A']->customer_id, '220.00', 2], [$this->b['C']->customer_id, '120.00', 1]], $byCustomer->map(fn ($g) => [(int) $g->group_id, (string) $g->outstanding, (int) $g->bookings_count])->all());

        $this->assertSame('120.00', (string) $this->report(['tab' => 'outstanding', 'overdue' => 1])->viewData('totals')->outstanding);
        $this->assertSame('100.00', (string) $this->report(['tab' => 'outstanding', 'tour' => $this->tours['beta']])->viewData('totals')->outstanding);
        $this->assertSame('240.00', (string) $this->report(['tab' => 'outstanding', 'trip_to' => now()->addDays(20)->toDateString()])->viewData('totals')->outstanding);
        $this->assertSame('220.00', (string) $this->report(['tab' => 'outstanding', 'q' => 'Maria'])->viewData('totals')->outstanding);
        $this->report(['tab' => 'outstanding', 'group' => 'none'])->assertSeeInOrder(['Total · 3 bookings', 'CA$620.50', 'CA$280.50', 'CA$340'], false);
    }

    public function test_csv_exports_agree_with_the_screen(): void
    {
        $ledger = array_map('str_getcsv', array_filter(explode("\n", $this->report(['tab' => 'ledger', 'from' => '2026-11-01', 'to' => '2026-11-30', 'export' => 'csv'])->streamedContent())));
        $header = array_shift($ledger);
        $signed = array_search('Signed amount (CAD)', $header, true);
        $this->assertCount(9, $ledger);
        $this->assertSame('380.25', array_reduce($ledger, fn ($sum, $row) => bcadd($sum, $row[$signed], 2), '0.00'));

        $owing = array_map('str_getcsv', array_filter(explode("\n", $this->report(['tab' => 'outstanding', 'export' => 'csv'])->streamedContent())));
        $header = array_shift($owing);
        $col = array_search('Outstanding (CAD)', $header, true);
        $this->assertCount(3, $owing);
        $this->assertSame('340.00', array_reduce($owing, fn ($sum, $row) => bcadd($sum, $row[$col], 2), '0.00'));
    }

    public function test_pending_tab_and_confirming_from_it(): void
    {
        $this->report(['tab' => 'pending'])->assertSee($this->b['A']->reference)->assertSee('CA$80');
        $this->actingAs($this->admin)->post(route('booking-payments.confirm', [$this->b['A'], $this->p['pending']]))->assertRedirect();
        $this->report(['tab' => 'pending'])->assertSee('Nothing waiting for verification');
        $this->assertSame('40.00', (string) $this->b['A']->fresh()->outstanding_amount); // 120 − 80
    }

    public function test_reject_from_the_pending_list_stays_on_the_list(): void
    {
        $pendingUrl = route('payments.index', ['tab' => 'pending']);
        $this->report(['tab' => 'pending'])->assertSee('data-reject="'.route('booking-payments.reject', [$this->b['A'], $this->p['pending']]).'"', false)->assertSee('Reject this payment?');

        $this->actingAs($this->admin)->from($pendingUrl)->post(route('booking-payments.reject', [$this->b['A'], $this->p['pending']]), ['reason' => ''])->assertRedirect($pendingUrl)->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->from($pendingUrl)->post(route('booking-payments.reject', [$this->b['A'], $this->p['pending']]), ['reason' => 'Not received in the bank account'])->assertRedirect($pendingUrl);

        $this->assertSame(['rejected', 'Not received in the bank account'], [$this->p['pending']->fresh()->status, $this->p['pending']->fresh()->reason]);
        $this->assertSame(['0.00', '120.00'], [(string) $this->b['A']->fresh()->pending_amount, (string) $this->b['A']->fresh()->outstanding_amount], 'a rejected payment never reduces the balance');
        $this->report(['tab' => 'pending'])->assertSee('Nothing waiting for verification');
    }

    public function test_receipts_and_statements(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('receipts.payment', [$this->b['A'], $this->p['A1']]))->assertOk()
            ->assertSee('PAYMENT RECEIPT')->assertSee($this->p['A1']->receipt_number)->assertSee('PAID CA$150')->assertSeeInOrder(['Total due', 'CA$300', 'Balance outstanding', 'CA$120'], false);
        $pdf = $this->get(route('receipts.payment', [$this->b['A'], $this->p['A1'], 'pdf' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->get(route('receipts.payment', [$this->b['A'], $this->p['pending']]))->assertNotFound();
        $this->get(route('receipts.payment', [$this->b['B'], $this->p['A1']]))->assertNotFound();

        $this->get(route('receipts.statement', $this->b['E']))->assertOk()->assertSee('BOOKING STATEMENT')->assertSee('Promo')->assertSeeInOrder(['Price', 'CA$150', 'Discounts', 'CA$50', 'Total due', 'CA$100', 'Paid', 'CA$100', 'Balance outstanding', 'CA$0'], false);
        $this->assertStringStartsWith('%PDF', $this->get(route('receipts.statement', [$this->b['E'], 'pdf' => 1]))->getContent());
    }

    public function test_reports_need_the_payments_permission(): void
    {
        $clerk = User::factory()->create();
        $clerk->givePermissionTo('list-bookings');
        $this->actingAs($clerk)->get(route('payments.index'))->assertForbidden();
    }

    private function norm(array $t): array
    {
        return array_map(fn ($v) => is_int($v) ? $v : number_format((float) $v, 2, '.', ''), $t);
    }
}
