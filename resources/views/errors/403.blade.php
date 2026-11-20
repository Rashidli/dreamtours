@extends('errors.layout')
@section('code', '403')
@section('title', 'You don’t have access to this page')
@section('tone', '--er-bg:#fff1b8;--er-fg:#5e4200')
@section('icon')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>@endsection
@section('message', request()->is('admin*') ? 'Your account’s role doesn’t include permission for this page or action.' : 'You don’t have permission to view this page.')
@section('hint', request()->is('admin*') ? 'If you need it for your work, ask an administrator (the person who manages staff accounts) to add this permission to your role, then reload the page.' : 'If you think this is a mistake, please contact us and tell us which page you were trying to open.')
