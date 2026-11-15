@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $range = ['from' => $f['from'], 'to' => $f['to']]; // the chosen dates; $period holds that period's totals
  $tabUrl = fn (string $t, array $extra = []) => route('payments.index', array_merge(['tab' => $t], $extra));
@endphp

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
  @foreach(['overview' => 'Overview', 'ledger' => 'Ledger', 'outstanding' => 'Outstanding balances', 'pending' => 'Pending verification'] as $key => $label)
    <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ $tabUrl($key, in_array($key, ['overview', 'ledger'], true) ? $range : []) }}">{{ $label }}@if($key === 'pending' && $pendingCount) <span class="badge bg-warning">{{ $pendingCount }}</span>@endif</a></li>
  @endforeach
</ul>

@if(in_array($tab, ['overview', 'ledger'], true))
<form class="row g-2 align-items-end mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="col-auto"><label class="form-label small mb-0">From</label><input class="form-control form-control-sm" type="date" name="from" value="{{ $f['from'] }}"></div>
  <div class="col-auto"><label class="form-label small mb-0">To</label><input class="form-control form-control-sm" type="date" name="to" value="{{ $f['to'] }}"></div>
  <div class="col-auto d-flex gap-1">
    @php($now = now($tz))
    @foreach(['This month' => [$now->copy()->startOfMonth(), $now], 'Last month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()], 'This year' => [$now->copy()->startOfYear(), $now]] as $label => [$a, $b])
      <a class="btn btn-sm btn-light" href="{{ $tabUrl($tab, ['from' => $a->toDateString(), 'to' => $b->toDateString()] + request()->except(['from', 'to', 'page', 'tab'])) }}">{{ $label }}</a>
    @endforeach
  </div>
  @if($tab === 'ledger')
    <div class="col-md-2"><label class="form-label small mb-0">Search</label><input class="form-control form-control-sm" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Receipt, ref., booking, name"></div>
    <div class="col-auto"><label class="form-label small mb-0">Type</label><select class="form-select form-select-sm" name="kind"><option value="">All</option><option value="payment" @selected(($f['kind'] ?? '') === 'payment')>Payments</option><option value="refund" @selected(($f['kind'] ?? '') === 'refund')>Refunds</option></select></div>
    <div class="col-auto"><label class="form-label small mb-0">Status</label><select class="form-select form-select-sm" name="status"><option value="">All</option>@foreach(\App\Models\BookingPayment::STATUSES as $value => [$label])<option value="{{ $value }}" @selected(($f['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label small mb-0">Method</label><select class="form-select form-select-sm" name="method"><option value="">All</option>@foreach(\App\Models\BookingPayment::METHODS as $value => $label)<option value="{{ $value }}" @selected(($f['method'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small mb-0">Tour</label><select class="form-select form-select-sm" name="tour"><option value="">All tours</option>@foreach($tours as $t)<option value="{{ $t->id }}" @selected(($f['tour'] ?? null) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label small mb-0">Recorded by</label><select class="form-select form-select-sm" name="recorded_by"><option value="">Anyone</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(($f['recorded_by'] ?? null) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
  @endif
  <div class="col-auto d-flex gap-2"><button class="btn btn-sm btn-primary">Apply</button>
    @if($tab === 'ledger')<a class="btn btn-sm btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="ri-download-2-line"></i> CSV</a>@endif</div>
</form>
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
      <div class="col-md-3"><a href="{{ $url }}" class="card h-100 border-{{ $color }} text-reset text-decoration-none mb-0"><div class="card-body">
        <p class="text-muted small mb-1">{{ $label }}</p><h4 class="mb-1 text-{{ $color }}">{{ money($amount) }}</h4><p class="small text-muted mb-0">{{ $note }}</p>
      </div></a></div>
    @endforeach
  </div>
  <p class="small text-muted">Period {{ \Illuminate\Support\Carbon::parse($f['from'])->format('M j, Y') }} – {{ \Illuminate\Support\Carbon::parse($f['to'])->format('M j, Y') }}, by the date money was received. Only confirmed payments and refunds count; pending, rejected and voided ones never do.</p>

  <div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
      <h5 class="card-title">Still owed today</h5>
      <table class="table table-sm mb-0">
        <tr><td>Outstanding on active bookings</td><td class="text-end"><a href="{{ $tabUrl('outstanding') }}" class="fw-semibold">{{ money($owing->outstanding) }}</a></td><td class="text-end text-muted small">{{ $owing->bookings_count }} bookings</td></tr>
        <tr class="text-danger"><td>…of which overdue</td><td class="text-end"><a href="{{ $tabUrl('outstanding', ['overdue' => 1]) }}" class="fw-semibold text-danger">{{ money($overdue->outstanding) }}</a></td><td class="text-end small">{{ $overdue->bookings_count }} bookings</td></tr>
      </table>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
      <h5 class="card-title">By payment method (period)</h5>
      <table class="table table-sm mb-0">
        <thead><tr><th>Method</th><th class="text-end">Received</th><th class="text-end">Refunded</th><th class="text-end">Net</th></tr></thead>
        @forelse($methods as $m)
          <tr><td><a href="{{ $tabUrl('ledger', $range + ['method' => $m->method, 'status' => 'confirmed']) }}">{{ \App\Models\BookingPayment::METHODS[$m->method] ?? $m->method }}</a></td><td class="text-end">{{ money($m->received) }}</td><td class="text-end">{{ money($m->refunded) }}</td><td class="text-end fw-semibold">{{ money(bcsub((string) $m->received, (string) $m->refunded, 2)) }}</td></tr>
        @empty
          <tr><td colspan="4" class="text-muted small">No confirmed payments in this period.</td></tr>
        @endforelse
      </table>
    </div></div></div>
  </div>

@elseif($tab === 'ledger')
  <div class="row g-2 mb-2">
    @foreach([['Received', $totals['received'], $totals['received_count'], 'success'], ['Refunded', $totals['refunded'], $totals['refunded_count'], 'danger'], ['Net', $totals['net'], null, 'primary'], ['Pending (not counted)', $totals['pending'], $totals['pending_count'], 'warning']] as [$label, $amount, $n, $color])
      <div class="col-6 col-md-3"><div class="border rounded px-3 py-2"><span class="small text-muted">{{ $label }}{{ $n !== null ? ' · '.$n : '' }}</span><div class="fw-bold text-{{ $color }}">{{ money($amount) }}</div></div></div>
    @endforeach
  </div>
  <div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Date</th><th>Receipt</th><th>Type</th><th>Booking</th><th>Customer</th><th>Method</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($rows as $p)
        @php([$statusLabel, $statusColor] = \App\Models\BookingPayment::STATUSES[$p->status])
        <tr class="{{ in_array($p->status, ['voided', 'rejected'], true) ? 'text-muted' : '' }}">
          <td class="small text-nowrap">{{ $p->paid_on->format('M j, Y') }}</td>
          <td class="small">{{ $p->receipt_number ?? '—' }}</td>
          <td class="small">{{ $p->isRefund() ? 'Refund' : 'Payment' }}</td>
          <td class="small"><a href="{{ route('bookings.show', $p->booking_id) }}">{{ $p->booking?->reference }}</a><br><span class="text-muted">{{ str($p->booking?->tour?->name)->limit(28) }}</span></td>
          <td class="small">{{ trim($p->booking?->first_name.' '.$p->booking?->last_name) }}</td>
          <td class="small">{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}@if($p->reference)<br><span class="text-muted">{{ $p->reference }}</span>@endif</td>
          <td class="text-end text-nowrap {{ in_array($p->status, ['voided', 'rejected'], true) ? 'text-decoration-line-through' : ($p->isRefund() ? 'text-danger' : '') }}">{{ $p->isRefund() ? '−' : '' }}{{ money($p->amount, $p->currency) }}</td>
          <td><span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span></td>
          <td class="text-end">@if($p->status === 'confirmed' && $p->receipt_number)<a class="btn btn-sm btn-outline-primary py-0" href="{{ route('receipts.payment', [$p->booking_id, $p]) }}" target="_blank">Receipt</a>@endif</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-muted">Nothing matches these filters.</td></tr>
      @endforelse
      </tbody>
      @if($totals['rows'])
      <tfoot><tr class="fw-bold"><td colspan="6" class="text-end">{{ $totals['rows'] }} {{ str('entry')->plural($totals['rows']) }} · net confirmed</td><td class="text-end">{{ money($totals['net']) }}</td><td colspan="2"></td></tr></tfoot>
      @endif
    </table></div>
    {{ $rows->links('admin.vendor.pagination.bootstrap-5') }}
  </div></div>

@elseif($tab === 'outstanding')
  <form class="row g-2 align-items-end mb-3">
    <input type="hidden" name="tab" value="outstanding">
    <div class="col-md-2"><label class="form-label small mb-0">Search</label><input class="form-control form-control-sm" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Booking, name, email"></div>
    <div class="col-md-2"><label class="form-label small mb-0">Tour</label><select class="form-select form-select-sm" name="tour"><option value="">All tours</option>@foreach($tours as $t)<option value="{{ $t->id }}" @selected(($f['tour'] ?? null) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label small mb-0">Trip from</label><input class="form-control form-control-sm" type="date" name="trip_from" value="{{ $f['trip_from'] ?? '' }}"></div>
    <div class="col-auto"><label class="form-label small mb-0">Trip to</label><input class="form-control form-control-sm" type="date" name="trip_to" value="{{ $f['trip_to'] ?? '' }}"></div>
    <div class="col-auto"><label class="form-label small mb-0">Due from</label><input class="form-control form-control-sm" type="date" name="due_from" value="{{ $f['due_from'] ?? '' }}"></div>
    <div class="col-auto"><label class="form-label small mb-0">Due to</label><input class="form-control form-control-sm" type="date" name="due_to" value="{{ $f['due_to'] ?? '' }}"></div>
    <div class="col-auto"><label class="form-label small mb-0">Group by</label><select class="form-select form-select-sm" name="group"><option value="trip" @selected($f['group'] === 'trip')>Trip</option><option value="customer" @selected($f['group'] === 'customer')>Customer</option><option value="none" @selected($f['group'] === 'none')>No grouping</option></select></div>
    <div class="col-auto"><label class="small"><input type="checkbox" name="overdue" value="1" @checked($f['overdue'])> Overdue only</label></div>
    <div class="col-auto d-flex gap-2"><button class="btn btn-sm btn-primary">Apply</button><a class="btn btn-sm btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="ri-download-2-line"></i> CSV</a></div>
  </form>
  <div class="row g-2 mb-3">
    @foreach([['Bookings', $totals->bookings_count, false], ['Total due', $totals->due, true], ['Paid', $totals->paid, true], ['Outstanding', $totals->outstanding, true]] as [$label, $value, $isMoney])
      <div class="col-6 col-md-3"><div class="border rounded px-3 py-2"><span class="small text-muted">{{ $label }}</span><div class="fw-bold {{ $label === 'Outstanding' ? 'text-danger' : '' }}">{{ $isMoney ? money($value) : $value }}</div></div></div>
    @endforeach
  </div>
  @php($byGroup = $bookings->groupBy($groupKey))
  <div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Booking</th><th>Customer</th><th>Trip</th><th>Due date</th><th class="text-end">Total due</th><th class="text-end">Paid</th><th class="text-end">Outstanding</th><th>Status</th></tr></thead>
      @forelse($f['group'] === 'none' ? collect([null => $bookings]) : $groups->mapWithKeys(fn ($g) => [$g->group_id => $byGroup[$g->group_id] ?? collect()]) as $groupId => $list)
        <tbody>
        @if($f['group'] !== 'none')
          @php($g = $groups->firstWhere('group_id', $groupId))
          @php($first = $list->first())
          <tr class="table-light"><td colspan="4" class="fw-semibold">
            @if($f['group'] === 'trip'){{ $first?->tour?->name }} · {{ $first?->departure?->departs_at?->format('D, M j, Y') }}
            @else{{ $first?->customer?->full_name ?? trim($first?->first_name.' '.$first?->last_name) }}@if($first?->customer_id) <a class="small fw-normal" href="{{ route('customers.show', $first->customer_id) }}">profile</a>@endif @endif
            <span class="text-muted fw-normal small">· {{ $g->bookings_count }} {{ str('booking')->plural($g->bookings_count) }}</span></td>
            <td class="text-end">{{ money($g->due) }}</td><td class="text-end">{{ money($g->paid) }}</td><td class="text-end fw-bold text-danger">{{ money($g->outstanding) }}</td><td></td></tr>
        @endif
        @foreach($list as $b)
          @php($overdue = $b->balance_due_date && $b->balance_due_date->toDateString() < $f['today'])
          <tr>
            <td><a href="{{ route('bookings.show', $b) }}">{{ $b->reference }}</a></td>
            <td class="small">{{ trim($b->first_name.' '.$b->last_name) }}<br><span class="text-muted">{{ $b->phone }}</span></td>
            <td class="small">{{ str($b->tour?->name)->limit(30) }}<br><span class="text-muted">{{ \Illuminate\Support\Carbon::parse($b->departs_at)->timezone($tz)->format('M j, Y') }}</span></td>
            <td class="small {{ $overdue ? 'text-danger fw-semibold' : '' }}">{{ $b->balance_due_date?->format('M j, Y') ?? '—' }}{{ $overdue ? ' · overdue' : '' }}</td>
            <td class="text-end">{{ money(bcsub((string) $b->total_amount, (string) $b->discount_amount, 2), $b->currency) }}</td>
            <td class="text-end">{{ money($b->amount_paid, $b->currency) }}</td>
            <td class="text-end fw-semibold text-danger">{{ money($b->outstanding_amount, $b->currency) }}</td>
            <td><span class="badge bg-light text-body">{{ \App\Models\Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status }}</span></td>
          </tr>
        @endforeach
        </tbody>
      @empty
        <tbody><tr><td colspan="8" class="text-muted">Nothing is owed{{ collect($f)->only(['q', 'tour', 'trip_from', 'trip_to', 'due_from', 'due_to'])->filter()->isNotEmpty() || $f['overdue'] ? ' for these filters' : '' }}.</td></tr></tbody>
      @endforelse
      @if($totals->bookings_count)
      <tfoot><tr class="fw-bold" style="border-top:2px solid #333"><td colspan="4" class="text-end">Total · {{ $totals->bookings_count }} {{ str('booking')->plural($totals->bookings_count) }}</td><td class="text-end">{{ money($totals->due) }}</td><td class="text-end">{{ money($totals->paid) }}</td><td class="text-end text-danger">{{ money($totals->outstanding) }}</td><td></td></tr></tfoot>
      @endif
    </table></div>
  </div></div>

@else
  <div class="card"><div class="card-body">
    <p class="text-muted small">Payments a customer says they made (for example the deposit screenshot sent with a booking) or that staff recorded without checking. They don't reduce any balance until confirmed.</p>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Booking</th><th>Customer</th><th>Trip</th><th>Method</th><th class="text-end">Amount</th><th></th></tr></thead>
      <tbody>
      @forelse($rows as $p)
        <tr>
          <td class="small text-nowrap">{{ $p->paid_on->format('M j, Y') }}<br><span class="text-muted">{{ $p->paid_on->isToday() ? 'today' : $p->paid_on->diffForHumans() }}</span></td>
          <td><a href="{{ route('bookings.show', $p->booking_id) }}">{{ $p->booking?->reference }}</a></td>
          <td class="small">{{ trim($p->booking?->first_name.' '.$p->booking?->last_name) }}<br><span class="text-muted">{{ $p->booking?->email }}</span></td>
          <td class="small">{{ str($p->booking?->tour?->name)->limit(30) }}<br><span class="text-muted">{{ $p->booking?->departure?->departs_at?->format('M j, Y') }}</span></td>
          <td class="small">{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}@if($p->note)<br><span class="text-muted">{{ str($p->note)->limit(40) }}</span>@endif</td>
          <td class="text-end fw-semibold">{{ money($p->amount, $p->currency) }}</td>
          <td class="text-end text-nowrap">
            @if($p->proof_path)<a class="btn btn-sm btn-light" target="_blank" href="{{ route('booking-payments.proof', [$p->booking_id, $p]) }}" title="View proof"><i class="ri-attachment-2"></i> Proof</a>@endif
            @can('confirm-payments')
              <form class="d-inline" method="post" action="{{ route('booking-payments.confirm', [$p->booking_id, $p]) }}" onsubmit="return confirm('Confirm {{ money($p->amount, $p->currency) }} arrived for {{ $p->booking?->reference }}?')">@csrf<button class="btn btn-sm btn-success"><i class="ri-check-line"></i> Confirm</button></form>
              <a class="btn btn-sm btn-outline-danger" href="{{ route('bookings.show', $p->booking_id) }}" title="Reject on the booking page (a reason is required)"><i class="ri-close-line"></i></a>
            @endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-muted text-center py-4"><i class="ri-check-double-line fs-3 d-block"></i>Nothing waiting for verification.</td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div></div>
@endif
</div></div></div>@include('admin.includes.footer')
