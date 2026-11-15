{{--
  DreamTour email layout. Table-based with inline styles so it renders the same in
  Gmail, Outlook and Apple Mail; 600px wide, full width on phones.
  Sections: preheader (inbox preview), status + tone (pill above the title:
  pending | confirmed | info | alert), title, intro, body, and audience (customer | admin).
--}}
@php
  $tones = ['pending' => ['#fff4e0', '#9a5b00'], 'confirmed' => ['#e3f6ea', '#17663a'], 'info' => ['#ffe4ef', '#b0034b'], 'alert' => ['#fde8e8', '#a11a1a']];
  [$pillBg, $pillFg] = $tones[trim($__env->yieldContent('tone', 'info'))] ?? $tones['info'];
  $isAdmin = trim($__env->yieldContent('audience')) === 'admin';
  $site = setting('site_name', 'DreamTour');
  $siteUrl = rtrim(config('app.url'), '/');
  $phone = setting('phone');
  $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp'));
  $email = setting('email');
@endphp
<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>@yield('title')</title>
  <style>
    @media only screen and (max-width: 620px) {
      .dt-pad { padding-left: 20px !important; padding-right: 20px !important; }
      .dt-title { font-size: 24px !important; line-height: 30px !important; }
      .dt-stack, .dt-stack td { display: block !important; width: 100% !important; }
      .dt-label { padding-bottom: 0 !important; }
    }
    a { color: #f80a6a; }
  </style>
</head>
<body style="margin:0;padding:0;background:#f5f3ef;-webkit-text-size-adjust:100%;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;">@yield('preheader')&#8202;&zwnj;&nbsp;&#8202;&zwnj;&nbsp;&#8202;&zwnj;&nbsp;</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f3ef;">
    <tr><td align="center" style="padding:28px 12px;">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">

        {{-- Brand bar --}}
        <tr><td class="dt-pad" style="background:#141824;border-radius:16px 16px 0 0;padding:22px 32px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;letter-spacing:2px;color:#ffffff;">
              <a href="{{ $siteUrl }}" style="color:#ffffff;text-decoration:none;">DREAM<span style="color:#f80a6a;">TOUR</span></a>
            </td>
            @if($isAdmin)<td align="right" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#9aa0ad;">Office notification</td>@endif
          </tr></table>
        </td></tr>
        <tr><td style="background:#f80a6a;height:4px;line-height:4px;font-size:0;">&nbsp;</td></tr>

        {{-- Heading --}}
        <tr><td class="dt-pad" style="background:#ffffff;padding:32px 32px 8px;font-family:Arial,Helvetica,sans-serif;color:#1f2430;">
          @hasSection('status')
            <span style="display:inline-block;background:{{ $pillBg }};color:{{ $pillFg }};font-size:12px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:999px;padding:6px 12px;">@yield('status')</span>
          @endif
          <h1 class="dt-title" style="margin:14px 0 0;font-size:28px;line-height:34px;font-weight:800;color:#141824;">@yield('title')</h1>
          @hasSection('intro')
            <p style="margin:12px 0 0;font-size:16px;line-height:25px;color:#4b5263;">@yield('intro')</p>
          @endif
        </td></tr>

        {{-- Body --}}
        <tr><td class="dt-pad" style="background:#ffffff;padding:16px 32px 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#1f2430;">
          @yield('body')
        </td></tr>

        {{-- Help --}}
        @unless($isAdmin)
          <tr><td class="dt-pad" style="background:#141824;padding:24px 32px;font-family:Arial,Helvetica,sans-serif;color:#d7dae1;font-size:14px;line-height:22px;">
            <strong style="color:#ffffff;font-size:15px;">Questions? We're here to help.</strong><br>
            @if($phone)Call <a href="tel:{{ setting('phone_link', $phone) }}" style="color:#ffffff;">{{ $phone }}</a>@endif
            @if($whatsapp) · <a href="https://wa.me/{{ $whatsapp }}" style="color:#ffffff;">WhatsApp</a>@endif
            @if($email) · <a href="mailto:{{ $email }}" style="color:#ffffff;">{{ $email }}</a>@endif
            <br><span style="color:#9aa0ad;">Or simply reply to this email.</span>
          </td></tr>
        @endunless

        {{-- Footer --}}
        <tr><td class="dt-pad" style="background:#ebe7e0;border-radius:0 0 16px 16px;padding:18px 32px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#6b7280;">
          @if($isAdmin)
            Sent automatically by <a href="{{ $siteUrl }}" style="color:#6b7280;">{{ parse_url($siteUrl, PHP_URL_HOST) }}</a>. Reply to this email to answer the customer directly.
          @else
            {{ $site }} · <a href="{{ $siteUrl }}" style="color:#6b7280;">{{ parse_url($siteUrl, PHP_URL_HOST) }}</a><br>
            You are receiving this email because you sent a request on our website.
          @endif
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
