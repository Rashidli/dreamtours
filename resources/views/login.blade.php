@php
  $siteName = setting('site_name', config('app.name'));
  $mark = setting_image('favicon');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Log in · {{ $siteName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ $mark ?: asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/admin-ui.css') }}?v={{ @filemtime(public_path('assets/css/admin-ui.css')) }}" rel="stylesheet" type="text/css">
    <style>
        body { min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; padding: 24px 16px; background: #ebebeb; }
        .login { width: 100%; max-width: 420px; }
        .login-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 1px 0 0 rgba(26, 26, 26, .07), 0 0 0 1px rgba(0, 0, 0, .06), 0 12px 24px -12px rgba(26, 26, 26, .25); }
        .login-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .login-mark { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; background: #1a1a1a; }
        .login-mark-text { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: #1a1a1a; color: #fff; font-weight: 700; font-size: 18px; }
        .login h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
        .login .lead-text { color: #616161; margin: 0 0 24px; }
        .login .form-control { min-height: 40px; font-size: 14px; }
        .login .btn-primary { width: 100%; min-height: 40px; font-size: 14px; }
        .login .pw-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #616161; width: 32px; height: 32px; border-radius: 8px; }
        .login .pw-toggle:hover { background: #f1f1f1; color: #303030; }
        .login-foot { text-align: center; color: #616161; font-size: 13px; margin-top: 20px; }
        .login-foot a { color: #303030; }
    </style>
</head>
<body>
<main class="login">
    <div class="login-card">
        <div class="login-brand">
            @if($mark)<img src="{{ $mark }}" alt="" class="login-mark">@else<span class="login-mark-text" aria-hidden="true">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>@endif
            <span class="fw-semibold">{{ $siteName }}</span>
        </div>
        <h1>Log in</h1>
        <p class="lead-text">Continue to the {{ $siteName }} admin.</p>

        <form action="{{ route('login_submit') }}" method="post" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="position-relative">
                    <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" autocomplete="current-password" required style="padding-right:44px">
                    <button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" onclick="const f=document.getElementById('password');const show=f.type==='password';f.type=show?'text':'password';this.setAttribute('aria-pressed',show);this.setAttribute('aria-label',show?'Hide password':'Show password');this.querySelector('i').className=show?'ri-eye-off-line':'ri-eye-line';"><i class="ri-eye-line"></i></button>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                <label class="form-check-label" for="remember">Keep me logged in on this device</label>
            </div>
            <button class="btn btn-primary" type="submit">Log in</button>
        </form>
    </div>
    <p class="login-foot"><a href="{{ url('/') }}">← Back to {{ $siteName }}</a></p>
</main>
</body>
</html>
