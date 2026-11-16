@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php($tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Alerts</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Alerts</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link {{ $f['show'] === 'open' ? 'active' : '' }}" href="{{ route('alerts.index', array_filter(['type' => $f['type'] ?? null, 'mine' => request('mine')])) }}">Open @if($counts->sum())<span class="badge rounded-pill bg-danger ms-1">{{ $counts->sum() }}</span>@endif</a></li>
  <li class="nav-item"><a class="nav-link {{ $f['show'] === 'done' ? 'active' : '' }}" href="{{ route('alerts.index', array_filter(['show' => 'done', 'type' => $f['type'] ?? null, 'mine' => request('mine')])) }}">Done</a></li>
</ul>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="d-flex flex-wrap gap-1">
    <a class="btn btn-sm {{ empty($f['type']) ? 'btn-dark' : 'btn-light' }}" href="{{ route('alerts.index', array_filter(['show' => $f['show'] === 'done' ? 'done' : null, 'mine' => request('mine')])) }}">All types</a>
    @foreach(\App\Support\Alerts::TYPES as $type => [$label, $icon, $colour, $permission])
      @continue(!auth()->user()->can($permission))
      <a class="btn btn-sm {{ ($f['type'] ?? null) === $type ? 'btn-dark' : 'btn-light' }}" href="{{ route('alerts.index', array_filter(['type' => $type, 'show' => $f['show'] === 'done' ? 'done' : null, 'mine' => request('mine')])) }}"><i class="{{ $icon }} text-{{ ($f['type'] ?? null) === $type ? 'white' : $colour }} me-1"></i>{{ $label }}@if($counts[$type] ?? 0) <span class="badge rounded-pill bg-secondary">{{ $counts[$type] }}</span>@endif</a>
    @endforeach
    <a class="btn btn-sm {{ request()->boolean('mine') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ request()->fullUrlWithQuery(['mine' => request()->boolean('mine') ? null : 1, 'page' => null]) }}"><i class="ri-user-line me-1"></i>Assigned to me</a>
  </div>
  @if($f['show'] === 'open' && $alerts->total())
    <form method="post" action="{{ route('alerts.complete-all') }}" onsubmit="return confirm('Mark {{ $alerts->total() }} open {{ $f['type'] ? 'alerts of this type' : 'alerts' }} as done?')">@csrf<input type="hidden" name="type" value="{{ $f['type'] ?? '' }}"><button class="btn btn-outline-secondary"><i class="ri-check-double-line me-1"></i>Mark all done</button></form>
  @endif
</div>

<div class="card"><div class="card-body">
  <div class="list-group list-group-flush">
  @forelse($alerts as $alert)
    @php([$label, $icon, $colour] = \App\Support\Alerts::TYPES[$alert->type] ?? ['Alert', 'ri-notification-3-line', 'secondary'])
    <div class="list-group-item px-0 d-flex flex-wrap gap-3 align-items-start">
      <i class="{{ $icon }} text-{{ $colour }} fs-3"></i>
      <div class="flex-grow-1" style="min-width:220px">
        <div class="fw-semibold">{{ $alert->title }}</div>
        @if($alert->body)<div class="text-muted">{{ $alert->body }}</div>@endif
        <div class="text-muted"><span class="badge bg-{{ $colour }} bg-opacity-10 text-{{ $colour }} me-1">{{ $label }}</span>{{ $alert->created_at->timezone($tz)->format('M j, g:i A') }} · {{ $alert->created_at->diffForHumans() }}@if($alert->assignedUser) · <i class="ri-user-line"></i> {{ $alert->assignedUser->name }}@endif
          @if($alert->completed_at)<span class="d-block text-success"><i class="ri-check-line"></i> {{ $alert->completed_note ?: 'Done' }} · {{ $alert->completed_at->timezone($tz)->format('M j, g:i A') }}{{ $alert->completedBy ? ' by '.$alert->completedBy->name : ' (automatic)' }}</span>@endif
        </div>
      </div>
      <div class="d-flex gap-2">
        @if($alert->url)<a class="btn btn-sm btn-outline-primary" href="{{ $alert->url }}">Open</a>@endif
        @unless($alert->completed_at)<form method="post" action="{{ route('alerts.complete', $alert) }}">@csrf<button class="btn btn-sm btn-light"><i class="ri-check-line me-1"></i>Done</button></form>@endunless
      </div>
    </div>
  @empty
    <div class="crm-empty"><i class="ri-check-double-line"></i>{{ $f['show'] === 'open' ? 'No open alerts. You’re all caught up.' : 'Nothing marked done yet.' }}</div>
  @endforelse
  </div>
  <div class="mt-3">{{ $alerts->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>

@can('edit-settings')
<div class="card crm-filters"><div class="card-body">
  <h5 class="card-title mb-1">Which alerts to raise</h5>
  <p class="text-muted">In-app alerts for everyone allowed to act on them. Thresholds (seats left for “almost full”, days ahead for balances and tours) are in <a href="{{ route('settings.index') }}">Settings › Staff alerts</a>. Email and WhatsApp delivery can be added once those channels are connected.</p>
  <form method="post" action="{{ route('alerts.settings') }}">@csrf
    <div class="row g-2">
      @foreach(\App\Support\Alerts::TYPES as $type => [$label, $icon, $colour, $permission])
        <div class="col-sm-6 col-xl-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="on[]" value="{{ $type }}" id="alert-on-{{ $type }}" @checked(\App\Support\Alerts::enabled($type))><label class="form-check-label" for="alert-on-{{ $type }}"><i class="{{ $icon }} text-{{ $colour }} me-1"></i>{{ $label }} <span class="text-muted">· seen by people with {{ \App\Support\PermissionGroups::label($permission) }}</span></label></div></div>
      @endforeach
    </div>
    <button class="btn btn-primary mt-3"><i class="ri-save-line me-1"></i>Save</button>
  </form>
</div></div>
@endcan
</div></div></div>@include('admin.includes.footer')
