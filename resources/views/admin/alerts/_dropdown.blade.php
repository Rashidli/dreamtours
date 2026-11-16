{{-- The bell's list: newest open alerts this person may act on. --}}
@forelse($alerts as $alert)
  @php([$aLabel, $aIcon, $aColour] = \App\Support\Alerts::TYPES[$alert->type] ?? ['Alert', 'ri-notification-3-line', 'secondary'])
  <div class="d-flex gap-2 px-3 py-2 border-bottom">
    <i class="{{ $aIcon }} text-{{ $aColour }} fs-5"></i>
    <a href="{{ $alert->url ?: route('alerts.index') }}" class="flex-grow-1 text-body"><span class="fw-semibold d-block">{{ str($alert->title)->limit(60) }}</span><span class="text-muted">{{ $alert->created_at->diffForHumans() }}</span></a>
    <form method="post" action="{{ route('alerts.complete', $alert) }}">@csrf<button class="btn btn-sm btn-light" title="Mark done"><i class="ri-check-line"></i></button></form>
  </div>
@empty
  <div class="p-4 text-center text-muted"><i class="ri-check-double-line fs-3 d-block"></i>You're all caught up.</div>
@endforelse
