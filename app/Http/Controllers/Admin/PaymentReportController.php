<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Tour;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payments & balances (§7, §2): money received in a period, the full ledger, what
 * is still owed (by trip or customer) and payments waiting for verification. All
 * totals are summed in SQL over the same filtered query as the rows they describe,
 * so a total always matches the list under it.
 */
class PaymentReportController extends Controller
{
    public function __construct() { $this->middleware('permission:list-payments'); }

    public function index(Request $r): View|StreamedResponse
    {
        $tab = in_array($r->input('tab'), ['ledger', 'outstanding', 'pending'], true) ? $r->input('tab') : 'overview';
        $f = $this->filters($r);
        $common = ['tab' => $tab, 'f' => $f, 'tours' => Tour::withTranslation()->get()->sortBy('name'), 'users' => User::orderBy('name')->get(['id', 'name']), 'pendingCount' => BookingPayment::where('status', 'pending')->count()];

        if ($r->input('export') === 'csv' && in_array($tab, ['ledger', 'outstanding'], true)) {
            return $tab === 'ledger' ? $this->exportLedger($f) : $this->exportOutstanding($f);
        }

        return match ($tab) {
            'ledger' => view('admin.payments.index', $common + ['rows' => $this->ledger($f)->with(['booking.customer', 'booking.tour.translations', 'recorder'])->orderByDesc('booking_payments.paid_on')->orderByDesc('booking_payments.id')->paginate(50)->withQueryString(), 'totals' => $this->ledgerTotals($f)]),
            'outstanding' => view('admin.payments.index', $common + $this->outstandingData($f)),
            'pending' => view('admin.payments.index', $common + ['rows' => BookingPayment::where('booking_payments.status', 'pending')->with(['booking.customer', 'booking.tour.translations', 'booking.departure'])->oldest('paid_on')->get()]),
            default => view('admin.payments.index', $common + $this->overview($f)),
        };
    }

    /** Validated filters with defaults: the period is this month (in Toronto time) unless chosen. */
    private function filters(Request $r): array
    {
        $d = $r->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'],
            'kind' => ['nullable', Rule::in(['payment', 'refund'])], 'status' => ['nullable', Rule::in(array_keys(BookingPayment::STATUSES))],
            'method' => ['nullable', Rule::in(array_keys(BookingPayment::METHODS))], 'tour' => ['nullable', 'integer'], 'recorded_by' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'], 'trip_from' => ['nullable', 'date'], 'trip_to' => ['nullable', 'date'],
            'due_from' => ['nullable', 'date'], 'due_to' => ['nullable', 'date'], 'overdue' => ['nullable', 'boolean'],
            'group' => ['nullable', Rule::in(['trip', 'customer', 'none'])],
        ]);
        $now = now(ActivityLogController::DISPLAY_TIMEZONE);

        return $d + ['from' => $d['from'] ?? $now->copy()->startOfMonth()->toDateString(), 'to' => $d['to'] ?? $now->toDateString(), 'group' => $d['group'] ?? 'trip', 'overdue' => (bool) ($d['overdue'] ?? false), 'today' => $now->toDateString()];
    }

    /** Ledger rows matching the filters (payments and refunds, any status unless filtered). */
    private function ledger(array $f): Builder
    {
        return BookingPayment::query()->select('booking_payments.*')
            ->join('bookings', 'bookings.id', '=', 'booking_payments.booking_id')
            ->whereBetween('booking_payments.paid_on', [$f['from'], $f['to']])
            ->when($f['kind'] ?? null, fn ($q, $kind) => $q->where('booking_payments.kind', $kind))
            ->when($f['status'] ?? null, fn ($q, $status) => $q->where('booking_payments.status', $status))
            ->when($f['method'] ?? null, fn ($q, $method) => $q->where('booking_payments.method', $method))
            ->when($f['tour'] ?? null, fn ($q, $tour) => $q->where('bookings.tour_id', $tour))
            ->when($f['recorded_by'] ?? null, fn ($q, $user) => $q->where('booking_payments.recorded_by', $user))
            ->when($f['q'] ?? null, fn ($q, $term) => $q->where(fn ($x) => $x->where('booking_payments.receipt_number', 'like', "%{$term}%")->orWhere('booking_payments.reference', 'like', "%{$term}%")
                ->orWhere('bookings.reference', 'like', "%{$term}%")->orWhereRaw("concat_ws(' ', bookings.first_name, bookings.last_name) like ?", ["%{$term}%"])->orWhere('bookings.email', 'like', "%{$term}%")));
    }

    /** Confirmed money in and out for the filtered ledger; pending shown apart; voided / rejected never counted. */
    private function ledgerTotals(array $f): array
    {
        // Same filters, but only the sums (the row query selects booking_payments.*).
        $t = $this->ledger($f)->reorder()->toBase()->cloneWithout(['columns'])->selectRaw("
            count(*) as rows_count,
            coalesce(sum(case when booking_payments.kind = 'payment' and booking_payments.status = 'confirmed' then booking_payments.amount end), 0) as received,
            sum(case when booking_payments.kind = 'payment' and booking_payments.status = 'confirmed' then 1 else 0 end) as received_count,
            coalesce(sum(case when booking_payments.kind = 'refund' and booking_payments.status = 'confirmed' then booking_payments.amount end), 0) as refunded,
            sum(case when booking_payments.kind = 'refund' and booking_payments.status = 'confirmed' then 1 else 0 end) as refunded_count,
            coalesce(sum(case when booking_payments.status = 'pending' then booking_payments.amount end), 0) as pending,
            sum(case when booking_payments.status = 'pending' then 1 else 0 end) as pending_count")->first();

        return ['rows' => (int) $t->rows_count, 'received' => $t->received, 'received_count' => (int) $t->received_count, 'refunded' => $t->refunded, 'refunded_count' => (int) $t->refunded_count, 'net' => bcsub((string) $t->received, (string) $t->refunded, 2), 'pending' => $t->pending, 'pending_count' => (int) $t->pending_count];
    }

    /** Bookings that still owe money: active, on a departure, outstanding above zero. */
    private function owing(array $f): Builder
    {
        return Booking::query()
            ->join('tour_departures', 'tour_departures.id', '=', 'bookings.tour_departure_id')
            ->where('bookings.status', '!=', 'cancelled')
            ->where('bookings.outstanding_amount', '>', 0)
            ->when($f['tour'] ?? null, fn ($q, $tour) => $q->where('bookings.tour_id', $tour))
            ->when($f['trip_from'] ?? null, fn ($q, $date) => $q->where('tour_departures.departs_at', '>=', Carbon::parse($date, ActivityLogController::DISPLAY_TIMEZONE)->startOfDay()->utc()))
            ->when($f['trip_to'] ?? null, fn ($q, $date) => $q->where('tour_departures.departs_at', '<=', Carbon::parse($date, ActivityLogController::DISPLAY_TIMEZONE)->endOfDay()->utc()))
            ->when($f['due_from'] ?? null, fn ($q, $date) => $q->where('bookings.balance_due_date', '>=', $date))
            ->when($f['due_to'] ?? null, fn ($q, $date) => $q->where('bookings.balance_due_date', '<=', $date))
            ->when($f['overdue'], fn ($q) => $q->where('bookings.balance_due_date', '<', $f['today']))
            ->when($f['q'] ?? null, fn ($q, $term) => $q->where(fn ($x) => $x->where('bookings.reference', 'like', "%{$term}%")->orWhereRaw("concat_ws(' ', bookings.first_name, bookings.last_name) like ?", ["%{$term}%"])->orWhere('bookings.email', 'like', "%{$term}%")));
    }

    private const OWING_SUMS = 'count(*) as bookings_count, coalesce(sum(bookings.total_amount - bookings.discount_amount), 0) as due, coalesce(sum(bookings.amount_paid), 0) as paid, coalesce(sum(bookings.outstanding_amount), 0) as outstanding';

    private function outstandingData(array $f): array
    {
        $totals = $this->owing($f)->toBase()->selectRaw(self::OWING_SUMS)->first();
        $key = match ($f['group']) { 'customer' => 'bookings.customer_id', 'trip' => 'bookings.tour_departure_id', default => null };
        $groups = $key ? $this->owing($f)->toBase()->selectRaw("{$key} as group_id, ".self::OWING_SUMS.', min(tour_departures.departs_at) as first_departure, min(bookings.balance_due_date) as first_due')
            ->groupBy($key)->orderBy($f['group'] === 'trip' ? 'first_departure' : 'outstanding', $f['group'] === 'trip' ? 'asc' : 'desc')->get() : collect();
        $bookings = $this->owing($f)->select('bookings.*', 'tour_departures.departs_at as departs_at')->with(['customer', 'tour.translations', 'departure'])->orderBy('tour_departures.departs_at')->orderBy('bookings.id')->get();

        return ['totals' => $totals, 'groups' => $groups, 'bookings' => $bookings, 'groupKey' => $f['group'] === 'customer' ? 'customer_id' : 'tour_departure_id'];
    }

    private function overview(array $f): array
    {
        $period = $this->ledgerTotals(['from' => $f['from'], 'to' => $f['to']]);
        $owing = $this->owing(['overdue' => false, 'today' => $f['today']])->toBase()->selectRaw(self::OWING_SUMS)->first();
        $overdue = $this->owing(['overdue' => true, 'today' => $f['today']])->toBase()->selectRaw(self::OWING_SUMS)->first();
        $methods = BookingPayment::whereBetween('paid_on', [$f['from'], $f['to']])->where('status', 'confirmed')
            ->selectRaw("method, coalesce(sum(case when kind = 'payment' then amount end), 0) as received, coalesce(sum(case when kind = 'refund' then amount end), 0) as refunded, count(*) as n")
            ->groupBy('method')->orderByDesc('received')->get();
        $allPending = BookingPayment::where('status', 'pending')->selectRaw('count(*) as n, coalesce(sum(amount), 0) as total')->first();

        return ['period' => $period, 'owing' => $owing, 'overdue' => $overdue, 'methods' => $methods, 'allPending' => $allPending];
    }

    private function exportLedger(array $f): StreamedResponse
    {
        $rows = $this->ledger($f)->with(['booking.tour.translations', 'recorder'])->orderBy('booking_payments.paid_on')->orderBy('booking_payments.id')->get();
        Activity::record('payments_exported', null, ['rows' => $rows->count(), 'period' => "{$f['from']} – {$f['to']}"], label: 'Payment ledger');

        return $this->csv('dreamtour-payments-'.$f['from'].'-to-'.$f['to'].'.csv', ['Date', 'Receipt', 'Type', 'Status', 'Amount (CAD)', 'Signed amount (CAD)', 'Method', 'Reference', 'Booking', 'Customer', 'Tour', 'Recorded by', 'Note'], $rows->map(fn (BookingPayment $p) => [
            $p->paid_on->toDateString(), $p->receipt_number, $p->isRefund() ? 'Refund' : 'Payment', BookingPayment::STATUSES[$p->status][0], (string) $p->amount,
            $p->status === 'confirmed' ? ($p->isRefund() ? '-' : '').$p->amount : '0.00', BookingPayment::METHODS[$p->method] ?? $p->method, $p->reference,
            $p->booking?->reference, trim($p->booking?->first_name.' '.$p->booking?->last_name), $p->booking?->tour?->name, $p->recorder?->name ?? ($p->recorded_by ? '' : 'Customer'), $p->note ?? $p->reason,
        ]));
    }

    private function exportOutstanding(array $f): StreamedResponse
    {
        $rows = $this->owing($f)->select('bookings.*', 'tour_departures.departs_at as departs_at')->with('tour.translations')->orderBy('tour_departures.departs_at')->get();
        Activity::record('balances_exported', null, ['rows' => $rows->count()], label: 'Outstanding balances');

        return $this->csv('dreamtour-outstanding-'.$f['today'].'.csv', ['Booking', 'Customer', 'Email', 'Phone', 'Tour', 'Departure', 'Balance due date', 'Total due (CAD)', 'Paid (CAD)', 'Outstanding (CAD)', 'Payment status'], $rows->map(fn (Booking $b) => [
            $b->reference, trim($b->first_name.' '.$b->last_name), $b->email, $b->phone, $b->tour?->name, Carbon::parse($b->departs_at)->toDateString(), $b->balance_due_date?->toDateString(),
            bcsub((string) $b->total_amount, (string) $b->discount_amount, 2), (string) $b->amount_paid, (string) $b->outstanding_amount, Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status,
        ]));
    }

    private function csv(string $name, array $header, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v ?? '', $row));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
