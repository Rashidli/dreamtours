<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\BookingMoney;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM requirements §7 — payments, balances, discounts and refunds. Every figure is checked to the cent. */
class PaymentLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
    }

    private function booking(string $price = '210.00', string $deposit = '110.00', string $departs = '+30 days', array $extra = []): Booking
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara '.uniqid(), 'slug' => 'n-'.uniqid()]] + ($extra['tour'] ?? []));
        $departure = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->modify($departs)->setTime(8, 0), 'price' => 105, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);

        return Booking::create(['tour_id' => $tour->id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'status' => 'new', 'first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => $extra['email'] ?? 'maria@example.com', 'phone' => '416-555-0101', 'participant_count' => 2, 'currency' => 'CAD', 'total_amount' => $price, 'deposit_amount' => $deposit] + array_diff_key($extra, ['tour' => 1, 'email' => 1]));
    }

    /** The stored figures, as strings, for exact comparison. */
    private function figures(Booking $booking): array
    {
        $b = $booking->fresh();

        return ['paid' => (string) $b->amount_paid, 'discount' => (string) $b->discount_amount, 'refunded' => (string) $b->refunded_amount, 'pending' => (string) $b->pending_amount, 'outstanding' => (string) $b->outstanding_amount, 'status' => $b->payment_status];
    }

    private function pay(Booking $booking, string $amount, bool $confirmed = true, string $method = 'etransfer'): BookingPayment
    {
        return BookingMoney::record($booking, ['amount' => $amount, 'method' => $method], $this->admin, $confirmed);
    }

    public function test_a_new_booking_owes_its_full_price_and_is_due_seven_days_before_departure(): void
    {
        $booking = $this->booking();

        $this->assertSame(['paid' => '0.00', 'discount' => '0.00', 'refunded' => '0.00', 'pending' => '0.00', 'outstanding' => '210.00', 'status' => 'unpaid'], $this->figures($booking));
        $this->assertSame(now()->addDays(23)->toDateString(), $booking->fresh()->balance_due_date->toDateString());
    }

    // §7: a guest-uploaded receipt is not proof — it waits as pending and does not reduce the balance.
    public function test_pending_payments_do_not_count_until_confirmed_and_confirming_counts_once(): void
    {
        $booking = $this->booking();
        $pending = $this->pay($booking, '110.00', confirmed: false);
        $this->assertSame(['paid' => '0.00', 'discount' => '0.00', 'refunded' => '0.00', 'pending' => '110.00', 'outstanding' => '210.00', 'status' => 'proof_submitted'], $this->figures($booking));
        $this->assertNull($pending->receipt_number);

        // AT-04: confirming CAD 110 adjusts the balance exactly once, with receipt and actor.
        $this->actingAs($this->admin)->post(route('booking-payments.confirm', [$booking, $pending]))->assertRedirect();
        $this->actingAs($this->admin)->post(route('booking-payments.confirm', [$booking, $pending]))->assertStatus(422);
        $pending->refresh();
        $this->assertSame(['confirmed', 'R-'.now()->year.'-00001', $this->admin->id], [$pending->status, $pending->receipt_number, $pending->confirmed_by]);
        $this->assertSame(['paid' => '110.00', 'discount' => '0.00', 'refunded' => '0.00', 'pending' => '0.00', 'outstanding' => '100.00', 'status' => 'deposit_paid'], $this->figures($booking));
        $this->assertTrue(ActivityLog::where(['subject_type' => BookingPayment::class, 'subject_id' => $pending->id, 'action' => 'updated', 'actor_id' => $this->admin->id])->exists());
    }

    public function test_cents_add_up_exactly(): void
    {
        $booking = $this->booking('333.33', '100.00');
        BookingMoney::adjust($booking, 'discount', '33.33', 'Repeat guest', null, $this->admin);
        $this->pay($booking, '100.10');
        $this->pay($booking, '0.20');
        $this->pay($booking, '99.99', method: 'cash');

        // 333.33 − 33.33 = 300.00; paid 100.10 + 0.20 + 99.99 = 200.29; outstanding 99.71.
        $this->assertSame(['paid' => '200.29', 'discount' => '33.33', 'refunded' => '0.00', 'pending' => '0.00', 'outstanding' => '99.71', 'status' => 'deposit_paid'], $this->figures($booking));
        $this->pay($booking, '99.71');
        $this->assertSame(['paid' => '300.00', 'outstanding' => '0.00', 'status' => 'paid'], array_intersect_key($this->figures($booking), array_flip(['paid', 'outstanding', 'status'])));
    }

    public function test_partial_payment_below_the_deposit_and_overpayment_credit(): void
    {
        $booking = $this->booking('200.00', '100.00');
        $this->pay($booking, '40.00');
        $this->assertSame(['160.00', 'partially_paid'], [$this->figures($booking)['outstanding'], $this->figures($booking)['status']]);

        $this->pay($booking, '200.00');
        $s = BookingMoney::summary($booking->fresh());
        $this->assertSame(['240.00', '0.00', '40.00', 'paid'], [$s['net'], $s['outstanding'], $s['credit'], $booking->fresh()->payment_status]);
    }

    // AT-13: a refund recalculates collected and balance without corrupting history.
    public function test_refunds_recalculate_and_keep_the_original_payment(): void
    {
        $booking = $this->booking('210.00', '110.00');
        $payment = $this->pay($booking, '210.00');
        $this->actingAs($this->admin);

        $this->post(route('booking-payments.refund', [$booking, $payment]), ['amount' => '250.00', 'method' => 'etransfer', 'reason' => 'Too much'])->assertSessionHasErrors('amount');
        $this->post(route('booking-payments.refund', [$booking, $payment]), ['amount' => '60.50', 'method' => 'etransfer', 'reason' => 'Missed one stop'])->assertRedirect();
        $this->assertSame(['paid' => '149.50', 'discount' => '0.00', 'refunded' => '60.50', 'pending' => '0.00', 'outstanding' => '60.50', 'status' => 'deposit_paid'], $this->figures($booking));
        $this->assertSame(['confirmed', '210.00', '149.50'], [$payment->fresh()->status, (string) $payment->fresh()->amount, $payment->fresh()->refundable()]);
        $refund = BookingPayment::where('kind', 'refund')->firstOrFail();
        $this->assertSame(['RF-'.now()->year.'-00001', $payment->id, 'Missed one stop'], [$refund->receipt_number, $refund->refund_of_id, $refund->reason]);

        // A cancelled trip: nothing more is owed, the rest is credit until refunded.
        $booking->update(['status' => 'cancelled']);
        $s = BookingMoney::summary($booking->fresh());
        $this->assertSame(['0.00', '149.50'], [$s['outstanding'], $s['credit']]);
        $this->post(route('booking-payments.refund', [$booking, $payment]), ['amount' => '149.50', 'method' => 'etransfer', 'reason' => 'Trip cancelled'])->assertRedirect();
        $this->assertSame(['paid' => '0.00', 'discount' => '0.00', 'refunded' => '210.00', 'pending' => '0.00', 'outstanding' => '0.00', 'status' => 'refunded'], $this->figures($booking));
        $this->assertSame('0.00', $payment->fresh()->refundable());
        $this->assertSame(3, BookingPayment::count(), 'nothing is deleted');
    }

    public function test_reject_and_void_take_rows_out_of_the_totals_but_keep_them(): void
    {
        $booking = $this->booking('210.00', '110.00');
        $pending = $this->pay($booking, '110.00', confirmed: false);
        $mistake = $this->pay($booking, '50.00');
        $this->actingAs($this->admin);

        $this->post(route('booking-payments.reject', [$booking, $pending]), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post(route('booking-payments.reject', [$booking, $pending]), ['reason' => 'Not in the bank account'])->assertRedirect();
        $this->post(route('booking-payments.void', [$booking, $mistake]), ['reason' => 'Entered on the wrong booking'])->assertRedirect();

        $this->assertSame(['paid' => '0.00', 'discount' => '0.00', 'refunded' => '0.00', 'pending' => '0.00', 'outstanding' => '210.00', 'status' => 'unpaid'], $this->figures($booking));
        $this->assertSame(['rejected', 'voided'], [$pending->fresh()->status, $mistake->fresh()->status]);

        // A payment with refunds can't be voided until they are.
        $paid = $this->pay($booking, '100.00');
        BookingMoney::refund($paid, '10.00', 'cash', 'Snack voucher', $this->admin);
        $this->post(route('booking-payments.void', [$booking, $paid]), ['reason' => 'Oops'])->assertStatus(422);
    }

    public function test_discounts_and_complimentary_seats_lower_what_is_owed_but_are_not_payments(): void
    {
        $booking = $this->booking('210.00', '110.00');
        $passenger = $booking->passengers()->create(['first_name' => 'Leo', 'last_name' => 'Lopez', 'category' => 'child', 'price' => '95.00']);
        $this->actingAs($this->admin);

        $this->post(route('booking-adjustments.store', $booking), ['kind' => 'discount', 'amount' => '300', 'reason' => 'Too much'])->assertSessionHasErrors('amount');
        $this->post(route('booking-adjustments.store', $booking), ['kind' => 'discount', 'amount' => '21.00', 'reason' => '10% repeat guest'])->assertRedirect();
        $this->post(route('booking-adjustments.store', $booking), ['kind' => 'complimentary', 'amount' => '95.00', 'reason' => 'Tour leader child', 'booking_passenger_id' => $passenger->id])->assertRedirect();
        $this->assertSame(['paid' => '0.00', 'discount' => '116.00', 'refunded' => '0.00', 'pending' => '0.00', 'outstanding' => '94.00', 'status' => 'unpaid'], $this->figures($booking));
        $s = BookingMoney::summary($booking->fresh());
        $this->assertSame(['210.00', '94.00', '94.00', '95.00'], [$s['price'], $s['due'], $s['deposit'], $s['complimentary']], 'the deposit never exceeds what is due');

        $discount = $booking->adjustments()->where('kind', 'discount')->firstOrFail();
        $this->post(route('booking-adjustments.void', [$booking, $discount]), ['reason' => 'Not a repeat guest'])->assertRedirect();
        $this->assertSame(['95.00', '115.00'], [$this->figures($booking)['discount'], $this->figures($booking)['outstanding']]);

        // A booking that is entirely complimentary owes nothing.
        $comp = $this->booking('105.00', '50.00');
        BookingMoney::adjust($comp, 'complimentary', '105.00', 'Driver', null, $this->admin);
        $this->assertSame(['0.00', 'complimentary'], [$this->figures($comp)['outstanding'], $this->figures($comp)['status']]);
    }

    public function test_receipt_numbers_run_in_order_across_bookings(): void
    {
        $a = $this->booking();
        $b = $this->booking();
        $year = now()->year;

        $this->assertSame("R-$year-00001", $this->pay($a, '10.00')->receipt_number);
        $this->assertSame("R-$year-00002", $this->pay($b, '10.00')->receipt_number);
        $this->assertNull($this->pay($b, '10.00', confirmed: false)->receipt_number);
        $this->assertSame("R-$year-00003", $this->pay($a, '10.00')->receipt_number);
    }

    public function test_due_dates_follow_tour_and_setting_and_can_be_overridden(): void
    {
        Setting::updateOrCreate(['key' => 'balance_due_days'], ['value' => '14']);
        Cache::forget('settings');
        $this->assertSame(now()->addDays(16)->toDateString(), $this->booking()->fresh()->balance_due_date->toDateString());
        $this->assertSame(now()->addDays(27)->toDateString(), $this->booking(extra: ['tour' => ['balance_due_days' => 3]])->fresh()->balance_due_date->toDateString());
        $this->assertSame(now()->toDateString(), $this->booking(departs: '+5 days')->fresh()->balance_due_date->toDateString(), 'never before the booking was made');

        $booking = $this->booking();
        $this->actingAs($this->admin)->put(route('booking-payments.terms', $booking), ['deposit_amount' => '50.00', 'balance_due_date' => now()->subDay()->toDateString(), 'payment_terms_note' => 'Group rate'])->assertRedirect();
        $s = BookingMoney::summary($booking->fresh());
        $this->assertSame(['50.00', true], [$s['deposit'], $s['overdue']]);
        $this->actingAs($this->admin)->put(route('booking-payments.terms', $booking), ['deposit_amount' => '999', 'balance_due_date' => now()->toDateString()])->assertSessionHasErrors('deposit_amount');
    }

    public function test_default_deposit_percentage_applies_when_a_tour_has_no_per_person_deposit(): void
    {
        $this->assertSame('210.00', BookingMoney::defaultDeposit('210.00'), 'default 100% keeps today\'s behaviour');
        Setting::updateOrCreate(['key' => 'default_deposit_percent'], ['value' => '50']);
        Cache::forget('settings');
        $this->assertSame('105.00', BookingMoney::defaultDeposit('210.00'));
        $this->assertSame('166.67', BookingMoney::defaultDeposit('333.33'), 'half a cent rounds up');
    }

    public function test_staff_without_confirm_permission_can_only_record_pending(): void
    {
        $booking = $this->booking();
        $clerk = User::factory()->create();
        $clerk->givePermissionTo(['list-bookings', 'record-payments']);
        $this->actingAs($clerk);

        $this->post(route('booking-payments.store', $booking), ['amount' => '110', 'method' => 'cash', 'paid_on' => now()->toDateString(), 'status' => 'confirmed'])->assertRedirect();
        $this->assertSame(['pending', null], [BookingPayment::first()->status, BookingPayment::first()->receipt_number]);
        $this->assertSame('210.00', $this->figures($booking)['outstanding']);
        $this->post(route('booking-payments.confirm', [$booking, BookingPayment::first()]))->assertForbidden();
        $this->post(route('booking-adjustments.store', $booking), ['kind' => 'discount', 'amount' => '10', 'reason' => 'x'])->assertForbidden();
        $this->post(route('booking-payments.refund', [$booking, BookingPayment::first()]), ['amount' => '1', 'method' => 'cash', 'reason' => 'x'])->assertForbidden();
    }

    // Journey B: one customer, a paid November trip and an unpaid December trip.
    public function test_customer_balances_are_per_trip_and_the_outstanding_filter_is_exact(): void
    {
        $november = $this->booking('180.00', '90.00', '+10 days');
        $december = $this->booking('240.00', '120.00', '+40 days');
        $this->pay($november, '180.00');
        $this->pay($december, '120.00');
        $customer = Customer::withStats()->findOrFail($november->customer_id);

        $this->assertSame($november->customer_id, $december->customer_id);
        $this->assertEquals(['2', '300.00', '120.00'], [$customer->trips_count, $customer->total_paid, $customer->outstanding]);
        $this->assertSame(['paid', 'deposit_paid'], [$november->fresh()->payment_status, $december->fresh()->payment_status]);
        $this->assertSame([$december->id], Booking::where('outstanding_amount', '>', 0)->pluck('id')->all());
        $this->actingAs($this->admin)->get(route('customers.index', ['outstanding' => 1]))->assertOk()->assertSee('Maria Lopez');
    }

    public function test_booking_page_shows_the_ledger_figures(): void
    {
        $booking = $this->booking('210.00', '110.00');
        BookingMoney::adjust($booking, 'discount', '10.00', 'Early bird', null, $this->admin);
        $this->pay($booking, '110.00');
        $this->pay($booking, '20.00', confirmed: false);

        $this->actingAs($this->admin)->get(route('bookings.show', $booking))->assertOk()
            ->assertSeeInOrder(['Price', 'CA$210', 'Discounts', 'CA$10', 'Total due', 'CA$200', 'Paid (confirmed)', 'CA$110', 'Outstanding', 'CA$90'], false)
            ->assertSee('waiting for verification')->assertSee('R-'.now()->year.'-00001')->assertSee('Early bird')
            ->assertDontSee('name="amount_paid"', false)->assertDontSee('name="payment_status"', false);
    }

    public function test_sync_command_brings_old_bookings_into_the_ledger_once(): void
    {
        $typed = $this->booking('210.00', '110.00');
        $proof = $this->booking('180.00', '90.00', extra: ['payment_proof' => 'payment-proofs/x.png', 'email' => 'other@example.com']);
        BookingPayment::query()->delete();
        Booking::whereKey($typed->id)->update(['amount_paid' => '110.00']); // as typed before the ledger existed

        $this->artisan('crm:sync-payments')->expectsOutputToContain('2 ledger entries created')->assertSuccessful();
        $this->artisan('crm:sync-payments')->expectsOutputToContain('0 ledger entries created')->assertSuccessful();
        $this->assertSame(['110.00', '100.00', 'deposit_paid'], [$this->figures($typed)['paid'], $this->figures($typed)['outstanding'], $this->figures($typed)['status']]);
        $this->assertSame(['0.00', '90.00', '180.00', 'proof_submitted'], [$this->figures($proof)['paid'], $this->figures($proof)['pending'], $this->figures($proof)['outstanding'], $this->figures($proof)['status']]);
    }
}
