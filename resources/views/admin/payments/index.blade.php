@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-payments.css') }}?v={{ @filemtime(public_path('assets/css/admin-payments.css')) }}">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $range = ['from' => $f['from'], 'to' => $f['to']]; // the chosen dates; $period holds that period's totals
  $tabUrl = fn (string $t, array $extra = []) => route('payments.index', array_merge(['tab' => $t], $extra));
  $now = now($tz);
  $presets = ['This month' => [$now->copy()->startOfMonth(), $now], 'Last month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()], 'This year' => [$now->copy()->startOfYear(), $now]];
  $statusBadge = fn (string $status) => '<span class="badge bg-'.\App\Models\BookingPayment::STATUSES[$status][1].'">'.\App\Models\BookingPayment::STATUSES[$status][0].'</span>';
  $fmtDate = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M j, Y');
  $periodLabel = $fmtDate($f['from']).' – '.$fmtDate($f['to']);
  $customPeriod = collect($presets)->every(fn ($p) => !($f['from'] === $p[0]->toDateString() && $f['to'] === $p[1]->toDateString()));

  // Active filters as removable chips (ledger and outstanding tabs); removing one keeps the others.
  $chips = collect();
  $chipUrl = fn (array $drop) => $tabUrl($tab, collect(request()->except(array_merge($drop, ['page', 'tab', 'export'])))->filter(fn ($v) => $v !== null && $v !== '')->all());
  if (in_array($tab, ['ledger', 'outstanding'], true) && filled($f['q'] ?? null)) $chips->push(['Search: “'.$f['q'].'”', $chipUrl(['q'])]);
  if (in_array($tab, ['ledger', 'outstanding'], true) && filled($f['tour'] ?? null)) $chips->push(['Tour: '.($tours->firstWhere('id', (int) $f['tour'])?->name ?? '#'.$f['tour']), $chipUrl(['tour'])]);
  if ($tab === 'ledger') {
    if (filled($f['kind'] ?? null)) $chips->push([$f['kind'] === 'refund' ? 'Refunds only' : 'Payments only', $chipUrl(['kind'])]);
    if (filled($f['status'] ?? null)) $chips->push(['Status: '.\App\Models\BookingPayment::STATUSES[$f['status']][0], $chipUrl(['status'])]);
    if (filled($f['method'] ?? null)) $chips->push(['Method: '.(\App\Models\BookingPayment::METHODS[$f['method']] ?? $f['method']), $chipUrl(['method'])]);
    if (filled($f['recorded_by'] ?? null)) $chips->push(['Recorded by: '.($users->firstWhere('id', (int) $f['recorded_by'])?->name ?? '#'.$f['recorded_by']), $chipUrl(['recorded_by'])]);
  }
  if ($tab === 'outstanding') {
    if (filled($f['trip_from'] ?? null) || filled($f['trip_to'] ?? null)) $chips->push(['Trip: '.(filled($f['trip_from'] ?? null) ? $fmtDate($f['trip_from']) : '…').' – '.(filled($f['trip_to'] ?? null) ? $fmtDate($f['trip_to']) : '…'), $chipUrl(['trip_from', 'trip_to'])]);
    if (filled($f['due_from'] ?? null) || filled($f['due_to'] ?? null)) $chips->push(['Balance due: '.(filled($f['due_from'] ?? null) ? $fmtDate($f['due_from']) : '…').' – '.(filled($f['due_to'] ?? null) ? $fmtDate($f['due_to']) : '…'), $chipUrl(['due_from', 'due_to'])]);
    if ($f['overdue']) $chips->push(['Overdue only', $chipUrl(['overdue'])]);
  }

  // Muted summary next to the page title.
  $summary = match ($tab) {
    'ledger' => $totals['rows'].' '.str('entry')->plural($totals['rows']).' · '.$periodLabel,
    'outstanding' => $totals->bookings_count ? $totals->bookings_count.' '.str('booking')->plural($totals->bookings_count).' owe '.money($totals->outstanding) : 'Nothing owed',
    'pending' => $rows->count() ? $rows->count().' waiting for verification' : 'All caught up',
    default => $periodLabel,
  };
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex flex-wrap align-items-baseline gap-2"><h4 class="mb-0">Payments & balances</h4><span class="text-muted">{{ $summary }}</span></div>
                        <div class="pay-head-actions d-flex flex-wrap gap-2">
                          @if(in_array($tab, ['ledger', 'outstanding'], true))
                            <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="ri-download-2-line me-1"></i>Export CSV</a>
                          @elseif($tab !== 'pending' && $pendingCount)
                            <a class="btn btn-primary" href="{{ $tabUrl('pending') }}"><i class="ri-time-line me-1"></i>Review {{ $pendingCount }} pending</a>
                          @endif
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

<ul class="nav nav-tabs pay-tabs mb-3">
  @foreach(['overview' => 'Overview', 'ledger' => 'Ledger', 'outstanding' => 'Outstanding balances', 'pending' => 'Pending verification'] as $key => $label)
    <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif href="{{ $tabUrl($key, in_array($key, ['overview', 'ledger'], true) ? $range : []) }}">{{ $label }}@if($key === 'pending' && $pendingCount) <span class="badge bg-warning ms-1">{{ $pendingCount }}</span>@endif</a></li>
  @endforeach
</ul>

@if(in_array($tab, ['overview', 'ledger'], true))
{{-- Period first (it drives every figure). The ledger's own filters sit in the list card and belong to this form via form="pay-filters". --}}
<form id="pay-filters" class="pay-period" method="get" action="{{ route('payments.index') }}">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="pay-seg" role="group" aria-label="Quick periods">
    @foreach($presets as $label => [$a, $b])
      @php
        $active = $f['from'] === $a->toDateString() && $f['to'] === $b->toDateString();
      @endphp
      <a class="pay-seg-item {{ $active ? 'active' : '' }}" @if($active) aria-current="true" @endif href="{{ $tabUrl($tab, ['from' => $a->toDateString(), 'to' => $b->toDateString()] + request()->except(['from', 'to', 'page', 'tab'])) }}">{{ $label }}</a>
    @endforeach
    <span class="pay-seg-item pay-seg-static {{ $customPeriod ? 'active' : '' }}">Custom</span>
  </div>
  <div class="pay-range">
    <i class="ri-calendar-line" aria-hidden="true"></i>
    <input class="form-control" type="date" id="f-from" name="from" value="{{ $f['from'] }}" aria-label="From">
    <span class="text-muted">to</span>
    <input class="form-control" type="date" id="f-to" name="to" value="{{ $f['to'] }}" aria-label="To">
    <button class="btn btn-light">Apply</button>
  </div>
</form>
@endif

@if($tab === 'overview')
  {{-- Each figure opens the exact records behind it (§2). --}}
  <div class="card pay-stats">
    @foreach([
      ['Received', $period['received'], $period['received_count'].' '.str('payment')->plural($period['received_count']), '', $tabUrl('ledger', $range + ['kind' => 'payment', 'status' => 'confirmed'])],
      ['Refunded', $period['refunded'], $period['refunded_count'].' '.str('refund')->plural($period['refunded_count']), '', $tabUrl('ledger', $range + ['kind' => 'refund', 'status' => 'confirmed'])],
      ['Net collected', $period['net'], 'Received − refunded', '', $tabUrl('ledger', $range + ['status' => 'confirmed'])],
      ['Waiting for verification', $allPending->total, $allPending->n.' '.str('payment')->plural($allPending->n).' · all dates', $allPending->n ? 'is-warning' : '', $tabUrl('pending')],
    ] as [$label, $amount, $note, $tone, $url])
      <a href="{{ $url }}" class="pay-stat {{ $tone }}">
        <span class="pay-stat-label">{{ $label }}</span>
        <span class="pay-stat-value">{{ money($amount) }}</span>
        <span class="pay-stat-note">{{ $note }} <i class="ri-arrow-right-s-line" aria-hidden="true"></i></span>
      </a>
    @endforeach
  </div>
  <p class="pay-help">By the date money was received. Only confirmed payments and refunds count; pending, rejected and voided ones never do.</p>

  <div class="row g-3">
    <div class="col-lg-5"><div class="card pay-card h-100">
      <div class="pay-card-head"><h5 class="pay-card-title">Still owed today</h5><a href="{{ $tabUrl('outstanding') }}">View balances</a></div>
      <div class="pay-owed">
        <a class="pay-owed-row" href="{{ $tabUrl('outstanding') }}">
          <span><span class="d-block">Outstanding on active bookings</span><span class="pay-sub">{{ $owing->bookings_count }} {{ str('booking')->plural($owing->bookings_count) }}</span></span>
          <span class="pay-amount">{{ money($owing->outstanding) }}</span>
        </a>
        <a class="pay-owed-row" href="{{ $tabUrl('outstanding', ['overdue' => 1]) }}">
          <span><span class="d-block {{ $overdue->bookings_count ? 'text-danger' : '' }}">Of which overdue</span><span class="pay-sub">{{ $overdue->bookings_count }} {{ str('booking')->plural($overdue->bookings_count) }} past the balance due date</span></span>
          <span class="pay-amount {{ $overdue->bookings_count ? 'text-danger' : '' }}">{{ money($overdue->outstanding) }}</span>
        </a>
      </div>
    </div></div>
    <div class="col-lg-7"><div class="card pay-card tl-card h-100">
      <div class="pay-card-head"><h5 class="pay-card-title">By payment method</h5><span class="text-muted">{{ $periodLabel }}</span></div>
      <div class="table-responsive"><table class="table tl-table pay-table mb-0">
        <thead><tr><th>Method</th><th class="text-end">Received</th><th class="text-end">Refunded</th><th class="text-end">Net</th></tr></thead>
        <tbody>
        @forelse($methods as $m)
          <tr><td><a class="text-body fw-semibold" href="{{ $tabUrl('ledger', $range + ['method' => $m->method, 'status' => 'confirmed']) }}">{{ \App\Models\BookingPayment::METHODS[$m->method] ?? $m->method }}</a></td><td class="pay-amount fw-normal">{{ money($m->received) }}</td><td class="pay-amount fw-normal">{{ money($m->refunded) }}</td><td class="pay-amount">{{ money(bcsub((string) $m->received, (string) $m->refunded, 2)) }}</td></tr>
        @empty
          <tr><td colspan="4"><div class="pay-empty"><i class="ri-wallet-3-line"></i><strong>No confirmed payments in this period</strong><span>Pick another period above, or check the pending list.</span></div></td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div></div>
  </div>

@elseif($tab === 'ledger')
  <div class="card pay-stats">
    @foreach([['Received', $totals['received'], $totals['received_count'].' confirmed '.str('payment')->plural($totals['received_count']), ''], ['Refunded', $totals['refunded'], $totals['refunded_count'].' '.str('refund')->plural($totals['refunded_count']), ''], ['Net', $totals['net'], 'Received − refunded', ''], ['Pending (not counted)', $totals['pending'], $totals['pending_count'].' waiting', $totals['pending_count'] ? 'is-warning' : '']] as [$label, $amount, $note, $tone])
      <div class="pay-stat {{ $tone }}"><span class="pay-stat-label">{{ $label }}</span><span class="pay-stat-value">{{ money($amount) }}</span><span class="pay-stat-note">{{ $note }}</span></div>
    @endforeach
  </div>

  <div class="card tl-card pay-list"><div class="card-body p-0">
    <div class="tl-search-bar">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" form="pay-filters" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search receipt, reference, booking or name" aria-label="Search the ledger">@if(filled($f['q'] ?? null))<a href="{{ $chipUrl(['q']) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
      <button type="submit" form="pay-filters" class="visually-hidden">Search</button>
    </div>
    <div class="pay-filter-row">
      <select class="form-select" form="pay-filters" name="kind" aria-label="Type" onchange="this.form.submit()"><option value="">Payments & refunds</option><option value="payment" @selected(($f['kind'] ?? '') === 'payment')>Payments</option><option value="refund" @selected(($f['kind'] ?? '') === 'refund')>Refunds</option></select>
      <select class="form-select" form="pay-filters" name="status" aria-label="Status" onchange="this.form.submit()"><option value="">Any status</option>@foreach(\App\Models\BookingPayment::STATUSES as $value => [$label])<option value="{{ $value }}" @selected(($f['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
      <select class="form-select" form="pay-filters" name="method" aria-label="Method" onchange="this.form.submit()"><option value="">Any method</option>@foreach(\App\Models\BookingPayment::METHODS as $value => $label)<option value="{{ $value }}" @selected(($f['method'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
      <select class="form-select" form="pay-filters" name="tour" aria-label="Tour" onchange="this.form.submit()"><option value="">All tours</option>@foreach($tours as $t)<option value="{{ $t->id }}" @selected(($f['tour'] ?? null) == $t->id)>{{ $t->name }}</option>@endforeach</select>
      <select class="form-select" form="pay-filters" name="recorded_by" aria-label="Recorded by" onchange="this.form.submit()"><option value="">Recorded by anyone</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(($f['recorded_by'] ?? null) == $u->id)>{{ $u->name }}</option>@endforeach</select>
    </div>
    @if($chips->isNotEmpty())
      <div class="tl-chips tl-chips-bar">
        @foreach($chips as [$label, $removeUrl])<a href="{{ $removeUrl }}" class="tl-chip" title="Remove this filter">{{ $label }} <i class="ri-close-line"></i></a>@endforeach
        <a href="{{ $tabUrl('ledger', $range) }}" class="btn btn-link text-danger p-0 ms-1">Clear all filters</a>
      </div>
    @endif

    <div class="table-responsive"><table class="table table-hover align-middle tl-table pay-table">
      <thead><tr><th class="d-none d-sm-table-cell">Date</th><th>Booking · customer</th><th class="d-none d-md-table-cell">Method</th><th class="d-none d-sm-table-cell">Status</th><th class="text-end">Amount</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
      @forelse($rows as $p)
        @php
          $closed = in_array($p->status, ['voided', 'rejected'], true);
          $customer = trim($p->booking?->first_name.' '.$p->booking?->last_name);
          $hasReceipt = $p->status === 'confirmed' && $p->receipt_number;
        @endphp
        <tr class="{{ $closed ? 'pay-closed' : '' }}">
          <td class="text-nowrap d-none d-sm-table-cell">{{ $p->paid_on->format('M j, Y') }}<span class="pay-sub">{{ $p->receipt_number ?? 'No receipt' }}</span></td>
          <td><a href="{{ route('bookings.show', $p->booking_id) }}" class="fw-semibold text-body">{{ $customer ?: '—' }}</a><span class="pay-sub">{{ $p->booking?->reference }}@if($p->isRefund()) · <span class="text-danger">Refund</span>@endif<span class="d-sm-none"> · {{ $p->paid_on->format('M j') }}</span></span></td>
          <td class="d-none d-md-table-cell">{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</td>
          <td class="d-none d-sm-table-cell">{!! $statusBadge($p->status) !!}</td>
          <td class="pay-amount {{ $closed ? 'text-decoration-line-through' : ($p->isRefund() ? 'text-danger' : '') }}">{{ $p->isRefund() ? '−' : '' }}{{ money($p->amount, $p->currency) }}<span class="pay-sub d-sm-none">{!! $statusBadge($p->status) !!}</span></td>
          <td class="text-end text-nowrap">
            @if($hasReceipt)<a class="btn btn-sm btn-light d-none d-sm-inline-block" href="{{ route('receipts.payment', [$p->booking_id, $p]) }}" target="_blank" title="Open receipt {{ $p->receipt_number }}" aria-label="Open receipt {{ $p->receipt_number }}"><i class="ri-file-text-line"></i></a>@endif
            <button type="button" class="btn btn-sm btn-light" data-pay-details="#pd-{{ $p->id }}">Details</button>
            <template id="pd-{{ $p->id }}">
              <div class="modal-header"><h5 class="modal-title">{{ $p->isRefund() ? 'Refund' : 'Payment' }} · {{ money($p->amount, $p->currency) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
              <div class="modal-body">
                <div class="mb-3">{!! $statusBadge($p->status) !!}</div>
                <dl class="pay-dl">
                  <dt>Date received</dt><dd>{{ $p->paid_on->format('D, M j, Y') }}</dd>
                  <dt>Receipt</dt><dd>{{ $p->receipt_number ?? '—' }}</dd>
                  <dt>Method</dt><dd>{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</dd>
                  <dt>Reference</dt><dd>{{ $p->reference ?: '—' }}</dd>
                  <dt>Booking</dt><dd><a href="{{ route('bookings.show', $p->booking_id) }}">{{ $p->booking?->reference }}</a></dd>
                  <dt>Customer</dt><dd>{{ $customer ?: '—' }}@if($p->booking?->email)<span class="pay-sub">{{ $p->booking->email }}</span>@endif</dd>
                  <dt>Tour</dt><dd>{{ $p->booking?->tour?->name ?? '—' }}</dd>
                  <dt>Recorded by</dt><dd>{{ $p->recorder?->name ?? ($p->recorded_by ? '—' : 'Customer') }}</dd>
                  @if($p->note)<dt>Note</dt><dd>{{ $p->note }}</dd>@endif
                  @if($p->reason)<dt>Reason</dt><dd>{{ $p->reason }}</dd>@endif
                </dl>
              </div>
              <div class="modal-footer"><a class="btn btn-light" href="{{ route('bookings.show', $p->booking_id) }}">Open booking</a>@if($hasReceipt)<a class="btn btn-primary" href="{{ route('receipts.payment', [$p->booking_id, $p]) }}" target="_blank"><i class="ri-file-text-line me-1"></i>Receipt</a>@endif</div>
            </template>
          </td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="pay-empty"><i class="ri-search-line"></i><strong>{{ $chips->isNotEmpty() ? 'Nothing matches these filters' : 'No payments in this period' }}</strong><span>@if($chips->isNotEmpty())<a href="{{ $tabUrl('ledger', $range) }}">Clear all filters</a> or pick another period.@else Try another period above.@endif</span></div></td></tr>
      @endforelse
      </tbody>
      @if($totals['rows'])
      <tfoot><tr><td colspan="4" class="d-none d-md-table-cell text-end">{{ $totals['rows'] }} {{ str('entry')->plural($totals['rows']) }} · net confirmed</td><td colspan="3" class="d-none d-sm-table-cell d-md-none text-end">Net confirmed</td><td class="d-sm-none text-end">Net confirmed</td><td class="pay-amount">{{ money($totals['net']) }}</td><td></td></tr></tfoot>
      @endif
    </table></div>
    <div class="tl-pager">{{ $rows->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

@elseif($tab === 'outstanding')
  <div class="card pay-stats">
    @foreach([['Bookings owing', $totals->bookings_count, false, ''], ['Total due', $totals->due, true, ''], ['Paid', $totals->paid, true, ''], ['Outstanding', $totals->outstanding, true, (float) $totals->outstanding > 0 ? 'is-danger' : '']] as [$label, $value, $isMoney, $tone])
      <div class="pay-stat {{ $tone }}"><span class="pay-stat-label">{{ $label }}</span><span class="pay-stat-value">{{ $isMoney ? money($value) : $value }}</span></div>
    @endforeach
  </div>

  <div class="card tl-card pay-list"><div class="card-body p-0">
    <form id="owing-filters" method="get" action="{{ route('payments.index') }}">
      <input type="hidden" name="tab" value="outstanding">
      <div class="tl-search-bar">
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search booking, name or email" aria-label="Search balances">@if(filled($f['q'] ?? null))<a href="{{ $chipUrl(['q']) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <div class="pay-group-by"><label class="text-muted text-nowrap mb-0" for="o-group">Group by</label><select class="form-select" id="o-group" name="group" onchange="this.form.submit()"><option value="trip" @selected($f['group'] === 'trip')>Trip</option><option value="customer" @selected($f['group'] === 'customer')>Customer</option><option value="none" @selected($f['group'] === 'none')>No grouping</option></select></div>
      </div>
      <div class="pay-filter-row pay-filter-row-owing">
        <select class="form-select" id="o-tour" name="tour" aria-label="Tour"><option value="">All tours</option>@foreach($tours as $t)<option value="{{ $t->id }}" @selected(($f['tour'] ?? null) == $t->id)>{{ $t->name }}</option>@endforeach</select>
        <div class="pay-daterange"><span class="pay-daterange-label">Trip</span><input class="form-control" type="date" name="trip_from" value="{{ $f['trip_from'] ?? '' }}" aria-label="Trip from"><span class="text-muted">–</span><input class="form-control" type="date" name="trip_to" value="{{ $f['trip_to'] ?? '' }}" aria-label="Trip to"></div>
        <div class="pay-daterange"><span class="pay-daterange-label">Due</span><input class="form-control" type="date" name="due_from" value="{{ $f['due_from'] ?? '' }}" aria-label="Balance due from"><span class="text-muted">–</span><input class="form-control" type="date" name="due_to" value="{{ $f['due_to'] ?? '' }}" aria-label="Balance due to"></div>
        <div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="o-overdue" name="overdue" value="1" @checked($f['overdue'])><label class="form-check-label" for="o-overdue">Overdue only</label></div>
        <button class="btn btn-light ms-auto"><i class="ri-filter-3-line me-1"></i>Apply</button>
      </div>
    </form>
    @if($chips->isNotEmpty())
      <div class="tl-chips tl-chips-bar">
        @foreach($chips as [$label, $removeUrl])<a href="{{ $removeUrl }}" class="tl-chip" title="Remove this filter">{{ $label }} <i class="ri-close-line"></i></a>@endforeach
        <a href="{{ $tabUrl('outstanding', ['group' => $f['group']]) }}" class="btn btn-link text-danger p-0 ms-1">Clear all filters</a>
      </div>
    @endif

    @php
      $byGroup = $bookings->groupBy($groupKey);
    @endphp
    <div class="table-responsive"><table class="table align-middle tl-table pay-table">
      <thead><tr><th>Booking · customer</th><th class="d-none d-sm-table-cell">Trip</th><th class="d-none d-sm-table-cell">Balance due</th><th class="text-end d-none d-lg-table-cell">Total due</th><th class="text-end d-none d-lg-table-cell">Paid</th><th class="text-end">Outstanding</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
      @forelse($f['group'] === 'none' ? collect([null => $bookings]) : $groups->mapWithKeys(fn ($g) => [$g->group_id => $byGroup[$g->group_id] ?? collect()]) as $groupId => $list)
        <tbody>
        @if($f['group'] !== 'none')
          @php
            $g = $groups->firstWhere('group_id', $groupId);
            $first = $list->first();
          @endphp
          <tr class="pay-group"><td colspan="3">
            @if($f['group'] === 'trip')<i class="ri-bus-line me-1" aria-hidden="true"></i>{{ $first?->tour?->name }} · {{ $first?->departure?->departs_at?->format('D, M j, Y') }}
            @else<i class="ri-user-line me-1" aria-hidden="true"></i>{{ $first?->customer?->full_name ?? trim($first?->first_name.' '.$first?->last_name) }}@if($first?->customer_id) <a class="fw-normal ms-1" href="{{ route('customers.show', $first->customer_id) }}">Profile</a>@endif @endif
            <span class="text-muted fw-normal ms-1">· {{ $g->bookings_count }} {{ str('booking')->plural($g->bookings_count) }}</span></td>
            <td class="pay-amount d-none d-lg-table-cell">{{ money($g->due) }}</td><td class="pay-amount d-none d-lg-table-cell">{{ money($g->paid) }}</td><td class="pay-amount text-danger d-none d-sm-table-cell">{{ money($g->outstanding) }}</td><td class="d-none d-sm-table-cell"></td></tr>
        @endif
        @foreach($list as $b)
          @php
            $isOverdue = $b->balance_due_date && $b->balance_due_date->toDateString() < $f['today'];
            $bName = trim($b->first_name.' '.$b->last_name);
            $departs = \Illuminate\Support\Carbon::parse($b->departs_at)->timezone($tz);
            $payStatus = \App\Models\Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status;
          @endphp
          <tr>
            <td><a href="{{ route('bookings.show', $b) }}" class="fw-semibold text-body">{{ $bName ?: '—' }}</a><span class="pay-sub">{{ $b->reference }} · {{ $payStatus }}</span>@if($b->balance_due_date)<span class="pay-sub d-sm-none {{ $isOverdue ? 'text-danger' : '' }}">Due {{ $b->balance_due_date->format('M j') }}{{ $isOverdue ? ' · overdue' : '' }}</span>@endif</td>
            <td class="text-nowrap d-none d-sm-table-cell">{{ $departs->format('M j, Y') }}<span class="pay-sub pay-trunc">{{ $b->tour?->name }}</span></td>
            <td class="text-nowrap d-none d-sm-table-cell">@if($isOverdue)<span class="text-danger fw-semibold">{{ $b->balance_due_date->format('M j, Y') }}</span><span class="pay-sub text-danger">Overdue</span>@else{{ $b->balance_due_date?->format('M j, Y') ?? '—' }}@endif</td>
            <td class="pay-amount fw-normal d-none d-lg-table-cell">{{ money(bcsub((string) $b->total_amount, (string) $b->discount_amount, 2), $b->currency) }}</td>
            <td class="pay-amount fw-normal d-none d-lg-table-cell">{{ money($b->amount_paid, $b->currency) }}</td>
            <td class="pay-amount text-danger">{{ money($b->outstanding_amount, $b->currency) }}</td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-light" data-pay-details="#bd-{{ $b->id }}">Details</button>
              <template id="bd-{{ $b->id }}">
                <div class="modal-header"><h5 class="modal-title">{{ $b->reference }} · {{ $bName }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                  <div class="pay-modal-sums">
                    <div><span>Total due</span><strong>{{ money(bcsub((string) $b->total_amount, (string) $b->discount_amount, 2), $b->currency) }}</strong></div>
                    <div><span>Paid</span><strong>{{ money($b->amount_paid, $b->currency) }}</strong></div>
                    <div><span>Outstanding</span><strong class="text-danger">{{ money($b->outstanding_amount, $b->currency) }}</strong></div>
                  </div>
                  <dl class="pay-dl">
                    <dt>Payment status</dt><dd><span class="badge bg-light">{{ $payStatus }}</span></dd>
                    <dt>Balance due</dt><dd class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">{{ $b->balance_due_date?->format('D, M j, Y') ?? '—' }}{{ $isOverdue ? ' · overdue' : '' }}</dd>
                    <dt>Tour</dt><dd>{{ $b->tour?->name ?? '—' }}</dd>
                    <dt>Departs</dt><dd>{{ $departs->format('D, M j, Y') }}</dd>
                    <dt>Phone</dt><dd>@if($b->phone)<a href="tel:{{ $b->phone }}">{{ $b->phone }}</a>@else — @endif</dd>
                    <dt>Email</dt><dd>@if($b->email)<a href="mailto:{{ $b->email }}">{{ $b->email }}</a>@else — @endif</dd>
                  </dl>
                </div>
                <div class="modal-footer">@if($b->customer_id)<a class="btn btn-light" href="{{ route('customers.show', $b->customer_id) }}">Customer profile</a>@endif<a class="btn btn-primary" href="{{ route('bookings.show', $b) }}">Open booking</a></div>
              </template>
            </td>
          </tr>
        @endforeach
        </tbody>
      @empty
        <tbody><tr><td colspan="7"><div class="pay-empty"><i class="ri-check-double-line"></i><strong>Nothing is owed{{ $chips->isNotEmpty() ? ' for these filters' : '' }}</strong><span>@if($chips->isNotEmpty())<a href="{{ $tabUrl('outstanding') }}">Clear all filters</a>@else Every active booking is paid in full.@endif</span></div></td></tr></tbody>
      @endforelse
      @if($totals->bookings_count)
      <tfoot><tr><td colspan="3" class="text-end d-none d-sm-table-cell">Total · {{ $totals->bookings_count }} {{ str('booking')->plural($totals->bookings_count) }}</td><td class="text-end d-sm-none">Total</td><td class="pay-amount d-none d-lg-table-cell">{{ money($totals->due) }}</td><td class="pay-amount d-none d-lg-table-cell">{{ money($totals->paid) }}</td><td class="pay-amount text-danger">{{ money($totals->outstanding) }}</td><td></td></tr></tfoot>
      @endif
    </table></div>
  </div></div>

@else
  <div class="card tl-card pay-list"><div class="card-body p-0">
    <div class="pay-card-head pay-card-head-lg">
      <div><h5 class="pay-card-title">Waiting for verification</h5><p class="text-muted mb-0">Payments a customer says they made (for example the deposit screenshot sent with a booking) or that staff recorded without checking. They don't reduce any balance until confirmed.</p></div>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle tl-table pay-table">
      <thead><tr><th>Date</th><th>Booking · customer</th><th class="d-none d-md-table-cell">Method</th><th class="text-end">Amount</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
      @forelse($rows as $p)
        @php
          $customer = trim($p->booking?->first_name.' '.$p->booking?->last_name);
        @endphp
        <tr>
          <td class="text-nowrap">{{ $p->paid_on->format('M j, Y') }}<span class="pay-sub">{{ $p->paid_on->isToday() ? 'Today' : $p->paid_on->diffForHumans() }}</span></td>
          <td><a href="{{ route('bookings.show', $p->booking_id) }}" class="fw-semibold text-body">{{ $customer ?: '—' }}</a><span class="pay-sub">{{ $p->booking?->reference }}</span></td>
          <td class="d-none d-md-table-cell">{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</td>
          <td class="pay-amount">{{ money($p->amount, $p->currency) }}</td>
          <td class="text-end text-nowrap">
            @can('confirm-payments')
              <form class="d-inline" method="post" action="{{ route('booking-payments.confirm', [$p->booking_id, $p]) }}" onsubmit="return confirm('Confirm {{ money($p->amount, $p->currency) }} arrived for {{ $p->booking?->reference }}?')">@csrf<button class="btn btn-sm btn-primary"><i class="ri-check-line"></i> Confirm</button></form>
              <button type="button" class="btn btn-sm btn-light" data-reject="{{ route('booking-payments.reject', [$p->booking_id, $p]) }}" data-what="{{ money($p->amount, $p->currency) }} · {{ $p->booking?->reference }} · {{ $customer }}" title="Funds did not arrive">Reject</button>
            @endcan
            <button type="button" class="btn btn-sm btn-light" data-pay-details="#pp-{{ $p->id }}">Details</button>
            <template id="pp-{{ $p->id }}">
              <div class="modal-header"><h5 class="modal-title">Pending · {{ money($p->amount, $p->currency) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
              <div class="modal-body">
                <dl class="pay-dl">
                  <dt>Date</dt><dd>{{ $p->paid_on->format('D, M j, Y') }} <span class="text-muted">({{ $p->paid_on->isToday() ? 'today' : $p->paid_on->diffForHumans() }})</span></dd>
                  <dt>Method</dt><dd>{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</dd>
                  <dt>Reference</dt><dd>{{ $p->reference ?: '—' }}</dd>
                  <dt>Booking</dt><dd><a href="{{ route('bookings.show', $p->booking_id) }}">{{ $p->booking?->reference }}</a></dd>
                  <dt>Customer</dt><dd>{{ $customer ?: '—' }}@if($p->booking?->email)<span class="pay-sub">{{ $p->booking->email }}</span>@endif</dd>
                  <dt>Trip</dt><dd>{{ $p->booking?->tour?->name ?? '—' }}<span class="pay-sub">{{ $p->booking?->departure?->departs_at?->format('D, M j, Y') }}</span></dd>
                  @if($p->note)<dt>Note</dt><dd>{{ $p->note }}</dd>@endif
                </dl>
              </div>
              <div class="modal-footer">@if($p->proof_path)<a class="btn btn-light" target="_blank" href="{{ route('booking-payments.proof', [$p->booking_id, $p]) }}"><i class="ri-attachment-2 me-1"></i>View proof</a>@endif<a class="btn btn-light" href="{{ route('bookings.show', $p->booking_id) }}">Open booking</a></div>
            </template>
            @if($p->proof_path)<a class="btn btn-sm btn-light" target="_blank" href="{{ route('booking-payments.proof', [$p->booking_id, $p]) }}" title="View proof" aria-label="View proof"><i class="ri-attachment-2"></i></a>@endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="pay-empty"><i class="ri-check-double-line"></i><strong>Nothing waiting for verification</strong><span>New customer deposits and unchecked payments will appear here.</span></div></td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div></div>

  @can('confirm-payments')
  {{-- Reject right here: a reason is required, and the list stays open afterwards. --}}
  <div class="modal fade" id="rejectPayment" tabindex="-1" aria-labelledby="rejectPaymentTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="">@csrf
    <div class="modal-header"><h5 class="modal-title" id="rejectPaymentTitle">Reject this payment?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <p class="fw-semibold mb-3" data-what></p>
      <label class="form-label" for="reject-reason">Reason</label>
      <div class="d-flex flex-wrap gap-1 mb-2">@foreach(['Not received in the bank account', 'Wrong amount', 'Screenshot unreadable', 'Duplicate of another payment'] as $reason)<button type="button" class="btn btn-sm btn-light" data-reason="{{ $reason }}">{{ $reason }}</button>@endforeach</div>
      <input class="form-control" id="reject-reason" name="reason" maxlength="255" required placeholder="Pick one above or type your own">
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

{{-- One shared details modal: each row's "Details" button copies its <template> in. --}}
<div class="modal fade" id="payDetails" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"></div></div></div>
<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-pay-details]');
  if (!btn) return;
  const tpl = document.querySelector(btn.dataset.payDetails);
  const el = document.getElementById('payDetails');
  if (!tpl || !el || !window.bootstrap) return;
  el.querySelector('.modal-content').innerHTML = tpl.innerHTML;
  bootstrap.Modal.getOrCreateInstance(el).show();
});
</script>
</div></div></div>@include('admin.includes.footer')
