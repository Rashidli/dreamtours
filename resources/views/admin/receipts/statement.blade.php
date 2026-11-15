@extends('admin.receipts._layout', [
  'title' => 'Statement '.$booking->reference,
  'docHeading' => 'BOOKING STATEMENT<br><b>'.e($booking->reference).'</b><br>'.e(\App\Models\Booking::STATUSES[$booking->status] ?? $booking->status),
])

@section('content')
  @php($cur = $booking->currency ?: 'CAD')
  @php($fmt = fn ($v) => money($v, $cur))

  @if($booking->passengers->isNotEmpty())
  <h2>Passengers</h2>
  <table class="lines">
    <thead><tr><th>Name</th><th>Fare</th><th class="num">Price</th></tr></thead>
    <tbody>
    @foreach($booking->passengers as $p)
      <tr><td>{{ $p->first_name }} {{ $p->last_name }}</td><td>{{ $booking->tour && in_array($p->category, ['adult', 'child', 'infant'], true) ? $booking->tour->priceLabel($p->category) : ucfirst((string) $p->category) }}</td><td class="num">{{ $p->price !== null ? $fmt($p->price) : '—' }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @endif

  @php($adjustments = $booking->adjustments->where('status', 'active'))
  @if($adjustments->isNotEmpty())
  <h2>Discounts & complimentary seats</h2>
  <table class="lines">
    <tbody>
    @foreach($adjustments as $a)
      <tr><td>{{ \App\Models\BookingAdjustment::KINDS[$a->kind] }}@if($a->passenger) — {{ $a->passenger->first_name }} {{ $a->passenger->last_name }}@endif · {{ $a->reason }}</td><td class="num">− {{ $fmt($a->amount) }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @endif

  <h2>Payments & refunds</h2>
  <table class="lines">
    <thead><tr><th>Date</th><th>Receipt</th><th>Type</th><th>Method</th><th class="num">Amount</th></tr></thead>
    <tbody>
    @php($shown = $booking->payments->whereIn('status', ['confirmed', 'pending']))
    @forelse($shown as $p)
      <tr>
        <td>{{ $p->paid_on->format('M j, Y') }}</td>
        <td>{{ $p->receipt_number ?? '—' }}</td>
        <td>{{ $p->isRefund() ? 'Refund' : 'Payment' }}{{ $p->status === 'pending' ? ' (awaiting verification — not counted)' : '' }}</td>
        <td>{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</td>
        <td class="num {{ $p->status === 'pending' ? 'muted' : '' }}">{{ $p->isRefund() ? '−' : '' }}{{ $fmt($p->amount) }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">No payments yet.</td></tr>
    @endforelse
    </tbody>
  </table>

  @include('admin.receipts._totals')

  @if(setting('payment_instructions') && \App\Support\Money::cmp($summary['outstanding'], 0) > 0)
    <h2>How to pay</h2>
    <p>{!! nl2br(e(setting('payment_instructions'))) !!}<br>Please include booking reference <b>{{ $booking->reference }}</b>.</p>
  @endif
@endsection
