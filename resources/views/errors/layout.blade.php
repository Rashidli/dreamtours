{{-- Shared error page for the public site and the admin. Self-contained: no includes, no queries
     that can fail (settings are read inside try/catch with fallbacks). --}}
@php
  $isAdmin = request()->is('admin*');
  $siteName = 'DreamTour';
  $mark = null;
  try {
      $siteName = (string) (setting('site_name') ?: config('app.name') ?: 'DreamTour');
      $mark = setting_image('favicon');
  } catch (\Throwable $e) {
      $mark = null;
  }
  $homeUrl = url('/');
  $dashboardUrl = null;
  try {
      $dashboardUrl = \Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/admin');
  } catch (\Throwable $e) {
      $dashboardUrl = url('/admin');
  }
  $code = trim($__env->yieldContent('code'));
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title') · {{ $siteName }}</title>
<link rel="icon" href="{{ $mark ?: asset('favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
  *, *::before, *::after { box-sizing: border-box; }
  html, body { margin: 0; }
  body { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; padding: 32px 16px;
    background: #f1f1f1; color: #303030; font: 14px/1.5 Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; -webkit-font-smoothing: antialiased; }
  a { color: #005bd3; }
  .er-brand { display: inline-flex; align-items: center; gap: 10px; color: #303030; text-decoration: none; font-weight: 650; font-size: 16px; }
  .er-brand img, .er-brand .er-initial { width: 32px; height: 32px; border-radius: 8px; object-fit: contain; }
  .er-initial { display: grid; place-items: center; background: #303030; color: #fff; font-size: 16px; }
  .er-card { width: 100%; max-width: 520px; background: #fff; border-radius: 12px; box-shadow: 0 0 0 1px rgba(0,0,0,.08), 0 1px 0 rgba(0,0,0,.05), 0 4px 12px -4px rgba(0,0,0,.08); padding: 32px; }
  .er-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; margin-bottom: 16px; background: var(--er-bg, #ebebeb); color: var(--er-fg, #616161); }
  .er-icon svg { width: 24px; height: 24px; }
  .er-code { margin: 0 0 4px; color: #616161; font-size: 13px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
  h1 { margin: 0 0 8px; font-size: 22px; line-height: 1.3; font-weight: 700; letter-spacing: -.01em; }
  .er-text { margin: 0 0 6px; color: #303030; }
  .er-hint { margin: 0; color: #616161; }
  .er-box { margin-top: 16px; padding: 10px 12px; border-radius: 8px; background: #f7f7f7; color: #616161; font-size: 13px; }
  .er-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 24px; }
  .er-btn { display: inline-flex; align-items: center; justify-content: center; height: 36px; padding: 0 16px; border-radius: 8px; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; border: 0;
    background: #fff; color: #303030; box-shadow: inset 0 -1px 0 #b5b5b5, 0 0 0 1px #e3e3e3; }
  .er-btn:hover { background: #f7f7f7; }
  .er-btn-primary { background: #303030; color: #fff; box-shadow: inset 0 -1px 0 #000, 0 0 0 1px #303030; }
  .er-btn-primary:hover { background: #1a1a1a; }
  .er-btn:focus-visible { outline: 2px solid #005bd3; outline-offset: 2px; }
  .er-foot { color: #8a8a8a; font-size: 13px; text-align: center; }
  @media (max-width: 480px) { .er-card { padding: 24px 20px; } .er-actions .er-btn { flex: 1 1 100%; } h1 { font-size: 20px; } }
</style>
</head>
<body>
  <a class="er-brand" href="{{ $isAdmin ? $dashboardUrl : $homeUrl }}">@if($mark)<img src="{{ $mark }}" alt="">@else<span class="er-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>@endif{{ $siteName }}</a>
  <main class="er-card" style="@yield('tone')">
    <div class="er-icon" aria-hidden="true">@yield('icon')</div>
    <p class="er-code">Error {{ $code }}</p>
    <h1>@yield('title')</h1>
    <p class="er-text">@yield('message')</p>
    <p class="er-hint">@yield('hint')</p>
    @yield('extra')
    <div class="er-actions">
      @hasSection('actions')
        @yield('actions')
      @else
        <a class="er-btn er-btn-primary" href="{{ $isAdmin ? $dashboardUrl : $homeUrl }}">{{ $isAdmin ? 'Go to dashboard' : 'Go to the home page' }}</a>
        <a class="er-btn" href="{{ url()->previous() !== url()->current() ? url()->previous() : $homeUrl }}" onclick="if (history.length > 1) { history.back(); return false; }">Go back</a>
      @endif
    </div>
  </main>
  <p class="er-foot">{{ $siteName }}@if(!$isAdmin) · <a href="{{ $homeUrl }}">{{ preg_replace('#^https?://#', '', $homeUrl) }}</a>@endif</p>
</body>
</html>
