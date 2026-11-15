{{-- Money summary (§7): every figure is computed from the payment ledger by BookingMoney; nothing here is typed. --}}
@php
  $m = $moneySummary;
  $cur = $booking->currency ?: 'CAD';
  $fmt = fn ($v) => money($v, $cur);
  $statusColor = ['unpaid' => 'danger', 'proof_submitted' => 'warning', 'partially_paid' => 'info', 'deposit_paid' => 'primary', 'paid' => 'success', 'complimentary' => 'success', 'refunded' => 'secondary'][$booking->payment_status] ?? 'light';
@endphp
<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Payment</h5>
    <span class="badge bg-{{ $statusColor }}">{{ \App\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] ?? $booking->payment_status }}</span>
  </div>
  <table class="table table-sm mb-2"><tbody>
    <tr><td class="text-muted">Price <small>{{ $booking->tax_included ? '(taxes incl.)' : '(taxes extra)' }}</small></td><td class="text-end">{{ $fmt($m['price']) }}</td></tr>
    @if(\App\Support\Money::cmp($m['discounts'], 0) > 0)<tr><td class="text-muted">Discounts & complimentary</td><td class="text-end text-success">− {{ $fmt($m['discounts']) }}</td></tr>@endif
    <tr class="fw-semibold"><td>Total due</td><td class="text-end">{{ $fmt($m['due']) }}</td></tr>
    <tr><td class="text-muted">Paid (confirmed)</td><td class="text-end">{{ $fmt($m['paid']) }}</td></tr>
    @if(\App\Support\Money::cmp($m['refunds'], 0) > 0)<tr><td class="text-muted">Refunded</td><td class="text-end">− {{ $fmt($m['refunds']) }}</td></tr><tr><td class="text-muted">Net collected</td><td class="text-end">{{ $fmt($m['net']) }}</td></tr>@endif
    <tr class="fw-bold {{ \App\Support\Money::cmp($m['outstanding'], 0) > 0 ? 'text-danger' : 'text-success' }}" style="border-top:2px solid #dee2e6"><td>Outstanding</td><td class="text-end">{{ $fmt($m['outstanding']) }}</td></tr>
    @if(\App\Support\Money::cmp($m['credit'], 0) > 0)<tr class="text-primary"><td>Credit {{ $booking->status === 'cancelled' ? '(refund due)' : '(overpaid)' }}</td><td class="text-end">{{ $fmt($m['credit']) }}</td></tr>@endif
  </tbody></table>

  @if(\App\Support\Money::cmp($m['pending'], 0) > 0)
    <div class="alert alert-warning py-2 small mb-2"><i class="ri-time-line"></i> <b>{{ $fmt($m['pending']) }}</b> waiting for verification — not counted as paid until confirmed below.</div>
  @endif
  @if($booking->status !== 'cancelled' && \App\Support\Money::cmp($m['due'], 0) > 0)
    <div class="small mb-1 d-flex justify-content-between"><span class="text-muted">Deposit {{ $fmt($m['deposit']) }}</span>
      @if(\App\Support\Money::cmp($m['deposit_outstanding'], 0) > 0)<span class="text-danger">{{ $fmt($m['deposit_outstanding']) }} still to pay</span>@else<span class="text-success"><i class="ri-check-line"></i> paid</span>@endif</div>
    @php($pct = \App\Support\Money::cmp($m['due'], 0) > 0 ? min(100, max(0, (float) bcdiv(bcmul(\App\Support\Money::max0($m['net']), '100', 2), $m['due'], 2))) : 0)
    <div class="progress mb-2" style="height:6px" title="{{ $pct }}% of the total paid"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>
    @if($m['due_date'])<div class="small {{ $m['overdue'] ? 'text-danger fw-semibold' : 'text-muted' }}"><i class="ri-calendar-event-line"></i> Balance due {{ $m['due_date']->format('D, M j, Y') }}{{ $m['overdue'] ? ' — overdue' : '' }}</div>@endif
    @if($booking->payment_terms_note)<div class="small text-muted"><i class="ri-information-line"></i> {{ $booking->payment_terms_note }}</div>@endif
  @endif

  @if($booking->tour_departure_id)
  <div class="d-grid gap-2 mt-3">
    @can('record-payments')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPayment"><i class="ri-add-circle-line"></i> Record payment</button>@endcan
    <div class="d-flex gap-2">
      @can('apply-discounts')<button class="btn btn-sm btn-outline-secondary flex-grow-1" data-bs-toggle="modal" data-bs-target="#addAdjustment"><i class="ri-price-tag-3-line"></i> Discount / comp</button>@endcan
      @can('edit-bookings')<button class="btn btn-sm btn-outline-secondary flex-grow-1" data-bs-toggle="modal" data-bs-target="#paymentTerms"><i class="ri-calendar-todo-line"></i> Terms</button>@endcan
    </div>
  </div>
  @endif
</div></div>
