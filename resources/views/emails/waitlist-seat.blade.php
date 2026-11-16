@extends('emails.layout')

@section('tone', 'confirmed')
@section('status', 'Seat available')
@section('title', 'Good news — a seat is free')
@section('preheader', 'A seat has opened on '.$entry->departure?->tour?->name.' on '.$entry->departure?->departs_at?->format('D, M j').'.')
@section('intro')
  Hi {{ $entry->first_name }}, you were on the waitlist for <strong>{{ $entry->departure?->tour?->name }}</strong> on <strong>{{ $entry->departure?->departs_at?->format('l, F j, Y') }}</strong>, and {{ $entry->guests > 1 ? $entry->guests.' seats are' : 'a seat is' }} now available for you.
@endsection

@section('body')
  <p style="margin:0 0 14px;color:#4b5263;">Reply to this email or call us to confirm, and we will book it in your name. Seats are held for the next person on the list if we don't hear from you soon.</p>
  @if(setting('phone'))<p style="margin:0;color:#4b5263;">Phone: <strong>{{ setting('phone') }}</strong></p>@endif
@endsection
