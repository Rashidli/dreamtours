@extends('emails.layout')
@section('body')
  @php($departure = $booking->departure)
  <p style="margin-top:0">Hi {{ $booking->first_name }},</p>
  <p>Your trip is almost here! Here are your seat numbers and boarding details.</p>
  <table cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px;margin:16px 0">
    <tr><td style="color:#6b7280;width:40%">Tour</td><td><strong>{{ $booking->tour?->name }}</strong></td></tr>
    <tr><td style="color:#6b7280">Departure</td><td>{{ $departure?->departs_at?->format('l, F j, Y g:i A') }}</td></tr>
    @unless($booking->hasMixedPickups())<tr><td style="color:#6b7280">Pickup point</td><td>{{ $booking->pickupPoint?->name }} — {{ $booking->pickupPoint?->address }}</td></tr>@endunless
    <tr><td style="color:#6b7280">Reference</td><td>{{ $booking->reference }}</td></tr>
  </table>
  <table cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px">
    <tr style="background:#f5f3ef"><th align="left">Passenger</th>@if($booking->hasMixedPickups())<th align="left">Pickup point</th>@endif<th align="left">Seat</th></tr>
    @foreach($booking->passengers as $passenger)
      <tr><td style="border-bottom:1px solid #eee">{{ $passenger->first_name }} {{ $passenger->last_name }}</td>@if($booking->hasMixedPickups())<td style="border-bottom:1px solid #eee">{{ $passenger->pickupPoint?->name }} — {{ $passenger->pickupPoint?->address }}</td>@endif<td style="border-bottom:1px solid #eee"><strong>{{ $passenger->seat_number }}</strong></td></tr>
    @endforeach
  </table>
  <p>Please arrive at your pickup point 15 minutes early and bring a photo ID{{ $booking->tour?->contentItems?->contains('title', 'Valid passport') ? ' and your passport' : '' }}.</p>
  <p>Have a wonderful trip!</p>
@endsection
