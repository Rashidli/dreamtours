@extends('errors.layout')
@section('code', '500')
@section('title', 'Something went wrong on our side')
@section('tone', '--er-bg:#fedad9;--er-fg:#8e0b21')
@section('icon')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17h.01"/></svg>@endsection
@section('message', 'Sorry — the page couldn’t be loaded because of an error on our server. It wasn’t anything you did.')
@section('hint', 'Try again in a minute. If it keeps happening, let us know what you were doing and the reference below.')
@section('extra')
  @php
    try { $errAt = now()->utc()->format('Y-m-d H:i:s').' UTC'; } catch (\Throwable $e) { $errAt = gmdate('Y-m-d H:i:s').' UTC'; }
  @endphp
  <div class="er-box">Reference: {{ $errAt }} · {{ substr(request()->path(), 0, 80) }}</div>
@endsection
@section('actions')
  <a class="er-btn er-btn-primary" href="{{ url()->current() }}">Try again</a>
  <a class="er-btn" href="{{ request()->is('admin*') ? url('/admin/home') : url('/') }}">{{ request()->is('admin*') ? 'Go to dashboard' : 'Go to the home page' }}</a>
@endsection
