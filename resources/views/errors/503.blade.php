@extends('errors.layout')
@section('code', '503')
@section('title', 'Down for maintenance')
@section('tone', '--er-bg:#e0f0ff;--er-fg:#00527c')
@section('icon')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5z"/></svg>@endsection
@section('message', 'We’re making some improvements right now. Your bookings and information are safe.')
@section('hint', 'We’ll be back in a few minutes — please try again shortly.')
@section('actions')
  <a class="er-btn er-btn-primary" href="{{ url()->current() }}">Try again</a>
@endsection
