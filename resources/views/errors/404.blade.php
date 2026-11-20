@extends('errors.layout')
@section('code', '404')
@section('title', 'Page not found')
@section('tone', '--er-bg:#eaf4ff;--er-fg:#005bd3')
@section('icon')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>@endsection
@section('message', 'We couldn’t find the page you were looking for.')
@section('hint', request()->is('admin*') ? 'The link may be out of date, or the record may have been deleted. Check the address, or find it from the dashboard or the search.' : 'The link may be out of date, or the page may have moved. Check the address, or start again from the home page.')
@section('actions')
  @php($isAdmin = request()->is('admin*'))
  <a class="er-btn er-btn-primary" href="{{ $isAdmin ? url('/admin/home') : url('/') }}">{{ $isAdmin ? 'Go to dashboard' : 'Go to the home page' }}</a>
  <a class="er-btn" href="{{ url('/') }}" onclick="if (history.length > 1) { history.back(); return false; }">Go back</a>
  @if($isAdmin)<a class="er-btn" href="{{ url('/') }}">Open the website</a>@endif
@endsection
