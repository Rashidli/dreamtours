@extends('emails.layout')
@section('body')
  @php($departure = $booking->departure)
  @if($forAdmin)
    <p style="margin-top:0">A new booking was submitted on the website.</p>
  @else
    <p style="margin-top:0">Hi {{ $booking->first_name }},</p>
    <p>Your request has been received. A DreamTour representative will contact you to confirm your booking after checking your payment.</p>
  @endif
  <table cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px;margin:16px 0">
    <tr><td style="color:#6b7280;width:40%">Booking reference</td><td><strong>{{ $booking->reference }}</strong></td></tr>
    <tr><td style="color:#6b7280">Tour</td><td>{{ $booking->tour?->name }}</td></tr>
    <tr><td style="color:#6b7280">Departure</td><td>{{ $departure?->departs_at?->format('l, F j, Y g:i A') }}</td></tr>
    <tr><td style="color:#6b7280">Pickup point</td><td>{{ $booking->pickupPoint?->name }}</td></tr>
    <tr><td style="color:#6b7280">Passengers</td><td>{{ $booking->participant_count }}</td></tr>
    <tr><td style="color:#6b7280">Total</td><td>{{ money($booking->total_amount, $booking->currency) }} {{ setting('price_tax_note') }}</td></tr>
    <tr><td style="color:#6b7280">Deposit due now</td><td><strong>{{ money($booking->deposit_amount, $booking->currency) }}</strong></td></tr>
    @if($forAdmin)
      <tr><td style="color:#6b7280">Customer</td><td>{{ $booking->first_name }} {{ $booking->last_name }} · {{ $booking->phone }} · {{ $booking->email }}</td></tr>
      <tr><td style="color:#6b7280">Emergency contact</td><td>{{ $booking->emergency_contact_name }} · {{ $booking->emergency_contact_phone }}</td></tr>
    @endif
  </table>
  <p style="margin-bottom:4px"><strong>Passengers</strong></p>
  <ul style="margin-top:0;padding-left:18px">
    @foreach($booking->passengers as $passenger)
      <li>{{ $passenger->first_name }} {{ $passenger->last_name }} — {{ \App\Models\TourDeparturePrice::CODES[$passenger->price_code][0] ?? $passenger->price_code }}@if($passenger->room_number), room {{ $passenger->room_number }}@endif</li>
    @endforeach
  </ul>
  @unless($forAdmin)
    @if(setting('payment_instructions'))
      <p style="margin-bottom:4px"><strong>Payment instructions</strong></p>
      <p style="margin-top:0">{!! nl2br(e(setting('payment_instructions'))) !!}</p>
    @endif
    <p>Your seat numbers will be emailed to you 1–2 days before departure once the booking is fully paid.</p>
    <p>Questions? Reply to this email or message us on WhatsApp.</p>
  @endunless
@endsection
