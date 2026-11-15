@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Activity history</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Activity history</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">
<p class="text-muted small">Every important change and email, newest first. Entries cannot be edited or deleted. Times are {{ str($timezone)->after('/')->replace('_', ' ') }} time.</p>
<form class="row g-2 mb-3">
    <div class="col-md-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search record, person or reason"></div>
    <div class="col-md-2"><select class="form-select" name="actor" aria-label="Who"><option value="">Anyone</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('actor') == $user->id)>{{ $user->name }}</option>@endforeach<option value="system" @selected(request('actor') === 'system')>System (automatic)</option><option value="visitor" @selected(request('actor') === 'visitor')>Website visitor</option></select></div>
    <div class="col-md-2"><select class="form-select" name="module" aria-label="Module"><option value="">All modules</option>@foreach($modules as $module)<option @selected(request('module') === $module)>{{ $module }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="action" aria-label="Action"><option value="">All actions</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->ucfirst() }}</option>@endforeach</select></div>
    <div class="col-md-1"><input class="form-control" type="number" min="1" name="record" value="{{ request('record') }}" placeholder="ID" title="Record ID"></div>
    <div class="col-md-1"><input class="form-control" type="date" name="from" value="{{ request('from') }}" title="From"></div>
    <div class="col-md-1"><input class="form-control" type="date" name="to" value="{{ request('to') }}" title="To"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-outline-primary">Filter</button>@if(collect(request()->except('page'))->filter()->isNotEmpty())<a class="btn btn-light" href="{{ route('activity-log.index') }}">Reset</a>@endif</div>
</form>
<div class="table-responsive"><table class="table table-sm align-top">
    <thead><tr><th style="width:130px">When</th><th style="width:170px">Who</th><th style="width:120px">Action</th><th style="width:220px">Record</th><th>Summary</th><th style="width:110px">From</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr>
            <td class="small text-nowrap" title="{{ $log->created_at->utc()->format('Y-m-d H:i:s') }} UTC">{{ $log->created_at->timezone($timezone)->format('M j, Y') }}<br><span class="text-muted">{{ $log->created_at->timezone($timezone)->format('g:i:s A') }}</span></td>
            <td class="small">{{ $log->actor_name }}@if($log->actor_role)<br><span class="badge bg-light text-body">{{ $log->actor_role }}</span>@endif</td>
            <td><span class="badge {{ match(true) { str_contains($log->action, 'deleted') || str_contains($log->action, 'failed') => 'bg-danger-subtle text-danger', $log->action === 'created' => 'bg-success-subtle text-success', str_starts_with($log->action, 'email') => 'bg-info-subtle text-info', default => 'bg-secondary-subtle text-secondary' } }}">{{ str($log->action)->replace('_', ' ') }}</span></td>
            <td class="small"><span class="text-muted">{{ $log->moduleLabel() }}</span><br>@if($url = $log->subjectUrl())<a href="{{ $url }}">{{ $log->subject_label }}</a>@else{{ $log->subject_label }}@endif @if($log->subject_id)<span class="text-muted">#{{ $log->subject_id }}</span>@endif</td>
            <td class="small">
                @php($fields = collect($log->changes ?? []))
                @php($isEdit = $fields->isNotEmpty() && $fields->every(fn ($v) => is_array($v) && array_is_list($v) && count($v) === 2))
                <span class="text-muted">{{ $isEdit ? str($fields->keys()->map(fn ($k) => str($k)->replace('_', ' '))->take(4)->implode(', '))->limit(80).($fields->count() > 4 ? ' +'.($fields->count() - 4).' more' : '') : str($fields->map(fn ($v) => is_array($v) ? null : $v)->filter()->first() ?? '')->limit(60) }}</span>
                @if($fields->isNotEmpty() || $log->reason)
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 ms-1" data-bs-toggle="modal" data-bs-target="#activityDetails" data-log="{{ $log->id }}">Details</button>
                    <template id="activity-{{ $log->id }}">
                        <p class="small text-muted mb-2">{{ $log->created_at->timezone($timezone)->format('M j, Y g:i:s A') }} · {{ $log->actor_name }}{{ $log->actor_role ? ' ('.$log->actor_role.')' : '' }} · {{ str($log->action)->replace('_', ' ') }} · {{ $log->moduleLabel() }}: {{ $log->subject_label }}</p>
                        @include('admin.activity_log.entry')
                        @if($log->user_agent)<p class="small text-muted mt-3 mb-0 text-break">{{ $log->origin }} · {{ $log->ip }} · {{ $log->user_agent }}</p>@endif
                    </template>
                @endif
            </td>
            <td class="small text-muted">{{ $log->origin }}@if($log->ip)<br>{{ $log->ip }}@endif</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No activity matches these filters.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $logs->links('admin.vendor.pagination.bootstrap-5') }}
</div></div>

<div class="modal fade" id="activityDetails" tabindex="-1" aria-labelledby="activityDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="activityDetailsTitle">Activity details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"></div>
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
