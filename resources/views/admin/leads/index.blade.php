@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-leads.css') }}?v={{ @filemtime(public_path('assets/css/admin-leads.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $filters = collect(request()->except(['page', 'view']))->filter(fn ($v) => $v !== null && $v !== '');
  $moreFilters = $filters->keys()->intersect(['type', 'campaign', 'tour', 'from', 'to', 'trip_from', 'trip_to', 'contacted_from', 'contacted_to'])->isNotEmpty();
  $total = $view === 'list' ? $leads->total() : $counts->sum();

  // Active filters as removable chips (same pattern as the tours list).
  $remove = fn (array $keys) => request()->fullUrlWithQuery(array_fill_keys($keys, null) + ['page' => null]);
  $day = fn ($value) => rescue(fn () => \Illuminate\Support\Carbon::parse($value)->format('M j, Y'), $value, false);
  $range = fn (string $label, string $from, string $to) => request()->filled($from) || request()->filled($to)
      ? [$label.': '.(request()->filled($from) ? $day(request($from)) : '…').' – '.(request()->filled($to) ? $day(request($to)) : '…'), $remove([$from, $to])] : null;
  $assignedLabel = ['me' => 'Assigned to me', 'none' => 'Unassigned'][request('assigned')] ?? ('Assigned: '.($users->firstWhere('id', request('assigned'))?->name ?? '#'.request('assigned')));
  $chips = collect([
    request()->filled('q') ? ['Search: “'.request('q').'”', $remove(['q'])] : null,
    request()->boolean('due') ? ['Follow-up due', $remove(['due'])] : null,
    request()->filled('assigned') ? [$assignedLabel, $remove(['assigned'])] : null,
    request()->filled('source') ? ['Source: '.(\App\Models\Lead::SOURCES[request('source')] ?? request('source')), $remove(['source'])] : null,
    request()->filled('stage') ? ['Stage: '.($stages->firstWhere('id', request('stage'))?->name ?? '#'.request('stage')), $remove(['stage'])] : null,
    request()->filled('type') ? ['Request: '.(\App\Models\Lead::TYPES[request('type')] ?? request('type')), $remove(['type'])] : null,
    request()->filled('campaign') ? ['Campaign: '.request('campaign'), $remove(['campaign'])] : null,
    request()->filled('tour') ? ['Tour: '.($tours->firstWhere('id', request('tour'))?->name ?? '#'.request('tour')), $remove(['tour'])] : null,
    $range('Received', 'from', 'to'),
    $range('Trip date', 'trip_from', 'trip_to'),
    $range('Last contact', 'contacted_from', 'contacted_to'),
  ])->filter();
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-baseline gap-2"><h4 class="mb-0">Leads</h4><span class="text-muted">{{ $total }} {{ Str::plural('lead', $total) }}{{ $filters->isNotEmpty() ? ' match the filters' : '' }}</span></div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<div class="card tl-card crm-filters"><div class="card-body p-0">
  <div class="tl-tabs-bar ld-tabs-bar">
    <div class="tl-tabs ld-tabs" role="group" aria-label="View">
      <a class="tl-tab {{ $view === 'board' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => null, 'page' => null]) }}" @if($view === 'board') aria-current="page" @endif><i class="ri-layout-column-line"></i> Board</a>
      <a class="tl-tab {{ $view === 'list' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" @if($view === 'list') aria-current="page" @endif><i class="ri-list-check"></i> List</a>
    </div>
    <div class="ld-actions">
      @if($due)<a class="btn {{ request()->boolean('due') ? 'btn-danger' : 'btn-outline-danger' }}" href="{{ request()->fullUrlWithQuery(['due' => request()->boolean('due') ? null : 1]) }}"><i class="ri-alarm-warning-line"></i> {{ $due }} follow-up{{ $due === 1 ? '' : 's' }} due</a>@endif
      @can('manage-lead-stages')<a class="btn btn-light" href="{{ route('lead-stages.index') }}"><i class="ri-settings-3-line"></i> Stages</a>@endcan
      @can('create-leads')<a class="btn btn-primary" href="{{ route('leads.create') }}"><i class="ri-add-line"></i> New lead</a>@endcan
    </div>
  </div>

  <form class="tl-search-bar ld-filters" id="lead-filters">
    @if($view === 'list')<input type="hidden" name="view" value="list">@endif
    @if(request()->boolean('due'))<input type="hidden" name="due" value="1">@endif
    <label class="tl-search-field ld-search"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, phone or L-reference" aria-label="Search leads">@if(request()->filled('q'))<a href="{{ $remove(['q']) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
    <div class="ld-filter"><select class="form-select" name="assigned" aria-label="Assigned to" onchange="this.form.submit()">
      <option value="">Everyone</option><option value="me" @selected(request('assigned') === 'me')>Assigned to me</option><option value="none" @selected(request('assigned') === 'none')>Unassigned</option>
      @foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned') == $user->id)>{{ $user->name }}</option>@endforeach
    </select></div>
    <div class="ld-filter"><select class="form-select" name="source" aria-label="Source" onchange="this.form.submit()"><option value="">All sources</option>@foreach(\App\Models\Lead::SOURCES as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach</select></div>
    @if($view === 'list')<div class="ld-filter"><select class="form-select" name="stage" aria-label="Stage" onchange="this.form.submit()"><option value="">All stages</option>@foreach($stages as $stage)<option value="{{ $stage->id }}" @selected(request('stage') == $stage->id)>{{ $stage->name }}</option>@endforeach</select></div>@endif
    <div class="d-flex align-items-center gap-2"><button class="visually-hidden">Search</button>
      <a class="btn btn-light ld-more-toggle {{ $moreFilters ? 'active' : '' }}" data-bs-toggle="collapse" href="#moreLeadFilters" role="button" aria-expanded="{{ $moreFilters ? 'true' : 'false' }}" aria-controls="moreLeadFilters"><i class="ri-filter-3-line"></i> More filters</a></div>
    <div class="collapse ld-more {{ $moreFilters ? 'show' : '' }}" id="moreLeadFilters"><div class="ld-more-inner"><div class="row g-3">
      <div class="col-sm-6 col-lg-4 col-xl-2"><label class="fw-semibold form-label" for="f-type">Request type</label><select class="form-select" id="f-type" name="type"><option value="">Any</option>@foreach(\App\Models\Lead::TYPES as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-lg-4 col-xl-2"><label class="fw-semibold form-label" for="f-campaign">Campaign</label><select class="form-select" id="f-campaign" name="campaign"><option value="">Any</option>@foreach($campaigns as $campaign)<option @selected(request('campaign') === $campaign)>{{ $campaign }}</option>@endforeach</select></div>
      <div class="col-sm-12 col-lg-4 col-xl-2"><label class="fw-semibold form-label" for="f-tour">Tour</label><select class="form-select" id="f-tour" name="tour"><option value="">Any</option>@foreach($tours as $tour)<option value="{{ $tour->id }}" @selected(request('tour') == $tour->id)>{{ $tour->name }}</option>@endforeach</select></div>
      <div class="col-md-6 col-lg-4 col-xl-2"><label class="fw-semibold form-label">Received between</label><div class="input-group"><input class="form-control" type="date" name="from" value="{{ request('from') }}" aria-label="Received from"><input class="form-control" type="date" name="to" value="{{ request('to') }}" aria-label="Received to"></div></div>
      <div class="col-md-6 col-lg-4 col-xl-2"><label class="fw-semibold form-label">Trip date between</label><div class="input-group"><input class="form-control" type="date" name="trip_from" value="{{ request('trip_from') }}" aria-label="Trip from"><input class="form-control" type="date" name="trip_to" value="{{ request('trip_to') }}" aria-label="Trip to"></div></div>
      <div class="col-md-6 col-lg-4 col-xl-2"><label class="fw-semibold form-label">Last contact between</label><div class="input-group"><input class="form-control" type="date" name="contacted_from" value="{{ request('contacted_from') }}" aria-label="Contacted from"><input class="form-control" type="date" name="contacted_to" value="{{ request('contacted_to') }}" aria-label="Contacted to"></div></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">@if($moreFilters)<a class="btn btn-light" href="{{ $remove(['type', 'campaign', 'tour', 'from', 'to', 'trip_from', 'trip_to', 'contacted_from', 'contacted_to']) }}">Clear these</a>@endif<button class="btn btn-primary">Apply filters</button></div>
    </div></div>
  </form>

  @if($chips->isNotEmpty())
    <div class="tl-chips tl-chips-bar">
      @foreach($chips as [$label, $removeUrl])<a href="{{ $removeUrl }}" class="tl-chip" title="Remove this filter">{{ $label }} <i class="ri-close-line"></i></a>@endforeach
      <a class="btn btn-link text-danger ld-clear" href="{{ route('leads.index', $view === 'list' ? ['view' => 'list'] : []) }}">Clear all filters</a>
    </div>
  @endif

@if($view === 'board')
  <div class="lead-board" id="lead-board" data-csrf="{{ csrf_token() }}">
    @foreach($stages as $stage)
      <div class="lead-col {{ $stage->isClosed() ? 'closed' : '' }}" data-stage="{{ $stage->id }}" data-kind="{{ $stage->kind }}" data-name="{{ $stage->name }}">
        <div class="lead-col-head">
          <span class="stage-dot" style="background:{{ \App\Models\LeadStage::HEX[$stage->color] ?? '#6c757d' }}"></span>
          <span class="lead-col-title">{{ $stage->name }}</span>
          <span class="badge bg-light" data-count>{{ $counts[$stage->id] ?? 0 }}</span>
        </div>
        <div class="lead-col-body" data-drop>
          @forelse($board[$stage->id] as $lead)
            <a class="lead-card" href="{{ route('leads.show', $lead) }}" draggable="true" data-lead="{{ $lead->id }}" data-url="{{ route('leads.stage', $lead) }}">
              <div class="d-flex justify-content-between align-items-baseline gap-2">
                <span class="lead-name text-truncate">{{ $lead->full_name }}</span>
                <span class="lead-meta text-nowrap" title="Received {{ $lead->created_at->timezone($tz)->format('M j, Y g:i A') }}">{{ $lead->created_at->diffForHumans(null, true, true) }}</span>
              </div>
              <div class="lead-meta lead-request text-truncate">{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour) · {{ $lead->tour->name }}@endif</div>
              @if($lead->departure || $lead->guests)<div class="lead-meta lead-facts">@if($lead->departure)<span><i class="ri-calendar-line"></i> {{ $lead->departure->departs_at->format('M j') }}</span>@endif @if($lead->guests)<span><i class="ri-user-line"></i> {{ $lead->guests }}</span>@endif</div>@endif
              @if($lead->stage?->kind === 'lost' && $lead->lost_reason)<div class="lead-meta lead-lost text-truncate">{{ $lead->lost_reason }}</div>@endif
              <div class="lead-foot">
                @if($lead->assignedUser)<span class="lead-avatar" title="Assigned to {{ $lead->assignedUser->name }}">{{ \App\Models\Lead::initials($lead->assignedUser->name) }}</span>@else<span class="lead-avatar none" title="Unassigned">?</span>@endif
                <span class="lead-chip text-truncate" title="{{ $lead->sourceLabel() }}">{{ $lead->utm_campaign ?: (\App\Models\Lead::SOURCES[$lead->source] ?? $lead->source) }}</span>
                @if($lead->contact_attempts)<span class="lead-meta ms-auto" title="{{ $lead->contact_attempts }} contact attempts"><i class="ri-phone-line"></i> {{ $lead->contact_attempts }}</span>@endif
                @if($lead->next_follow_up_at && !$stage->isClosed())<span class="lead-meta lead-follow {{ $lead->isFollowUpDue() ? 'lead-due' : '' }} {{ $lead->contact_attempts ? '' : 'ms-auto' }}" title="Follow up {{ $lead->next_follow_up_at->timezone($tz)->format('D, M j g:i A') }}"><i class="ri-alarm-line"></i> {{ $lead->next_follow_up_at->timezone($tz)->format('M j') }}</span>@endif
              </div>
            </a>
          @empty
            <div class="lead-empty" data-empty>{{ $stage->isClosed() ? 'Nothing here yet' : 'Drop a lead here' }}</div>
          @endforelse
          @if($stage->isClosed() && ($counts[$stage->id] ?? 0) > $board[$stage->id]->count())
            <a class="d-block text-center py-2 see-all" href="{{ request()->fullUrlWithQuery(['view' => 'list', 'stage' => $stage->id]) }}">See all {{ $counts[$stage->id] }} <i class="ri-arrow-right-s-line"></i></a>
          @endif
        </div>
      </div>
    @endforeach
  </div>
  <p class="ld-board-hint"><i class="ri-drag-move-line"></i> Drag a card to another column to change its stage, or open it to log a call, set a reminder or convert it to a booking.</p>

  {{-- Lost needs a reason: asked here instead of a browser prompt. --}}
  <div class="modal fade" id="lostReason" tabindex="-1" aria-labelledby="lostReasonTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="lostReasonTitle">Why was this lead lost?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <p class="text-muted mb-2">Pick a common reason or type your own. It's shown on the card and helps the sales reports.</p>
      <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['Price too high', 'Chose another company', 'Dates did not work', 'No reply', 'Tour sold out', 'Just asking'] as $reason)<button type="button" class="btn btn-light ld-reason" data-reason="{{ $reason }}">{{ $reason }}</button>@endforeach</div>
      <label class="fw-semibold form-label" for="lost-reason-input">Reason</label>
      <input class="form-control" id="lost-reason-input" name="lost_reason" maxlength="255" placeholder="e.g. Booked with a competitor" required>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Mark as lost</button></div>
  </form></div></div>
@else
    <div class="table-responsive"><table class="table table-hover align-middle tl-table crm-table mb-0">
      <thead><tr><th>Lead</th><th class="d-none d-sm-table-cell">Request</th><th>Stage</th><th class="d-none d-md-table-cell">Assigned</th><th class="d-none d-lg-table-cell">Source</th><th class="d-none d-lg-table-cell">Last contact</th><th class="d-none d-md-table-cell">Follow-up</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @forelse($leads as $lead)
        <tr>
          <td><a class="ld-lead-link" href="{{ route('leads.show', $lead) }}">{{ $lead->full_name }}</a><div class="text-muted text-nowrap">{{ $lead->reference }} · {{ $lead->created_at->timezone($tz)->format('M j, Y') }}</div></td>
          <td class="ld-cell-request d-none d-sm-table-cell">{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour)<div class="text-muted text-truncate">{{ $lead->tour->name }}@if($lead->departure) · {{ $lead->departure->departs_at->format('M j') }}@endif @if($lead->guests) · {{ $lead->guests }} {{ str('guest')->plural($lead->guests) }}@endif</div>@endif</td>
          <td><span class="badge bg-{{ $lead->stage?->color }}">{{ $lead->stage?->name }}</span></td>
          <td class="d-none d-md-table-cell">@if($lead->assignedUser)<span class="d-inline-flex align-items-center gap-2"><span class="lead-avatar">{{ \App\Models\Lead::initials($lead->assignedUser->name) }}</span>{{ $lead->assignedUser->name }}</span>@else<span class="text-muted">Unassigned</span>@endif</td>
          <td class="d-none d-lg-table-cell text-muted">{{ $lead->sourceLabel() }}</td>
          <td class="d-none d-lg-table-cell text-nowrap">{{ $lead->last_contacted_at?->timezone($tz)->format('M j') ?? '—' }}@if($lead->contact_attempts) <span class="text-muted">({{ $lead->contact_attempts }})</span>@endif</td>
          <td class="d-none d-md-table-cell text-nowrap {{ $lead->isFollowUpDue() ? 'text-danger fw-semibold' : '' }}">@if($lead->next_follow_up_at)<i class="ri-alarm-line"></i> {{ $lead->next_follow_up_at->timezone($tz)->format('M j, g:i A') }}@else<span class="text-muted">—</span>@endif</td>
          <td class="text-end text-nowrap"><button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#lead-details-{{ $lead->id }}">Details</button></td>
        </tr>
      @empty
        <tr><td colspan="8" class="ld-empty-row"><i class="ri-inbox-line"></i><div class="fw-semibold">{{ $filters->isNotEmpty() ? 'No leads match these filters' : 'No leads yet' }}</div><div class="text-muted">{{ $filters->isNotEmpty() ? 'Try another search or remove a filter.' : 'Website requests appear here automatically. Add phone calls and walk-ins with “New lead”.' }}</div>@if($filters->isNotEmpty())<a class="btn btn-light mt-2" href="{{ route('leads.index', ['view' => 'list']) }}">Clear all filters</a>@endif</td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $leads->links('admin.vendor.pagination.bootstrap-5') }}</div>

    {{-- Details: a short summary with quick actions; the full page has the timeline and all tools. --}}
    @foreach($leads as $lead)
      @php
        $phone = $lead->phone ?: $lead->whatsapp;
        $wa = preg_replace('/\D/', '', (string) ($lead->whatsapp ?: $lead->phone));
      @endphp
      <div class="modal fade" id="lead-details-{{ $lead->id }}" tabindex="-1" aria-labelledby="lead-details-title-{{ $lead->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div class="min-w-0"><h5 class="modal-title d-flex align-items-center flex-wrap gap-2" id="lead-details-title-{{ $lead->id }}">{{ $lead->full_name }} <span class="badge bg-{{ $lead->stage?->color }}">{{ $lead->stage?->name }}</span></h5><div class="text-muted">{{ $lead->reference }} · received {{ $lead->created_at->timezone($tz)->format('M j, Y g:i A') }}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
          @if($phone || strlen($wa) >= 7 || $lead->email)
            <div class="ld-quick mb-3">
              @if($phone)<a class="btn btn-light" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><i class="ri-phone-line"></i> Call</a>@endif
              @if(strlen($wa) >= 7)<a class="btn btn-light" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i> WhatsApp</a>@endif
              @if($lead->email)<a class="btn btn-light" href="mailto:{{ $lead->email }}"><i class="ri-mail-line"></i> Email</a>@endif
            </div>
          @endif
          <dl class="ld-dl mb-0">
            <dt>Request</dt><dd>{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour) · {{ $lead->tour->name }}@endif</dd>
            @if($lead->departure)<dt>Departure</dt><dd>{{ $lead->departure->departs_at->format('D, M j, Y g:i A') }}</dd>@endif
            @if($lead->guests)<dt>Guests</dt><dd>{{ $lead->guests }}</dd>@endif
            <dt>Phone</dt><dd>{{ $lead->phone ?: ($lead->whatsapp ?: '—') }}</dd>
            <dt>Email</dt><dd class="text-break">{{ $lead->email ?: '—' }}</dd>
            <dt>Assigned</dt><dd>{{ $lead->assignedUser?->name ?? 'Unassigned' }}</dd>
            <dt>Source</dt><dd>{{ $lead->sourceLabel() }}</dd>
            <dt>Last contact</dt><dd>{{ $lead->last_contacted_at?->timezone($tz)->format('M j, Y g:i A') ?? 'Not contacted yet' }}@if($lead->contact_attempts) <span class="text-muted">· {{ $lead->contact_attempts }} {{ str('attempt')->plural($lead->contact_attempts) }}</span>@endif</dd>
            <dt>Follow-up</dt><dd class="{{ $lead->isFollowUpDue() ? 'text-danger fw-semibold' : '' }}">{{ $lead->next_follow_up_at?->timezone($tz)->format('D, M j, g:i A') ?? 'None set' }}{{ $lead->isFollowUpDue() ? ' — overdue' : '' }}@if($lead->follow_up_note)<div class="text-muted fw-normal">{{ $lead->follow_up_note }}</div>@endif</dd>
            @if($lead->stage?->kind === 'lost' && $lead->lost_reason)<dt>Lost because</dt><dd>{{ $lead->lost_reason }}</dd>@endif
          </dl>
          @if($lead->message)<div class="ld-message mt-3">{{ Str::limit($lead->message, 400) }}</div>@endif
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><a class="btn btn-primary" href="{{ route('leads.show', $lead) }}">Open lead <i class="ri-arrow-right-s-line"></i></a></div>
      </div></div></div>
    @endforeach
@endif
</div></div>
</div></div></div>

@if($view === 'board')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const board = document.getElementById('lead-board');
  const modalEl = document.getElementById('lostReason');
  let dragged = null, from = null;

  const toast = (text) => {
    const el = Object.assign(document.createElement('div'), { className: 'alert alert-danger shadow position-fixed bottom-0 end-0 m-3', role: 'alert', textContent: text });
    el.style.zIndex = 2000;
    document.body.append(el);
    setTimeout(() => el.remove(), 5000);
  };
  const count = (col, delta) => { const el = col.querySelector('[data-count]'); el.textContent = Math.max(0, +el.textContent + delta); };
  // Empty columns say so; a column that gets a card loses the message.
  const refreshEmpty = (body) => {
    const empty = body.querySelector('[data-empty]');
    const hasCards = body.querySelector('.lead-card:not(.dragging)') || body.contains(dragged);
    if (hasCards && empty) empty.remove();
    if (!hasCards && !empty) {
      const el = Object.assign(document.createElement('div'), { className: 'lead-empty', textContent: 'Drop a lead here' });
      el.dataset.empty = '';
      body.prepend(el);
    }
  };

  async function move(card, col, reason) {
    const res = await fetch(card.dataset.url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': board.dataset.csrf },
      body: JSON.stringify({ lead_stage_id: col.dataset.stage, lost_reason: reason || null }),
    });
    if (res.ok) return true;
    const body = await res.json().catch(() => ({}));
    if (body.needs_reason) return 'reason';
    toast(body.message || 'The lead could not be moved. Reload the page and try again.');
    return false;
  }

  function askReason() {
    return new Promise(resolve => {
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      const form = modalEl.querySelector('form');
      const input = form.querySelector('[name=lost_reason]');
      input.value = '';
      let answered = false;
      modalEl.querySelectorAll('[data-reason]').forEach(b => b.onclick = () => { input.value = b.dataset.reason; input.focus(); });
      form.onsubmit = e => { e.preventDefault(); if (!input.value.trim()) return; answered = true; modal.hide(); resolve(input.value.trim()); };
      modalEl.addEventListener('hidden.bs.modal', () => { if (!answered) resolve(null); }, { once: true });
      modal.show();
      setTimeout(() => input.focus(), 300);
    });
  }

  /*
   * Trello-style dragging: the whole column (header, cards, empty space below,
   * and the gap next to it) accepts the card, and a dashed slot shows where it
   * will land. The board scrolls sideways near its edges, a long column up/down.
   */
  const cols = [...board.querySelectorAll('.lead-col')];
  const slot = Object.assign(document.createElement('div'), { className: 'lead-slot' });
  let origin = null; // where the card was, to put it back if the move fails

  const columnAt = (x) => cols.find(col => { const r = col.getBoundingClientRect(); return x >= r.left - 6 && x <= r.right + 6; });
  const cardAfter = (body, y) => [...body.querySelectorAll('.lead-card:not(.dragging)')].find(card => { const r = card.getBoundingClientRect(); return y < r.top + r.height / 2; });
  const clearHighlight = () => cols.forEach(col => col.classList.remove('drop-over'));

  function autoScroll(e, body) {
    const b = board.getBoundingClientRect();
    if (e.clientX < b.left + 70) board.scrollLeft -= 18;
    else if (e.clientX > b.right - 70) board.scrollLeft += 18;
    const r = body.getBoundingClientRect();
    if (e.clientY < r.top + 50) body.scrollTop -= 14;
    else if (e.clientY > r.bottom - 50) body.scrollTop += 14;
  }

  board.addEventListener('dragstart', e => {
    const card = e.target.closest('.lead-card');
    if (!card) return;
    dragged = card; from = card.closest('.lead-col');
    origin = { body: card.parentElement, next: card.nextElementSibling };
    slot.style.height = card.offsetHeight + 'px';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', card.dataset.lead);
    setTimeout(() => card.classList.add('dragging')); // after the browser has taken the drag image of the card
  });

  board.addEventListener('dragover', e => {
    if (!dragged) return;
    const col = e.target.closest('.lead-col') || columnAt(e.clientX);
    if (!col) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (!col.classList.contains('drop-over')) { clearHighlight(); col.classList.add('drop-over'); }
    const body = col.querySelector('[data-drop]');
    const after = cardAfter(body, e.clientY);
    if (after) { if (slot.nextElementSibling !== after) body.insertBefore(slot, after); }
    else {
      const tail = body.querySelector(':scope > .see-all'); // keep "See all" links last
      if (slot.nextElementSibling !== tail || slot.parentElement !== body) body.insertBefore(slot, tail);
    }
    autoScroll(e, body);
  });

  board.addEventListener('dragleave', e => {
    if (!board.contains(e.relatedTarget)) { clearHighlight(); slot.remove(); }
  });

  board.addEventListener('dragend', () => {
    dragged?.classList.remove('dragging');
    clearHighlight();
    slot.remove();
    dragged = null;
  });

  board.addEventListener('drop', async e => {
    if (!dragged) return;
    const col = e.target.closest('.lead-col') || columnAt(e.clientX);
    if (!col || !slot.parentElement) return;
    e.preventDefault();
    const card = dragged, to = col, body = slot.parentElement;
    body.insertBefore(card, slot);
    slot.remove();
    card.classList.remove('dragging');
    clearHighlight();
    refreshEmpty(body);
    refreshEmpty(origin.body);
    if (to === from) return; // same column: only the order on screen changes

    count(to, 1); count(from, -1); // optimistic
    let result = await move(card, to);
    if (result === 'reason') {
      const reason = await askReason();
      result = reason ? await move(card, to, reason) : false;
    }
    if (result !== true) {
      origin.body.insertBefore(card, origin.next && origin.body.contains(origin.next) ? origin.next : null);
      count(to, -1); count(from, 1);
      refreshEmpty(body); refreshEmpty(origin.body);
    }
  });
});
</script>
@endif
@include('admin.includes.footer')
