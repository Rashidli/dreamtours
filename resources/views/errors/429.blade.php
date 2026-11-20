@extends('errors.layout')
@section('code', '429')
@section('title', 'Too many attempts')
@section('tone', '--er-bg:#fff1b8;--er-fg:#5e4200')
@section('icon')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12M6 22h12M7 2c0 5 10 5 10 10S7 17 7 22M17 2c0 5-10 5-10 10s10 5 10 10"/></svg>@endsection
@section('message', 'We received too many requests from you in a short time, so we paused them for a moment.')
@section('hint', request()->is('admin*') ? 'Wait a minute, then try again. If you keep getting this while signing in, check your email and password carefully or ask an administrator to reset your password.' : 'Please wait a minute, then try again.')
@section('actions')
  <a class="er-btn er-btn-primary" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Try again</a>
  <a class="er-btn" href="{{ url('/') }}">Go to the home page</a>
@endsection
