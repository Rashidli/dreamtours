@extends('errors.layout')
@section('code', '419')
@section('title', 'This page has expired')
@section('tone', '--er-bg:#e0f0ff;--er-fg:#00527c')
@section('icon')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>@endsection
@section('message', 'For your security, forms expire when a page has been left open for a long time, so nothing was saved.')
@section('hint', 'Go back, refresh the page, and send the form again. If you were signed out, sign in first.')
@section('actions')
  <a class="er-btn er-btn-primary" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Go back and refresh</a>
  @if(request()->is('admin*'))<a class="er-btn" href="{{ url('/admin') }}">Sign in again</a>@else<a class="er-btn" href="{{ url('/') }}">Go to the home page</a>@endif
@endsection
