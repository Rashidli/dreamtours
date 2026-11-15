@extends('emails.layout')

@if($forAdmin)
  @section('audience', 'admin')
  @section('tone', 'info')
  @section('status', 'New booking request')
  @section('title', $booking->tour?->name ?: 'New booking request')
  @section('preheader', "{$booking->first_name} {$booking->last_name} · {$booking->participant_count} guests · {$booking->reference}")
  @section('intro')
    {{ $booking->first_name }} {{ $booking->last_name }} sent a booking request on the website. It is waiting for your review.
  @endsection
@else
  @section('tone', 'pending')
  @section('status', 'Request received · pending confirmation')
  @section('title', 'We received your booking request')
  @section('preheader', "Reference {$booking->reference} — we'll confirm your booking once we've checked your payment.")
  @section('intro')
    Hi {{ $booking->first_name }}, thank you for choosing DreamTour! Your spot is <strong>not confirmed yet</strong> — we'll review your request and send a confirmation email once your payment has been checked.
  @endsection
@endif

@section('body')
  @if($forAdmin)
    @include('emails.partials.rows', ['title' => 'Customer', 'rows' => [
      'Name' => "{$booking->first_name} {$booking->last_name}",
      'Email' => $booking->email,
      'Phone' => $booking->phone,
      'WhatsApp' => $booking->whatsapp,
      'Emergency contact' => trim("{$booking->emergency_contact_name} {$booking->emergency_contact_phone}"),
    ]])
  @endif

  @include('emails.partials.booking-summary')

  @if($forAdmin)
    @include('emails.partials.rows', ['title' => 'Notes from the customer', 'rows' => [
      'Comments' => $booking->comments,
      'Special requests' => $booking->special_requests,
      'Roommate' => $booking->roommate_request,
      'Mobility' => $booking->mobility_information,
      'Food allergies' => $booking->food_allergies,
    ]])
    @include('emails.partials.button', ['url' => route('bookings.show', $booking), 'label' => 'Review booking in admin'])
    <p style="margin:8px 0 0;font-size:13px;color:#6b7280;">The link opens the admin panel; you will be asked to sign in if you aren't already.</p>
  @else
    @if(setting('payment_instructions'))
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;background:#fff4e0;border-radius:12px;">
        <tr><td style="padding:16px 18px;font-size:14px;line-height:22px;color:#5c3b00;">
          <strong style="display:block;margin-bottom:4px;color:#3d2700;">How to pay your deposit</strong>
          {!! nl2br(e(setting('payment_instructions'))) !!}
          <br>Please include your booking reference <strong>{{ $booking->reference }}</strong>.
        </td></tr>
      </table>
    @endif
    <p style="margin:22px 0 0;"><strong>What happens next</strong></p>
    <ol style="margin:6px 0 0;padding-left:20px;color:#4b5263;">
      <li>We check your request and payment.</li>
      <li>You receive a <strong>booking confirmation</strong> email.</li>
      <li>1–2 days before departure we email your seat numbers.</li>
    </ol>
  @endif
@endsection
