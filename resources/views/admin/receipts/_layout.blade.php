{{-- Printable / PDF document layout. Plain tables and simple CSS: dompdf renders the same file. --}}
@php
  $cur = $booking->currency ?: 'CAD';
  $fmt = fn ($v) => money($v, $cur);
  $site = setting('site_name', 'DreamTour');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
  @page { margin: 36px 40px; }
  body { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; color: #1f2430; font-size: 12px; margin: 0; background: #fff; }
  .page { max-width: 760px; margin: 0 auto; padding: {{ $forPdf ? '0' : '32px 24px' }}; }
  .brand { background: #141824; color: #fff; padding: 16px 20px; border-bottom: 4px solid #f80a6a; }
  .brand .name { font-size: 20px; font-weight: bold; letter-spacing: 2px; }
  .brand .name span { color: #f80a6a; }
  .brand .doc { float: right; text-align: right; font-size: 11px; color: #c9ccd4; }
  .brand .doc b { color: #fff; font-size: 14px; }
  h2 { font-size: 15px; margin: 22px 0 8px; color: #141824; }
  table { width: 100%; border-collapse: collapse; }
  .grid td { padding: 3px 0; vertical-align: top; }
  .grid td.label { color: #6b7280; width: 34%; }
  .lines th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; border-bottom: 1px solid #d9dce2; padding: 6px 4px; }
  .lines td { border-bottom: 1px solid #eef0f3; padding: 6px 4px; }
  .num { text-align: right; white-space: nowrap; }
  .muted { color: #9aa0ad; }
  .struck { text-decoration: line-through; color: #9aa0ad; }
  .totals { width: 52%; margin-left: 48%; margin-top: 10px; }
  .totals td { padding: 4px; }
  .totals .strong td { font-weight: bold; border-top: 2px solid #1f2430; font-size: 13px; }
  .stamp { display: inline-block; border: 2px solid #17663a; color: #17663a; padding: 4px 10px; font-weight: bold; font-size: 13px; letter-spacing: 1px; }
  .stamp.refund { border-color: #a11a1a; color: #a11a1a; }
  .footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #eef0f3; font-size: 10px; color: #6b7280; }
  .toolbar { text-align: right; margin-bottom: 12px; }
  .toolbar a, .toolbar button { font: inherit; font-size: 13px; padding: 7px 14px; border-radius: 6px; border: 1px solid #0f9cf3; background: #0f9cf3; color: #fff; text-decoration: none; cursor: pointer; margin-left: 6px; }
  .toolbar a.light { background: #fff; color: #0f9cf3; }
  @media print { .toolbar { display: none; } .page { padding: 0; } }
</style>
</head>
<body>
<div class="page">
  @unless($forPdf)
    <div class="toolbar"><a class="light" href="{{ route('bookings.show', $booking) }}">← Back to booking</a><button onclick="window.print()">Print</button><a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}">Download PDF</a></div>
  @endunless
  <div class="brand"><div class="doc">{!! $docHeading !!}</div><div class="name">DREAM<span>TOUR</span></div></div>

  <table class="grid" style="margin-top:18px"><tr>
    <td style="width:50%;padding-right:16px">
      <div class="muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px">Customer</div>
      <b>{{ trim($booking->first_name.' '.$booking->last_name) }}</b><br>{{ $booking->email }}<br>{{ $booking->phone }}
    </td>
    <td style="width:50%">
      <div class="muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px">Trip</div>
      <b>{{ $booking->tour?->name }}</b><br>
      @if($booking->departure){{ \App\Support\DepartureCalendar::range($booking->departure) }} · {{ $booking->departure->departs_at->format('g:i A') }}<br>@endif
      Booking {{ $booking->reference }} · {{ $booking->participant_count }} {{ str('guest')->plural($booking->participant_count) }}
    </td>
  </tr></table>

  @yield('content')

  <div class="footer">
    {{ $site }} · {{ setting('phone') }} · {{ setting('email') }} · {{ parse_url(config('app.url'), PHP_URL_HOST) }}<br>
    Issued {{ now(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }}. Amounts in {{ $cur }}{{ $booking->tax_included ? ', taxes included' : '' }}.
  </div>
</div>
</body>
</html>
