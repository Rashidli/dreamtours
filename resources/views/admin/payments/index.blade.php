@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $range = ['from' => $f['from'], 'to' => $f['to']]; // the chosen dates; $period holds that period's totals
  $tabUrl = fn (string $t, array $extra = []) => route('payments.index', array_merge(['tab' => $t], $extra));
  $now = now($tz);
  $presets = ['This month' => [$now->copy()->startOfMonth(), $now], 'Last month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()], 'This year' => [$now->copy()->startOfYear(), $now]];
  $statusBadge = fn (string $status) => '<span class="badge rounded-pill bg-'.\App\Models\BookingPayment::STATUSES[$status][1].'">'.\App\Models\BookingPayment::STATUSES[$status][0].'</span>';
@endphp
<style>
  /* Payments & balances: readable sizes on the 0.9rem theme; no -sm controls or tiny text for data. */
  .pay-tile { border: 1px solid #e9ebef; border-radius: 10px; padding: 14px 16px; height: 100%; background: #fff; display: block; color: inherit; transition: box-shadow .15s; }
  a.pay-tile:hover { box-shadow: 0 4px 14px rgba(20, 24, 36, .08); color: inherit; }
  .pay-tile .label { color: #74788d; font-size: .85rem; margin-bottom: 4px; }
  .pay-tile .value { font-size: 1.45rem; font-weight: 700; line-height: 1.2; }
  .pay-tile .note { color: #74788d; font-size: .8rem; margin-top: 2px; }
  .pay-filters .form-label { font-weight: 600; font-size: .8rem; color: #495057; margin-bottom: 4px; }
  .pay-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .4px; color: #74788d; font-weight: 600; white-space: nowrap; }
  .pay-table td { vertical-align: middle; }
  .pay-table .sub { display: block; color: #74788d; font-size: .82rem; margin-top: 1px; }
  .pay-table .amount { font-weight: 700; white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; }
  .pay-table tfoot td { font-weight: 700; border-top: 2px solid #343a40; }
  .pay-group td { background: #f6f7f9 !important; font-weight: 600; }
</style>

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Payments & balances</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Payments & balances</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

<ul class="nav nav-tabs mb-3">
  @foreach(['overview' => ['Overview', 'ri-dashboard-line'], 'ledger' => ['Ledger', 'ri-file-list-3-line'], 'outstanding' => ['Outstanding balances', 'ri-error-warning-line'], 'pending' => ['Pending verification', 'ri-time-line']] as $key => [$label, $icon])
    <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ $tabUrl($key, in_array($key, ['overview', 'ledger'], true) ? $range : []) }}"><i class="{{ $icon }} me-1"></i>{{ $label }}@if($key === 'pending' && $pendingCount) <span class="badge rounded-pill bg-warning ms-1">{{ $pendingCount }}</span>@endif</a></li>
  @endforeach
</ul>

@if(in_array($tab, ['overview', 'ledger'], true))
{{-- Period first (it drives every figure), then the ledger's own filters. --}}
<div class="card pay-filters"><div class="card-body">
  <form>
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="row g-3 align-items-end">
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="f-from">From</label><input class="form-control" type="date" id="f-from" name="from" value="{{ $f['from'] }}"></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="f-to">To</label><input class="form-control" type="date" id="f-to" name="to" value="{{ $f['to'] }}"></div>
      <div class="col-md-6 col-xl-4"><label class="form-label d-block">Quick periods</label><div class="btn-group flex-wrap" role="group">
        @foreach($presets as $label => [$a, $b])
          @php($active = $f['from'] === $a->toDateString() && $f['to'] === $b->toDateString())
          <a class="btn {{ $active ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ $tabUrl($tab, ['from' => $a->toDateString(), 'to' => $b->toDateString()] + request()->except(['from', 'to', 'page', 'tab'])) }}">{{ $label }}</a>
        @endforeach
      </div></div>
      @if($tab === 'overview')<div class="col-auto"><button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Apply</button></div>@endif
    </div>
    @if($tab === 'ledger')
    <hr class="my-3">
    <div class="row g-3 align-items-end">
      <div class="col-md-6 col-xl-3"><label class="form-label" for="f-q">Search</label><input class="form-control" id="f-q" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Receipt, reference, booking or name"></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="f-kind">Type</label><select class="form-select" id="f-kind" name="kind"><option value="">Payments & refunds</option><option value="payment" @selected(($f['kind'] ?? '') === 'payment')>Payments</option><option value="refund" @selected(($f['kind'] ?? '') === 'refund')>Refunds</option></select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="f-status">Status</label><select class="form-select" id="f-status" name="status"><option value="">Any status</option>@foreach(\App\Models\BookingPayment::STATUSES as $value => [$label])<option value="{{ $value }}" @selected(($f['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="f-method">Method</label><select class="form-select" id="f-method" name="method"><option value="">Any method</option>@foreach(\App\Models\BookingPayment::METHODS as $value => $label)<option value="{{ $value }}" @selected(($f['method'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-3"><label class="form-label" for="f-tour">Tour</label><select class="form-select" id="f-tour" name="tour"><option value="">All tours</option>@foreach($tours as $t)<option value="{{ $t->id }}" @selected(($f['tour'] ?? null) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="f-by">Recorded by</label><select class="form-select" id="f-by" name="recorded_by"><option value="">Anyone</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(($f['recorded_by'] ?? null) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
      <div class="col d-flex flex-wrap gap-2 justify-content-end">
        <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Apply</button>
        <a class="btn btn-light" href="{{ $tabUrl('ledger', $range) }}">Reset</a>
        <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="ri-download-2-line me-1"></i>Export CSV</a>
      </div>
    </div>
    @endif
  </form>
</div></div>
@endif

@if($tab === 'overview')
  {{-- Each figure opens the exact records behind it (§2). --}}
  <div class="row g-3 mb-3">
    @foreach([
      ['Received', $period['received'], $period['received_count'].' payments', 'success', $tabUrl('ledger', $range + ['kind' => 'payment', 'status' => 'confirmed'])],
      ['Refunded', $period['refunded'], $period['refunded_count'].' refunds', 'danger', $tabUrl('ledger', $range + ['kind' => 'refund', 'status' => 'confirmed'])],
      ['Net collected', $period['net'], 'received − refunded', 'primary', $tabUrl('ledger', $range + ['status' => 'confirmed'])],
      ['Waiting for verification', $allPending->total, $allPending->n.' payments · all dates', 'warning', $tabUrl('pending')],
    ] as [$label, $amount, $note, $color, $url])
      <div class="col-sm-6 col-xl-3"><a href="{{ $url }}" class="pay-tile" style="border-top:3px solid var(--bs-{{ $color }})">
        <div class="label">{{ $label }}</div><div class="value text-{{ $color }}">{{ money($amount) }}</div><div class="note">{{ $note }} <i class="ri-arrow-right-s-line"></i></div>
      </a></div>
    @endforeach
  </div>
  <p class="text-muted">{{ \Illuminate\Support\Carbon::parse($f['from'])->format('M j, Y') }} – {{ \Illuminate\Support\Carbon::parse($f['to'])->format('M j, Y') }}, by the date money was received. Only confirmed payments and refunds count; pending, rejected and voided ones never do.</p>

  <div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
      <h5 class="card-title mb-3">Still owed today</h5>
      <table class="table pay-table mb-0">
        <tr><td>Outstanding on active bookings<span class="sub">{{ $owing->bookings_count }} bookings</span></td><td class="amount"><a href="{{ $tabUrl('outstanding') }}">{{ money($owing->outstanding) }}</a></td></tr>
        <tr><td class="text-danger">…of which overdue<span class="sub">{{ $overdue->bookings_count }} bookings</span></td><td class="amount"><a class="text-danger" href="{{ $tabUrl('outstanding', ['overdue' => 1]) }}">{{ money($overdue->outstanding) }}</a></td></tr>
      </table>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
      <h5 class="card-title mb-3">By payment method</h5>
      <table class="table pay-table mb-0">
        <thead><tr><th>Method</th><th class="text-end">Received</th><th class="text-end">Refunded</th><th class="text-end">Net</th></tr></thead>
        @forelse($methods as $m)
          <tr><td><a href="{{ $tabUrl('ledger', $range + ['method' => $m->method, 'status' => 'confirmed']) }}">{{ \App\Models\BookingPayment::METHODS[$m->method] ?? $m->method }}</a></td><td class="amount fw-normal">{{ money($m->received) }}</td><td class="amount fw-normal">{{ money($m->refunded) }}</td><td class="amount">{{ money(bcsub((string) $m->received, (string) $m->refunded, 2)) }}</td></tr>
        @empty
          <tr><td colspan="4" class="text-muted">No confirmed payments in this period.</td></tr>
        @endforelse
      </table>
    </div></div></div>
  </div>

@elseif($tab === 'ledger')
  <div class="row g-3 mb-3">
    @foreach([['Received', $totals['received'], $totals['received_count'].' confirmed payments', 'success'], ['Refunded', $totals['refunded'], $totals['refunded_count'].' refunds', 'danger'], ['Net', $totals['net'], 'received − refunded', 'primary'], ['Pending (not counted)', $totals['pending'], $totals['pending_count'].' waiting', 'warning']] as [$label, $amount, $note, $color])
      <div class="col-sm-6 col-xl-3"><div class="pay-tile" style="border-top:3px solid var(--bs-{{ $color }})"><div class="label">{{ $label }}</div><div class="value text-{{ $color }}">{{ money($amount) }}</div><div class="note">{{ $note }}</div></div></div>
    @endforeach
  </div>
  <div class="card"><div class="card-body">
    <h5 class="card-title mb-3">Transactions <span class="text-muted fw-normal">({{ $totals['rows'] }})</span></h5>
    <div class="table-responsive"><table class="table table-hover pay-table mb-0">
      <thead><tr><th>Date</th><th>Receipt</th><th>Booking · customer</th><th>Method</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($rows as $p)
        @php($closed = in_array($p->status, ['voided', 'rejected'], true))
        <tr class="{{ $closed ? 'text-muted' : '' }}">
          <td class="text-nowrap">{{ $p->paid_on->format('M j, Y') }}</td>
          <td class="text-nowrap">{{ $p->receipt_number ?? '—' }}@if($p->isRefund())<span class="sub"><span class="badge bg-danger bg-opacity-10 text-danger">Refund</span></span>@endif</td>
          <td><a href="{{ route('bookings.show', $p->booking_id) }}" class="fw-semibold">{{ $p->booking?->reference }}</a> · {{ trim($p->booking?->first_name.' '.$p->booking?->last_name) }}<span class="sub text-truncate" style="max-width:320px">{{ $p->booking?->tour?->name }}</span></td>
          <td>{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}@if($p->reference)<span class="sub">{{ $p->reference }}</span>@endif</td>
          <td class="amount {{ $closed ? 'text-decoration-line-through' : ($p->isRefund() ? 'text-danger' : '') }}">{{ $p->isRefund() ? '−' : '' }}{{ money($p->amount, $p->currency) }}</td>
          <td>{!! $statusBadge($p->status) !!}</td>
          <td class="text-end">@if($p->status === 'confirmed' && $p->receipt_number)<a class="btn btn-sm btn-outline-primary" href="{{ route('receipts.payment', [$p->booking_id, $p]) }}" target="_blank"><i class="ri-file-text-line"></i> Receipt</a>@endif</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center text-muted py-4"><i class="ri-search-line fs-3 d-block mb-1"></i>Nothing matches these filters.</td></tr>
      @endforelse
      </tbody>
      @if($totals['rows'])
      <tfoot><tr><td colspan="4" class="text-end">{{ $totals['rows'] }} {{ str('entry')->plural($totals['rows']) }} · net confirmed</td><td class="amount">{{ money($totals['net']) }}</td><td colspan="2"></td></tr></tfoot>
      @endif
    </table></div>
    <div class="mt-3">{{ $rows->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

@elseif($tab === 'outstanding')
  <div class="card pay-filters"><div class="card-body">
    <form class="row g-3 align-items-end">
      <input type="hidden" name="tab" value="outstanding">
      <div class="col-md-6 col-xl-3"><label class="form-label" for="o-q">Search</label><input class="form-control" id="o-q" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Booking, name or email"></div>
      <div class="col-md-6 col-xl-3"><label class="form-label" for="o-tour">Tour</label><select class="form-select" id="o-tour" name="tour"><option value="">All tours</option>@foreach($tours as $t)<option value="{{ $t->id }}" @selected(($f['tour'] ?? null) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-xl-3"><label class="form-label">Trip date</label><div class="input-group"><input class="form-control" type="date" name="trip_from" value="{{ $f['trip_from'] ?? '' }}" aria-label="Trip from"><span class="input-group-text">–</span><input class="form-control" type="date" name="trip_to" value="{{ $f['trip_to'] ?? '' }}" aria-label="Trip to"></div></div>
      <div class="col-sm-6 col-xl-3"><label class="form-label">Balance due date</label><div class="input-group"><input class="form-control" type="date" name="due_from" value="{{ $f['due_from'] ?? '' }}" aria-label="Due from"><span class="input-group-text">–</span><input class="form-control" type="date" name="due_to" value="{{ $f['due_to'] ?? '' }}" aria-label="Due to"></div></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="o-group">Group by</label><select class="form-select" id="o-group" name="group"><option value="trip" @selected($f['group'] === 'trip')>Trip</option><option value="customer" @selected($f['group'] === 'customer')>Customer</option><option value="none" @selected($f['group'] === 'none')>No grouping</option></select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="o-overdue" name="overdue" value="1" @checked($f['overdue'])><label class="form-check-label" for="o-overdue">Overdue only</label></div></div>
      <div class="col d-flex flex-wrap gap-2 justify-content-end">
        <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Apply</button>
        <a class="btn btn-light" href="{{ $tabUrl('outstanding') }}">Reset</a>
        <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="ri-download-2-line me-1"></i>Export CSV</a>
      </div>
    </form>
  </div></div>
  <div class="row g-3 mb-3">
    @foreach([['Bookings owing', $totals->bookings_count, false, 'secondary'], ['Total due', $totals->due, true, 'secondary'], ['Paid', $totals->paid, true, 'success'], ['Outstanding', $totals->outstanding, true, 'danger']] as [$label, $value, $isMoney, $color])
      <div class="col-sm-6 col-xl-3"><div class="pay-tile" style="border-top:3px solid var(--bs-{{ $color }})"><div class="label">{{ $label }}</div><div class="value {{ $color === 'danger' ? 'text-danger' : '' }}">{{ $isMoney ? money($value) : $value }}</div></div></div>
    @endforeach
  </div>
  @php($byGroup = $bookings->groupBy($groupKey))
  <div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table pay-table mb-0">
      <thead><tr><th>Booking · customer</th><th>Trip</th><th>Balance due</th><th class="text-end">Total due</th><th class="text-end">Paid</th><th class="text-end">Outstanding</th><th>Status</th></tr></thead>
      @forelse($f['group'] === 'none' ? collect([null => $bookings]) : $groups->mapWithKeys(fn ($g) => [$g->group_id => $byGroup[$g->group_id] ?? collect()]) as $groupId => $list)
        <tbody>
        @if($f['group'] !== 'none')
          @php($g = $groups->firstWhere('group_id', $groupId))
          @php($first = $list->first())
          <tr class="pay-group"><td colspan="3">
            @if($f['group'] === 'trip')<i class="ri-bus-line me-1"></i>{{ $first?->tour?->name }} · {{ $first?->departure?->departs_at?->format('D, M j, Y') }}
            @else<i class="ri-user-line me-1"></i>{{ $first?->customer?->full_name ?? trim($first?->first_name.' '.$first?->last_name) }}@if($first?->customer_id) <a class="fw-normal ms-1" href="{{ route('customers.show', $first->customer_id) }}">profile</a>@endif @endif
            <span class="text-muted fw-normal ms-1">· {{ $g->bookings_count }} {{ str('booking')->plural($g->bookings_count) }}</span></td>
            <td class="amount fw-semibold">{{ money($g->due) }}</td><td class="amount fw-semibold">{{ money($g->paid) }}</td><td class="amount text-danger">{{ money($g->outstanding) }}</td><td></td></tr>
        @endif
        @foreach($list as $b)
          @php($overdue = $b->balance_due_date && $b->balance_due_date->toDateString() < $f['today'])
          <tr>
            <td><a href="{{ route('bookings.show', $b) }}" class="fw-semibold">{{ $b->reference }}</a> · {{ trim($b->first_name.' '.$b->last_name) }}<span class="sub">{{ $b->phone }}</span></td>
            <td>{{ str($b->tour?->name)->limit(34) }}<span class="sub">{{ \Illuminate\Support\Carbon::parse($b->departs_at)->timezone($tz)->format('D, M j, Y') }}</span></td>
            <td class="text-nowrap {{ $overdue ? 'text-danger fw-semibold' : '' }}">{{ $b->balance_due_date?->format('M j, Y') ?? '—' }}@if($overdue)<span class="sub text-danger">overdue</span>@endif</td>
            <td class="amount fw-normal">{{ money(bcsub((string) $b->total_amount, (string) $b->discount_amount, 2), $b->currency) }}</td>
            <td class="amount fw-normal">{{ money($b->amount_paid, $b->currency) }}</td>
            <td class="amount text-danger">{{ money($b->outstanding_amount, $b->currency) }}</td>
            <td><span class="badge rounded-pill bg-light text-body">{{ \App\Models\Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status }}</span></td>
          </tr>
        @endforeach
        </tbody>
      @empty
        <tbody><tr><td colspan="7" class="text-center text-muted py-4"><i class="ri-check-double-line fs-3 d-block mb-1"></i>Nothing is owed{{ collect($f)->only(['q', 'tour', 'trip_from', 'trip_to', 'due_from', 'due_to'])->filter()->isNotEmpty() || $f['overdue'] ? ' for these filters' : '' }}.</td></tr></tbody>
      @endforelse
      @if($totals->bookings_count)
      <tfoot><tr><td colspan="3" class="text-end">Total · {{ $totals->bookings_count }} {{ str('booking')->plural($totals->bookings_count) }}</td><td class="amount">{{ money($totals->due) }}</td><td class="amount">{{ money($totals->paid) }}</td><td class="amount text-danger">{{ money($totals->outstanding) }}</td><td></td></tr></tfoot>
      @endif
    </table></div>
  </div></div>

@else
  <div class="card"><div class="card-body">
    <h5 class="card-title mb-1">Waiting for verification</h5>
    <p class="text-muted">Payments a customer says they made (for example the deposit screenshot sent with a booking) or that staff recorded without checking. They don't reduce any balance until confirmed.</p>
    <div class="table-responsive"><table class="table table-hover pay-table mb-0">
      <thead><tr><th>Date</th><th>Booking · customer</th><th>Trip</th><th>Method</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @forelse($rows as $p)
        <tr>
          <td class="text-nowrap">{{ $p->paid_on->format('M j, Y') }}<span class="sub">{{ $p->paid_on->isToday() ? 'today' : $p->paid_on->diffForHumans() }}</span></td>
          <td><a href="{{ route('bookings.show', $p->booking_id) }}" class="fw-semibold">{{ $p->booking?->reference }}</a> · {{ trim($p->booking?->first_name.' '.$p->booking?->last_name) }}<span class="sub">{{ $p->booking?->email }}</span></td>
          <td>{{ str($p->booking?->tour?->name)->limit(34) }}<span class="sub">{{ $p->booking?->departure?->departs_at?->format('D, M j, Y') }}</span></td>
          <td>{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}@if($p->note)<span class="sub">{{ str($p->note)->limit(48) }}</span>@endif</td>
          <td class="amount">{{ money($p->amount, $p->currency) }}</td>
          <td class="text-end text-nowrap">
            @if($p->proof_path)<a class="btn btn-sm btn-light" target="_blank" href="{{ route('booking-payments.proof', [$p->booking_id, $p]) }}" title="View proof"><i class="ri-attachment-2"></i> Proof</a>@endif
            @can('confirm-payments')
              <form class="d-inline" method="post" action="{{ route('booking-payments.confirm', [$p->booking_id, $p]) }}" onsubmit="return confirm('Confirm {{ money($p->amount, $p->currency) }} arrived for {{ $p->booking?->reference }}?')">@csrf<button class="btn btn-sm btn-success"><i class="ri-check-line"></i> Confirm</button></form>
              <button type="button" class="btn btn-sm btn-outline-danger" data-reject="{{ route('booking-payments.reject', [$p->booking_id, $p]) }}" data-what="{{ money($p->amount, $p->currency) }} · {{ $p->booking?->reference }} · {{ trim($p->booking?->first_name.' '.$p->booking?->last_name) }}" title="Funds did not arrive"><i class="ri-close-line"></i> Reject</button>
            @endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-muted text-center py-4"><i class="ri-check-double-line fs-3 d-block mb-1"></i>Nothing waiting for verification.</td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div></div>

  @can('confirm-payments')
  {{-- Reject right here: a reason is required, and the list stays open afterwards. --}}
  <div class="modal fade" id="rejectPayment" tabindex="-1" aria-labelledby="rejectPaymentTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="">@csrf
    <div class="modal-header"><h5 class="modal-title" id="rejectPaymentTitle">Reject this payment?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <p class="text-muted mb-2" data-what></p>
      <div class="d-flex flex-wrap gap-1 mb-3">@foreach(['Not received in the bank account', 'Wrong amount', 'Screenshot unreadable', 'Duplicate of another payment'] as $reason)<button type="button" class="btn btn-sm btn-light" data-reason="{{ $reason }}">{{ $reason }}</button>@endforeach</div>
      <label class="form-label" for="reject-reason">Reason</label><input class="form-control" id="reject-reason" name="reason" maxlength="255" required>
      <p class="text-muted mt-2 mb-0">It stays in the booking's ledger as rejected and no longer counts as pending.</p>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject payment</button></div>
  </form></div></div>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('rejectPayment');
    const form = el.querySelector('form');
    el.querySelectorAll('[data-reason]').forEach(b => b.addEventListener('click', () => { form.reason.value = b.dataset.reason; form.reason.focus(); }));
    document.querySelectorAll('[data-reject]').forEach(btn => btn.addEventListener('click', () => {
      form.action = btn.dataset.reject;
      form.reason.value = '';
      el.querySelector('[data-what]').textContent = btn.dataset.what;
      bootstrap.Modal.getOrCreateInstance(el).show();
      setTimeout(() => form.reason.focus(), 300);
    }));
  });
  </script>
  @endcan
@endif
</div></div></div>@include('admin.includes.footer')
