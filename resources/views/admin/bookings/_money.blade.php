{{-- Money summary (§7): every figure is computed from the payment ledger by BookingMoney; nothing here is typed. --}}
@php
  $m = $moneySummary;
  $cur = $booking->currency ?: 'CAD';
  $fmt = fn ($v) => money($v, $cur);
  $statusColor = ['unpaid' => 'danger', 'proof_submitted' => 'warning', 'partially_paid' => 'info', 'deposit_paid' => 'info', 'paid' => 'success', 'complimentary' => 'success', 'refunded' => 'secondary'][$booking->payment_status] ?? 'secondary';
@endphp
<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="card-title mb-0">Payment</h5>
    <span class="badge bg-{{ $statusColor }}">{{ \App\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] ?? $booking->payment_status }}</span>
  </div>
  <div class="bk-sum">
    <div class="bk-sum-row"><span>Price <span class="text-muted">{{ $booking->tax_included ? '(taxes incl.)' : '(taxes extra)' }}</span></span><span>{{ $fmt($m['price']) }}</span></div>
    @if(\App\Support\Money::cmp($m['discounts'], 0) > 0)<div class="bk-sum-row"><span>Discounts & complimentary</span><span class="text-success">− {{ $fmt($m['discounts']) }}</span></div>@endif
    <div class="bk-sum-row bk-sum-total"><span>Total due</span><span>{{ $fmt($m['due']) }}</span></div>
    <div class="bk-sum-row"><span>Paid (confirmed)</span><span>{{ $fmt($m['paid']) }}</span></div>
    @if(\App\Support\Money::cmp($m['refunds'], 0) > 0)<div class="bk-sum-row"><span>Refunded</span><span>− {{ $fmt($m['refunds']) }}</span></div><div class="bk-sum-row"><span>Net collected</span><span>{{ $fmt($m['net']) }}</span></div>@endif
    <div class="bk-sum-row bk-sum-out {{ \App\Support\Money::cmp($m['outstanding'], 0) > 0 ? 'text-danger' : 'text-success' }}"><span>Outstanding</span><span>{{ $fmt($m['outstanding']) }}</span></div>
    @if(\App\Support\Money::cmp($m['credit'], 0) > 0)<div class="bk-sum-row fw-semibold" style="color:#005bd3"><span>Credit {{ $booking->status === 'cancelled' ? '(refund due)' : '(overpaid)' }}</span><span>{{ $fmt($m['credit']) }}</span></div>@endif
  </div>

  @if(\App\Support\Money::cmp($m['pending'], 0) > 0)
    <div class="alert alert-warning py-2 mt-3 mb-0"><i class="ri-time-line"></i> <b>{{ $fmt($m['pending']) }}</b> waiting for verification — not counted as paid until confirmed in the ledger.</div>
  @endif
  @if($booking->status !== 'cancelled' && \App\Support\Money::cmp($m['due'], 0) > 0)
    <div class="mt-3 mb-1 d-flex justify-content-between gap-2"><span class="text-muted">Deposit {{ $fmt($m['deposit']) }}</span>
      @if(\App\Support\Money::cmp($m['deposit_outstanding'], 0) > 0)<span class="text-danger">{{ $fmt($m['deposit_outstanding']) }} still to pay</span>@else<span class="text-success"><i class="ri-check-line"></i> paid</span>@endif</div>
    @php($pct = \App\Support\Money::cmp($m['due'], 0) > 0 ? min(100, max(0, (float) bcdiv(bcmul(\App\Support\Money::max0($m['net']), '100', 2), $m['due'], 2))) : 0)
    <div class="progress bk-progress mb-2" title="{{ $pct }}% of the total paid"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>
    @if($m['due_date'])<div class="{{ $m['overdue'] ? 'text-danger fw-semibold' : 'text-muted' }}"><i class="ri-calendar-event-line"></i> Balance due {{ $m['due_date']->format('D, M j, Y') }}{{ $m['overdue'] ? ' — overdue' : '' }}</div>@endif
    @if($booking->payment_terms_note)<div class="text-muted"><i class="ri-information-line"></i> {{ $booking->payment_terms_note }}</div>@endif
  @endif

  @if($booking->tour_departure_id)
  <div class="d-grid gap-2 mt-3">
    @can('record-payments')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPayment"><i class="ri-add-circle-line"></i> Record payment</button>@endcan
    <div class="d-flex gap-2">
      @can('apply-discounts')<button class="btn btn-light flex-grow-1" data-bs-toggle="modal" data-bs-target="#addAdjustment"><i class="ri-price-tag-3-line"></i> Discount / comp</button>@endcan
      @can('edit-bookings')<button class="btn btn-light flex-grow-1" data-bs-toggle="modal" data-bs-target="#paymentTerms"><i class="ri-calendar-todo-line"></i> Terms</button>@endcan
    </div>
  </div>
  @endif
</div></div>
