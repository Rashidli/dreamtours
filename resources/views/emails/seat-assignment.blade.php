@extends('emails.layout')

@php($departure = $booking->departure)
@php($mixed = $booking->hasMixedPickups())

@section('tone', 'confirmed')
@section('status', 'Your seats are ready')
@section('title', 'Your trip is almost here!')
@section('preheader', 'Seat numbers and boarding details for '.$booking->tour?->name.'.')
@section('intro')
  Hi {{ $booking->first_name }}, here are your seat numbers and boarding details.
@endsection

@section('body')
  @include('emails.partials.rows', ['title' => 'Boarding', 'rows' => [
    'Tour' => $booking->tour?->name,
    'Departure' => $departure?->departs_at?->format('l, F j, Y · g:i A'),
    'Pick-up point' => $mixed ? null : collect([$booking->pickupPoint?->name, $booking->pickupPoint?->address])->filter()->implode("\n"),
    'Reference' => $booking->reference,
  ]])
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;border:1px solid #ebe7e0;border-radius:12px;border-collapse:separate;">
    <tr>
      <td style="padding:12px 18px;font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#9aa0ad;">Passenger</td>
      @if($mixed)<td style="padding:12px 18px;font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#9aa0ad;">Pick-up</td>@endif
      <td align="right" style="padding:12px 18px;font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#9aa0ad;">Seat</td>
    </tr>
    @foreach($booking->passengers as $passenger)
      <tr>
        <td style="padding:10px 18px;font-size:14px;color:#141824;border-top:1px solid #f1eee8;">{{ $passenger->first_name }} {{ $passenger->last_name }}</td>
        @if($mixed)<td style="padding:10px 18px;font-size:14px;color:#4b5263;border-top:1px solid #f1eee8;">{{ $passenger->pickupPoint?->name }}<br><span style="font-size:12px;color:#9aa0ad;">{{ $passenger->pickupPoint?->address }}</span></td>@endif
        <td align="right" style="padding:10px 18px;border-top:1px solid #f1eee8;"><span style="display:inline-block;min-width:34px;text-align:center;background:#141824;color:#ffffff;font-size:15px;font-weight:800;border-radius:8px;padding:6px 8px;">{{ $passenger->seat_number }}</span>@if(($booking->departure?->vehicles?->count() ?? 0) > 1 && $passenger->departureVehicle)<br><span style="font-size:12px;color:#4b5263;">{{ $passenger->departureVehicle->label }}</span>@endif</td>
      </tr>
    @endforeach
  </table>
  <p style="margin:22px 0 0;color:#4b5263;">Please arrive at your pick-up point 15 minutes early and bring a photo ID{{ $booking->tour?->contentItems?->contains('title', 'Valid passport') ? ' and your passport' : '' }}.</p>
  <p style="margin:12px 0 0;"><strong>Have a wonderful trip!</strong></p>
@endsection
