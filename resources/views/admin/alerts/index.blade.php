@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-alerts.css') }}?v={{ @filemtime(public_path('assets/css/admin-alerts.css')) }}">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $done = $f['show'] === 'done';
  $type = $f['type'] ?? null;
  $mine = request()->boolean('mine');
  $openTotal = $counts->sum();
  // Query-string helper that keeps the other filters.
  $url = fn (array $changes) => route('alerts.index', array_filter(array_merge(['show' => $done ? 'done' : null, 'type' => $type, 'mine' => $mine ? 1 : null], $changes)));

  // What to do about each kind of alert: the button label and a one-line "why / next step" hint.
  $guide = [
    'new_inquiry' => ['Open lead', 'Reply while the customer is still deciding — the alert closes when the lead moves stage.'],
    'payment_pending' => ['Verify payment', 'Check the transfer arrived, then confirm or reject it — the alert closes itself.'],
    'balance_due_soon' => ['Open booking', 'Remind the customer before the due date; closes once it is paid.'],
    'booking_cancelled' => ['Open booking', 'Check refunds and free seats for the waitlist.'],
    'tour_almost_full' => ['See passengers', 'Few seats left — decide whether to add capacity or stop selling.'],
    'tour_full' => ['See passengers', 'No seats left — new customers can only join the waitlist.'],
    'upcoming_tour' => ['See passengers', 'Check the passenger list, pick-ups and payments before departure.'],
    'waitlist_joined' => ['Open', 'Someone is waiting for a seat on this departure.'],
    'waitlist_seat_free' => ['Open', 'A seat freed up — offer it to the next person on the waitlist.'],
    'email_failed' => ['Open', 'The customer did not get the email — check the address or contact them another way.'],
    'support_request' => ['Read message', 'A visitor wrote through the contact form; it closes when marked read.'],
  ];
  // Icons missing from the icon font, swapped for ones that exist.
  $iconFix = ['ri-funnel-line' => 'ri-user-received-line'];
  $meta = function ($alert) use ($iconFix) {
      [$label, $icon, $colour] = \App\Support\Alerts::TYPES[$alert->type] ?? ['Alert', 'ri-notification-3-line', 'secondary'];
      return [$label, $iconFix[$icon] ?? $icon, $colour];
  };

  // Today / Yesterday / Earlier, by when it was raised (open) or closed (done).
  $groups = collect($alerts->items())->groupBy(function ($alert) use ($done, $tz) {
      $at = ($done ? ($alert->completed_at ?? $alert->created_at) : $alert->created_at)->copy()->timezone($tz);
      return $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : 'Earlier');
  });

  // "Mark all done" applies to every open alert of the chosen type (not only the assigned ones).
  $markAllCount = $type ? (int) ($counts[$type] ?? 0) : (int) $openTotal;
  $typeLabel = $type ? \App\Support\Alerts::TYPES[$type][0] : null;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-baseline gap-2"><h4 class="mb-0">Alerts</h4><span class="text-muted">{{ $openTotal ? $openTotal.' open' : 'All caught up' }}</span></div>
                        <div class="d-flex flex-wrap gap-2">
                          @can('edit-settings')<button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#alert-settings-modal"><i class="ri-settings-3-line me-1"></i>Settings</button>@endcan
                          @if(!$done && $markAllCount)
                            <form method="post" action="{{ route('alerts.complete-all') }}" onsubmit="return confirm(@js('Mark '.($markAllCount === 1 ? 'the 1 open' : 'all '.$markAllCount.' open').($typeLabel ? ' “'.$typeLabel.'”' : '').' '.str('alert')->plural($markAllCount).' as done? They are closed for everyone.'))">@csrf<input type="hidden" name="type" value="{{ $type ?? '' }}"><button class="btn btn-light"><i class="ri-check-double-line me-1"></i>Mark all done</button></form>
                          @endif
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<div class="card tl-card al-card"><div class="card-body p-0">
  <div class="tl-tabs-bar">
    <nav class="tl-tabs" aria-label="Alert status">
      <a class="tl-tab {{ $done ? '' : 'active' }}" href="{{ $url(['show' => null]) }}" @if(!$done) aria-current="page" @endif>Open @if($openTotal)<span class="al-tab-count">{{ $openTotal }}</span>@endif</a>
      <a class="tl-tab {{ $done ? 'active' : '' }}" href="{{ $url(['show' => 'done']) }}" @if($done) aria-current="page" @endif>Done</a>
    </nav>
    <a class="al-mine ms-auto {{ $mine ? 'active' : '' }}" href="{{ $url(['mine' => $mine ? null : 1]) }}" aria-pressed="{{ $mine ? 'true' : 'false' }}"><i class="{{ $mine ? 'ri-checkbox-circle-fill' : 'ri-user-line' }}"></i>Assigned to me</a>
  </div>

  {{-- Kind of alert: one tap filters, the count says where the work is. --}}
  <div class="al-types" role="group" aria-label="Kind of alert">
    <a class="al-type {{ $type ? '' : 'active' }}" href="{{ $url(['type' => null]) }}">All kinds</a>
    @foreach(\App\Support\Alerts::TYPES as $t => [$label, $icon, $colour, $permission])
      @continue(!auth()->user()->can($permission))
      @continue(!($counts[$t] ?? 0) && $type !== $t && !$done)
      <a class="al-type {{ $type === $t ? 'active' : '' }}" href="{{ $url(['type' => $type === $t ? null : $t]) }}"><i class="{{ $iconFix[$icon] ?? $icon }} al-c-{{ $colour }}"></i>{{ $label }}@if(!$done && ($counts[$t] ?? 0))<span class="al-type-count">{{ $counts[$t] }}</span>@endif</a>
    @endforeach
  </div>

  @forelse($groups as $group => $items)
    <div class="al-group">{{ $group }}</div>
    @foreach($items as $alert)
      @php
        [$label, $icon, $colour] = $meta($alert);
        [$actionLabel, $hint] = $guide[$alert->type] ?? ['Open', null];
        $created = $alert->created_at->copy()->timezone($tz);
      @endphp
      <div class="al-row al-tone-{{ $colour }} {{ $alert->completed_at ? 'is-done' : 'is-open' }}">
        <span class="al-icon" aria-hidden="true"><i class="{{ $alert->completed_at ? 'ri-check-line' : $icon }}"></i></span>
        <div class="al-main">
          <div class="al-kind"><span class="al-c-{{ $colour }}">{{ $label }}</span>@if($alert->assignedUser)<span class="al-assignee"><i class="ri-user-line"></i>{{ $alert->assignedUser->id === auth()->id() ? 'You' : $alert->assignedUser->name }}</span>@endif</div>
          <div class="al-title">@if($alert->url)<a href="{{ $alert->url }}">{{ $alert->title }}</a>@else{{ $alert->title }}@endif</div>
          @if($alert->body)<div class="al-body">{{ $alert->body }}</div>@endif
          @if($alert->completed_at)
            <div class="al-resolved"><i class="ri-checkbox-circle-fill"></i>{{ $alert->completed_note ?: 'Done' }} · {{ $alert->completed_at->copy()->timezone($tz)->format('M j, g:i A') }}{{ $alert->completedBy ? ' by '.$alert->completedBy->name : ' (automatic)' }}</div>
          @elseif($hint)
            <div class="al-hint">{{ $hint }}</div>
          @endif
        </div>
        <div class="al-side">
          <time class="al-when" datetime="{{ $alert->created_at->toIso8601String() }}" title="{{ $created->format('D, M j, Y g:i A') }}">{{ $created->isToday() ? $created->format('g:i A') : $created->format('M j') }}<span>{{ $alert->created_at->diffForHumans(short: true) }}</span></time>
          <div class="al-actions">
            @if($alert->url)<a class="btn btn-light" href="{{ $alert->url }}">{{ $alert->completed_at ? 'Open' : $actionLabel }}<i class="ri-arrow-right-s-line ms-1"></i></a>@endif
            @unless($alert->completed_at)<form method="post" action="{{ route('alerts.complete', $alert) }}">@csrf<button class="btn al-done" title="Mark this alert done for everyone"><i class="ri-check-line me-1"></i>Mark done</button></form>@endunless
          </div>
        </div>
      </div>
    @endforeach
  @empty
    <div class="al-empty">
      <span class="al-empty-icon"><i class="{{ $done ? 'ri-history-line' : 'ri-check-double-line' }}"></i></span>
      @if($done)
        <strong>Nothing marked done{{ $type || $mine ? ' with these filters' : ' yet' }}</strong>
        <span>Alerts you or the system close appear here, with who closed them and when.</span>
      @elseif($type || $mine)
        <strong>No open alerts with these filters</strong>
        <span><a href="{{ route('alerts.index') }}">Show all open alerts</a></span>
      @else
        <strong>You’re all caught up</strong>
        <span>New inquiries, payments to verify, cancellations and tour updates will show up here.</span>
      @endif
    </div>
  @endforelse

  <div class="tl-pager">{{ $alerts->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>

@can('edit-settings')
<div class="modal fade" id="alert-settings-modal" tabindex="-1" aria-labelledby="alert-settings-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
  <form method="post" action="{{ route('alerts.settings') }}">@csrf
    <div class="modal-header"><h5 class="modal-title" id="alert-settings-title">Which alerts to raise</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <p class="text-muted mb-3">In-app alerts for everyone allowed to act on them. Thresholds (seats left for “almost full”, days ahead for balances and tours) are in <a href="{{ route('settings.index') }}">Settings › Staff alerts</a>. Email and WhatsApp delivery can be added once those channels are connected.</p>
      <div class="al-switches">
        @foreach(\App\Support\Alerts::TYPES as $t => [$label, $icon, $colour, $permission])
          <div class="form-check form-switch al-switch"><input class="form-check-input" type="checkbox" role="switch" name="on[]" value="{{ $t }}" id="alert-on-{{ $t }}" @checked(\App\Support\Alerts::enabled($t))><label class="form-check-label" for="alert-on-{{ $t }}"><span class="fw-semibold"><i class="{{ $iconFix[$icon] ?? $icon }} al-c-{{ $colour }} me-1"></i>{{ $label }}</span><span class="text-muted d-block">Seen by people with {{ \App\Support\PermissionGroups::label($permission) }}</span></label></div>
        @endforeach
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Save</button></div>
  </form>
</div></div></div>
@endcan
</div></div></div>@include('admin.includes.footer')
