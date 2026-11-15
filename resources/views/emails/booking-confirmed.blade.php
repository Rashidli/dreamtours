@extends('emails.layout')

@section('tone', 'confirmed')
@section('status', 'Booking confirmed')
@section('title', "You're going to {$booking->tour?->name}!")
@section('preheader', 'Your booking '.$booking->reference.' is confirmed'.($booking->departure ? ' for '.$booking->departure->departs_at->format('D, M j') : '').'.')
@section('intro')
  Hi {{ $booking->first_name }}, great news — your booking is <strong>confirmed</strong>. Here is everything you need for your trip.
@endsection

@section('body')
  @include('emails.partials.booking-summary')

  @php($due = (float) $booking->outstanding_amount) {{-- from the payment ledger --}}
  @php($currency = $booking->currency ?: ($booking->departure?->currency ?: 'CAD'))
  @if($due > 0)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;background:#fff4e0;border-radius:12px;">
      <tr><td style="padding:16px 18px;font-size:14px;line-height:22px;color:#5c3b00;">
        <strong style="color:#3d2700;">Balance remaining: {{ money($due, $currency) }}</strong><br>
        {!! setting('payment_instructions') ? nl2br(e(setting('payment_instructions'))).'<br>' : '' !!}Please include your booking reference <strong>{{ $booking->reference }}</strong>.
      </td></tr>
    </table>
  @endif

  <p style="margin:22px 0 0;"><strong>Before you go</strong></p>
  <ul style="margin:6px 0 0;padding-left:20px;color:#4b5263;">
    <li>Your seat numbers arrive by email 1–2 days before departure.</li>
    <li>Please be at your pick-up point 15 minutes early with a photo ID.</li>
  </ul>
  @include('emails.partials.button', ['url' => rtrim(config('app.url'), '/').'/tours/'.$booking->tour?->slug, 'label' => 'View trip details'])
@endsection
