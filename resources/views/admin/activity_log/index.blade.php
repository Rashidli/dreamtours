@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php($filtered = collect(request()->except('page'))->filter()->isNotEmpty())
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="tm-title"><h4>Activity history</h4><span class="text-muted">{{ $logs->total() }} {{ Str::plural('entry', $logs->total()) }}{{ $filtered ? ' match' : '' }}</span></div>
  </div>
  <p class="tm-lead">Every important change and email, newest first. Entries cannot be edited or deleted. Times are {{ str($timezone)->after('/')->replace('_', ' ') }} time.</p>

  <div class="card tl-card"><div class="card-body p-0">
    <form class="tm-filters" method="get" action="{{ route('activity-log.index') }}">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search record, person or reason" aria-label="Search record, person or reason"></label>
      <select class="form-select" name="actor" aria-label="Who"><option value="">Anyone</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('actor') == $user->id)>{{ $user->name }}</option>@endforeach<option value="system" @selected(request('actor') === 'system')>System (automatic)</option><option value="visitor" @selected(request('actor') === 'visitor')>Website visitor</option></select>
      <select class="form-select" name="module" aria-label="Module"><option value="">All modules</option>@foreach($modules as $module)<option @selected(request('module') === $module)>{{ $module }}</option>@endforeach</select>
      <select class="form-select" name="action" aria-label="Action"><option value="">All actions</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->ucfirst() }}</option>@endforeach</select>
      <input class="form-control tm-f-id" type="number" min="1" name="record" value="{{ request('record') }}" placeholder="Record ID" title="Record ID" aria-label="Record ID">
      <span class="tm-f-dates"><input class="form-control tm-f-date" type="date" name="from" value="{{ request('from') }}" title="From" aria-label="From date">–<input class="form-control tm-f-date" type="date" name="to" value="{{ request('to') }}" title="To" aria-label="To date"></span>
      <button class="btn btn-outline-secondary">Filter</button>
      @if($filtered)<a class="btn btn-link text-danger px-1" href="{{ route('activity-log.index') }}">Reset</a>@endif
    </form>

    <div class="table-responsive"><table class="table table-hover align-middle tl-table tm-table mb-0">
      <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Summary</th><th class="tm-hide-sm">From</th></tr></thead>
      <tbody>
      @forelse($logs as $log)
        @php($fields = collect($log->changes ?? []))
        @php($isEdit = $fields->isNotEmpty() && $fields->every(fn ($v) => is_array($v) && array_is_list($v) && count($v) === 2))
        @php($actionTone = match(true) { str_contains($log->action, 'deleted') || str_contains($log->action, 'failed') => 'bg-danger', $log->action === 'created' => 'bg-success', str_starts_with($log->action, 'email') => 'bg-info', default => 'bg-light' })
        <tr>
          <td class="tm-when" title="{{ $log->created_at->utc()->format('Y-m-d H:i:s') }} UTC">{{ $log->created_at->timezone($timezone)->format('M j, Y') }}<span>{{ $log->created_at->timezone($timezone)->format('g:i A') }}</span></td>
          <td class="text-nowrap"><span class="tm-strong d-block">{{ $log->actor_name }}</span>@if($log->actor_role)<span class="tm-muted">{{ $log->actor_role }}</span>@endif</td>
          <td><span class="badge {{ $actionTone }}">{{ str($log->action)->replace('_', ' ')->ucfirst() }}</span></td>
          <td class="tm-record"><span class="tm-muted">{{ $log->moduleLabel() }}</span>@if($url = $log->subjectUrl())<a href="{{ $url }}">{{ $log->subject_label }}</a>@else{{ $log->subject_label }}@endif @if($log->subject_id)<span class="tm-muted d-inline">#{{ $log->subject_id }}</span>@endif</td>
          <td class="tm-summary-cell">
            <div class="d-flex align-items-center gap-2">
              <span class="tm-summary-text">{{ $isEdit ? str($fields->keys()->map(fn ($k) => str($k)->replace('_', ' '))->take(4)->implode(', '))->limit(80).($fields->count() > 4 ? ' +'.($fields->count() - 4).' more' : '') : str($fields->map(fn ($v) => is_array($v) ? null : $v)->filter()->first() ?? '')->limit(60) }}</span>
              @if($fields->isNotEmpty() || $log->reason)
                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#activityDetails" data-log="{{ $log->id }}">Details</button>
              @endif
            </div>
            @if($fields->isNotEmpty() || $log->reason)
              <template id="activity-{{ $log->id }}">
                <dl class="tm-detail-meta">
                  <dt>When</dt><dd>{{ $log->created_at->timezone($timezone)->format('M j, Y g:i:s A') }}</dd>
                  <dt>Who</dt><dd>{{ $log->actor_name }}{{ $log->actor_role ? ' ('.$log->actor_role.')' : '' }}</dd>
                  <dt>Action</dt><dd>{{ str($log->action)->replace('_', ' ')->ucfirst() }}</dd>
                  <dt>Record</dt><dd>{{ $log->moduleLabel() }}: {{ $log->subject_label }}@if($log->subject_id) #{{ $log->subject_id }}@endif</dd>
                </dl>
                @if($fields->isNotEmpty())<h6 class="tm-detail-h">{{ $isEdit ? 'What changed' : 'Details' }}</h6>@endif
                @include('admin.activity_log.entry')
                @if($log->user_agent)<p class="tm-tech">{{ $log->origin }} · {{ $log->ip }} · {{ $log->user_agent }}</p>@endif
              </template>
            @endif
          </td>
          <td class="tm-muted tm-hide-sm">{{ $log->origin }}@if($log->ip)<span class="d-block">{{ $log->ip }}</span>@endif</td>
        </tr>
      @empty
        <tr><td colspan="6" class="tm-empty"><i class="ri-history-line"></i>
          @if($filtered)<strong>No activity matches these filters</strong><a href="{{ route('activity-log.index') }}">Reset filters</a>
          @else<strong>No activity yet</strong>Changes and emails appear here as they happen.@endif
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $logs->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

  <div class="modal fade" id="activityDetails" tabindex="-1" aria-labelledby="activityDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="activityDetailsTitle">Activity details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body tm-activity-body"></div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
    </div></div>
  </div>
  <script>
    // One shared dialog: each row's details sit in a <template> and are copied in when its button is pressed.
    document.getElementById('activityDetails').addEventListener('show.bs.modal', function (event) {
      const source = document.getElementById('activity-' + event.relatedTarget.dataset.log);
      this.querySelector('.modal-body').replaceChildren(source.content.cloneNode(true));
    });
  </script>
</div></div></div>@include('admin.includes.footer')
