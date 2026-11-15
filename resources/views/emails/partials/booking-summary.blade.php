{{-- Trip, passengers and money for a tour booking; shared by the received and confirmed emails. --}}
@php
  $departure = $booking->departure;
  $mixedPickups = $booking->hasMixedPickups();
  $taxNote = $booking->tax_included ? '' : ' '.setting('price_tax_note');
  $paid = (float) $booking->amount_paid;
  $currency = $booking->currency ?: ($departure?->currency ?: 'CAD');
@endphp
@include('emails.partials.rows', ['title' => 'Your trip', 'rows' => [
  'Tour' => $booking->tour?->name,
  'Departure' => $departure ? \App\Support\DepartureCalendar::range($departure).' · '.$departure->departs_at->format('g:i A') : null,
  'Pick-up point' => $mixedPickups ? 'Per passenger — see below' : collect([$booking->pickupPoint?->name, $booking->pickupPoint?->address])->filter()->implode("\n"),
  'Guests' => $booking->participant_count.' '.str('guest')->plural($booking->participant_count),
  'Booking reference' => $booking->reference,
]])
@include('emails.partials.rows', ['title' => 'Payment', 'rows' => [
  'Total price' => money($booking->total_amount, $currency).$taxNote,
  'Deposit' => (float) $booking->deposit_amount > 0 ? money($booking->deposit_amount, $currency) : null,
  'Paid so far' => $paid > 0 ? money($paid, $currency) : null,
  'Payment status' => \App\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] ?? null,
]])
@if($booking->passengers->isNotEmpty())
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;border:1px solid #ebe7e0;border-radius:12px;border-collapse:separate;">
    <tr><td style="padding:14px 18px 4px;font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#9aa0ad;">Passengers</td></tr>
    @foreach($booking->passengers as $passenger)
      <tr><td style="padding:10px 18px;font-size:14px;color:#141824;border-top:1px solid #f1eee8;">
        <strong>{{ $passenger->first_name }} {{ $passenger->last_name }}</strong>
        @php($ageBand = $booking->tour && in_array($passenger->category, ['adult', 'child', 'infant'], true) ? $booking->tour->priceLabel($passenger->category) : str($passenger->category ?: 'adult')->replace('_', ' ')->ucfirst())
        @php($room = \App\Models\TourDeparturePrice::CODES[$passenger->price_code] ?? null)
        <span style="color:#6b7280;"> · {{ $ageBand }}@if($room && $room[1]) · {{ $room[0] }}@endif @if($passenger->room_number) · room {{ $passenger->room_number }}@endif</span>
        @if($mixedPickups && $passenger->pickupPoint)<br><span style="color:#6b7280;">Pick-up: {{ $passenger->pickupPoint->name }}</span>@endif
      </td></tr>
    @endforeach
  </table>
@endif
