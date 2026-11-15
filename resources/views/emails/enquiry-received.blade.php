@extends('emails.layout')

@php
  $name = trim("{$booking->first_name} {$booking->last_name}");
  $about = $booking->tour?->name;
  $reply = $booking->type === 'callback' ? 'call you back' : 'get back to you';
@endphp

@if($forAdmin)
  @section('audience', 'admin')
  @section('tone', 'info')
  @section('status', 'New '.strtolower($label))
  @section('title', $name)
  @section('preheader', $label.($about ? " · {$about}" : '').' · '.$booking->phone)
  @section('intro')
    New {{ strtolower($label) }} from the website{{ $about ? ' about '.$about : '' }}. Reply to this email to answer {{ $booking->first_name }} directly.
  @endsection
@else
  @section('tone', 'pending')
  @section('status', 'Request received')
  @section('title', 'Thanks, '.$booking->first_name.' — we got your message')
  @section('preheader', "We'll {$reply} as soon as possible.")
  @section('intro')
    We received your {{ strtolower($label) }}{{ $about ? ' about '.$about : '' }}. A DreamTour representative will {{ $reply }} as soon as possible.
  @endsection
@endif

@section('body')
  @include('emails.partials.rows', ['title' => $forAdmin ? 'Contact details' : 'Your request', 'rows' => [
    'Request' => $label,
    'Tour' => $about,
    'Name' => $name,
    'Email' => $forAdmin ? $booking->email : null,
    'Phone' => $booking->phone,
    'Guests' => $booking->participant_count > 1 ? $booking->participant_count : null,
    'Reference' => $booking->reference,
  ]])
  @include('emails.partials.rows', ['title' => 'Message', 'rows' => ['Details' => $booking->comments]])
  @if($forAdmin)
    @include('emails.partials.button', ['url' => route('bookings.show', $booking), 'label' => 'Open request in admin'])
  @endif
@endsection
