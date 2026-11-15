{{-- Totals block shared by receipts and statements; figures come from BookingMoney::summary(). --}}
@php($cur = $booking->currency ?: 'CAD')
@php($fmt = fn ($v) => money($v, $cur))
<table class="totals">
  <tr><td>Price</td><td class="num">{{ $fmt($summary['price']) }}</td></tr>
  @if(\App\Support\Money::cmp($summary['discounts'], 0) > 0)<tr><td>Discounts & complimentary</td><td class="num">− {{ $fmt($summary['discounts']) }}</td></tr>@endif
  <tr><td><b>Total due</b></td><td class="num"><b>{{ $fmt($summary['due']) }}</b></td></tr>
  <tr><td>Paid</td><td class="num">{{ $fmt($summary['paid']) }}</td></tr>
  @if(\App\Support\Money::cmp($summary['refunds'], 0) > 0)<tr><td>Refunded</td><td class="num">− {{ $fmt($summary['refunds']) }}</td></tr>@endif
  <tr class="strong"><td>{{ \App\Support\Money::cmp($summary['credit'], 0) > 0 ? 'Credit' : 'Balance outstanding' }}</td><td class="num">{{ $fmt(\App\Support\Money::cmp($summary['credit'], 0) > 0 ? $summary['credit'] : $summary['outstanding']) }}</td></tr>
  @if(\App\Support\Money::cmp($summary['outstanding'], 0) > 0 && $summary['due_date'])<tr><td colspan="2" class="muted" style="text-align:right">Balance due by {{ $summary['due_date']->format('M j, Y') }}</td></tr>@endif
</table>
