@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
@php($first = $emails->filter(fn ($e) => $e['available'])->keys()->first())
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="tm-title"><h4>Email previews</h4><span class="text-muted">{{ $emails->count() }} automatic {{ Str::plural('email', $emails->count()) }}</span></div>
  </div>
  <p class="tm-lead">Each email is shown as it arrives, filled with the latest real booking or request. Nothing is sent from this page.</p>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card"><div class="card-body p-0">
        <ul class="tm-mail-list" aria-label="Automatic emails">
          @foreach($emails as $key => $email)
            <li>
              @if($email['available'])
                <a href="{{ route('email-previews.show', $key) }}" target="email-preview" class="tm-mail-item {{ $key === $first ? 'active' : '' }}" data-mail-title="{{ $email['title'] }}">
                  <i class="ri-mail-line" aria-hidden="true"></i><span class="tm-mail-title">{{ $email['title'] }}</span><span class="badge bg-light">{{ $email['to'] }}</span>
                </a>
              @else
                <span class="tm-mail-item tm-off" title="No record to fill it with yet">
                  <i class="ri-mail-forbid-line" aria-hidden="true"></i><span class="tm-mail-title">{{ $email['title'] }}</span><span class="badge bg-light">No sample yet</span>
                </span>
              @endif
            </li>
          @endforeach
        </ul>
        <p class="tm-muted px-3 pb-3 mb-0">Office alerts go to the <a href="{{ route('settings.index') }}">Notification email</a> setting.</p>
      </div></div>
    </div>
    <div class="col-lg-8">
      <div class="card h-100 overflow-hidden"><div class="card-body p-0">
        @if($first)
          <div class="tm-mail-bar">
            <strong id="mail-preview-title">{{ $emails[$first]['title'] }}</strong>
            <a href="{{ route('email-previews.show', $first) }}" id="mail-preview-open" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="ri-external-link-line"></i> Open in new tab</a>
          </div>
          <iframe name="email-preview" title="Email preview" class="tm-mail-frame" src="{{ route('email-previews.show', $first) }}"></iframe>
        @else
          <div class="tm-empty"><i class="ri-mail-line"></i><strong>No emails to preview yet</strong>Previews appear once there is a booking or request to fill them with.</div>
          <iframe name="email-preview" title="Email preview" class="d-none" src="about:blank"></iframe>
        @endif
      </div></div>
    </div>
  </div>

</div></div></div>
<script>
// Highlights the email shown in the preview and keeps the title / "Open in new tab" link in step.
document.addEventListener('click', function (e) {
  const link = e.target.closest('a.tm-mail-item');
  if (!link) return;
  document.querySelectorAll('a.tm-mail-item.active').forEach(function (a) { a.classList.remove('active'); });
  link.classList.add('active');
  const title = document.getElementById('mail-preview-title'); if (title) title.textContent = link.dataset.mailTitle;
  const open = document.getElementById('mail-preview-open'); if (open) open.href = link.href;
});
</script>
@include('admin.includes.footer')
