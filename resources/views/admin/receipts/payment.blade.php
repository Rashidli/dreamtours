@extends('admin.receipts._layout', [
  'title' => ($payment->isRefund() ? 'Refund ' : 'Receipt ').$payment->receipt_number,
  'docHeading' => ($payment->isRefund() ? 'REFUND RECEIPT' : 'PAYMENT RECEIPT').'<br><b>'.e($payment->receipt_number).'</b><br>'.e($payment->paid_on->format('M j, Y')),
])

@section('content')
  @php($cur = $booking->currency ?: 'CAD')
  @php($fmt = fn ($v) => money($v, $cur))
  <h2>{{ $payment->isRefund() ? 'Refund issued' : 'Payment received' }}</h2>
  <table class="lines">
    <thead><tr><th>Description</th><th>Method</th><th>Reference</th><th class="num">Amount</th></tr></thead>
    <tbody><tr>
      <td>{{ $payment->isRefund() ? 'Refund'.($payment->refundOf ? ' of '.$payment->refundOf->receipt_number : '').($payment->reason ? ' — '.$payment->reason : '') : 'Payment for booking '.$booking->reference }}</td>
      <td>{{ \App\Models\BookingPayment::METHODS[$payment->method] ?? $payment->method }}</td>
      <td>{{ $payment->reference ?: '—' }}</td>
      <td class="num"><b>{{ $payment->isRefund() ? '−' : '' }}{{ $fmt($payment->amount) }}</b></td>
    </tr></tbody>
  </table>
  <p style="margin-top:14px"><span class="stamp {{ $payment->isRefund() ? 'refund' : '' }}">{{ $payment->isRefund() ? 'REFUNDED' : 'PAID' }} {{ $fmt($payment->amount) }}</span></p>

  <h2>Booking balance as of today</h2>
  @include('admin.receipts._totals')
@endsection
