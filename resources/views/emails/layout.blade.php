<!doctype html>
<html lang="en">
<body style="margin:0;background:#f5f3ef;font-family:Arial,Helvetica,sans-serif;color:#1f2430">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px"><tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden">
      <tr><td style="background:#141824;padding:20px 28px;color:#ffffff;font-size:22px;font-weight:bold">DREAM<span style="color:#e11d48">TOUR</span></td></tr>
      <tr><td style="padding:28px;font-size:15px;line-height:1.6">@yield('body')</td></tr>
      <tr><td style="padding:16px 28px;background:#f5f3ef;font-size:12px;color:#6b7280">
        {{ setting('site_name', 'DreamTour') }} · {{ setting('phone') }} · {{ setting('email') }}
      </td></tr>
    </table>
  </td></tr></table>
</body>
</html>
