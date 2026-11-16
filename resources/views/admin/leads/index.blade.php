@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.leads._styles')
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $filters = collect(request()->except(['page', 'view']))->filter(fn ($v) => $v !== null && $v !== '');
  $moreFilters = $filters->keys()->intersect(['type', 'campaign', 'tour', 'from', 'to', 'trip_from', 'trip_to', 'contacted_from', 'contacted_to'])->isNotEmpty();
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Leads</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Leads</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<div class="card crm-filters"><div class="card-body pb-2">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="btn-group" role="group" aria-label="View">
      <a class="btn btn-sm {{ $view === 'board' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ request()->fullUrlWithQuery(['view' => null, 'page' => null]) }}"><i class="ri-layout-column-line align-middle"></i> Board</a>
      <a class="btn btn-sm {{ $view === 'list' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"><i class="ri-list-check align-middle"></i> List</a>
    </div>
    <div class="d-flex flex-wrap gap-2">
      @if($due)<a class="btn btn-sm {{ request()->boolean('due') ? 'btn-danger' : 'btn-outline-danger' }}" href="{{ request()->fullUrlWithQuery(['due' => request()->boolean('due') ? null : 1]) }}"><i class="ri-alarm-warning-line align-middle"></i> {{ $due }} follow-up{{ $due === 1 ? '' : 's' }} due</a>@endif
      @can('manage-lead-stages')<a class="btn btn-sm btn-light" href="{{ route('lead-stages.index') }}"><i class="ri-settings-3-line align-middle"></i> Stages</a>@endcan
      @can('export-leads')<div class="btn-group"><a class="btn btn-sm btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'xlsx', 'page' => null]) }}" title="Download the leads matching these filters"><i class="ri-file-excel-2-line"></i> Excel</a><a class="btn btn-sm btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}">CSV</a></div>@endcan
      @can('create-leads')<a class="btn btn-sm btn-primary" href="{{ route('leads.create') }}"><i class="ri-add-line align-middle"></i> New lead</a>@endcan
    </div>
  </div>

  <form class="row g-2" id="lead-filters">
    @if($view === 'list')<input type="hidden" name="view" value="list">@endif
    @if(request()->boolean('due'))<input type="hidden" name="due" value="1">@endif
    <div class="col-md-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Name, email, phone or L-reference"></div>
    <div class="col-md-2"><select class="form-select" name="assigned" aria-label="Assigned to" onchange="this.form.submit()">
      <option value="">Everyone</option><option value="me" @selected(request('assigned') === 'me')>Assigned to me</option><option value="none" @selected(request('assigned') === 'none')>Unassigned</option>
      @foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned') == $user->id)>{{ $user->name }}</option>@endforeach
    </select></div>
    <div class="col-md-2"><select class="form-select" name="source" aria-label="Source" onchange="this.form.submit()"><option value="">All sources</option>@foreach(\App\Models\Lead::SOURCES as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach</select></div>
    @if($view === 'list')<div class="col-md-2"><select class="form-select" name="stage" aria-label="Stage" onchange="this.form.submit()"><option value="">All stages</option>@foreach($stages as $stage)<option value="{{ $stage->id }}" @selected(request('stage') == $stage->id)>{{ $stage->name }}</option>@endforeach</select></div>@endif
    <div class="col-md-auto d-flex gap-2"><button class="btn btn-sm btn-outline-primary">Filter</button>@if($filters->isNotEmpty())<a class="btn btn-sm btn-light" href="{{ route('leads.index', $view === 'list' ? ['view' => 'list'] : []) }}">Reset</a>@endif
      <a class="btn btn-sm btn-link" data-bs-toggle="collapse" href="#moreLeadFilters" role="button" aria-expanded="{{ $moreFilters ? 'true' : 'false' }}">More filters</a></div>
    <div class="collapse {{ $moreFilters ? 'show' : '' }}" id="moreLeadFilters"><div class="row g-2 pt-1">
      <div class="col-md-2"><label class="form-label">Request type</label><select class="form-select" name="type"><option value="">Any</option>@foreach(\App\Models\Lead::TYPES as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Campaign</label><select class="form-select" name="campaign"><option value="">Any</option>@foreach($campaigns as $campaign)<option @selected(request('campaign') === $campaign)>{{ $campaign }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Tour</label><select class="form-select" name="tour"><option value="">Any</option>@foreach($tours as $tour)<option value="{{ $tour->id }}" @selected(request('tour') == $tour->id)>{{ $tour->name }}</option>@endforeach</select></div>
      <div class="col-md-3"><label class="form-label">Received between</label><div class="input-group"><input class="form-control" type="date" name="from" value="{{ request('from') }}" aria-label="Received from"><input class="form-control" type="date" name="to" value="{{ request('to') }}" aria-label="Received to"></div></div>
      <div class="col-md-3"><label class="form-label">Trip date between</label><div class="input-group"><input class="form-control" type="date" name="trip_from" value="{{ request('trip_from') }}" aria-label="Trip from"><input class="form-control" type="date" name="trip_to" value="{{ request('trip_to') }}" aria-label="Trip to"></div></div>
      <div class="col-md-3"><label class="form-label">Last contact between</label><div class="input-group"><input class="form-control" type="date" name="contacted_from" value="{{ request('contacted_from') }}" aria-label="Contacted from"><input class="form-control" type="date" name="contacted_to" value="{{ request('contacted_to') }}" aria-label="Contacted to"></div></div>
    </div></div>
  </form>
</div></div>

@if($view === 'board')
  <div class="lead-board" id="lead-board" data-csrf="{{ csrf_token() }}">
    @foreach($stages as $stage)
      <div class="lead-col {{ $stage->isClosed() ? 'closed' : '' }}" data-stage="{{ $stage->id }}" data-kind="{{ $stage->kind }}" data-name="{{ $stage->name }}">
        <div class="lead-col-head">
          <span class="stage-dot" style="background:{{ \App\Models\LeadStage::HEX[$stage->color] ?? '#6c757d' }}"></span>
          <span class="flex-grow-1">{{ $stage->name }}</span>
          <span class="badge bg-light text-body" data-count>{{ $counts[$stage->id] ?? 0 }}</span>
        </div>
        <div class="lead-col-body" data-drop>
          @forelse($board[$stage->id] as $lead)
            <a class="lead-card" href="{{ route('leads.show', $lead) }}" draggable="true" data-lead="{{ $lead->id }}" data-url="{{ route('leads.stage', $lead) }}">
              <div class="d-flex justify-content-between gap-2">
                <span class="lead-name text-truncate">{{ $lead->full_name }}</span>
                <span class="lead-meta text-nowrap" title="{{ $lead->created_at->timezone($tz)->format('M j, Y g:i A') }}">{{ $lead->created_at->diffForHumans(null, true, true) }}</span>
              </div>
              <div class="lead-meta text-truncate">{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour) · {{ $lead->tour->name }}@endif</div>
              @if($lead->departure || $lead->guests)<div class="lead-meta">@if($lead->departure)<i class="ri-calendar-line"></i> {{ $lead->departure->departs_at->format('M j') }}@endif @if($lead->guests)<i class="ri-user-line ms-1"></i> {{ $lead->guests }}@endif</div>@endif
              @if($lead->stage?->kind === 'lost' && $lead->lost_reason)<div class="lead-meta text-truncate fst-italic">{{ $lead->lost_reason }}</div>@endif
              <div class="d-flex align-items-center gap-1 mt-2">
                @if($lead->assignedUser)<span class="lead-avatar" title="Assigned to {{ $lead->assignedUser->name }}">{{ \App\Models\Lead::initials($lead->assignedUser->name) }}</span>@else<span class="lead-avatar none" title="Unassigned">?</span>@endif
                <span class="lead-chip text-truncate" style="max-width:120px" title="{{ $lead->sourceLabel() }}">{{ $lead->utm_campaign ?: (\App\Models\Lead::SOURCES[$lead->source] ?? $lead->source) }}</span>
                @if($lead->contact_attempts)<span class="lead-meta ms-auto" title="{{ $lead->contact_attempts }} contact attempts"><i class="ri-phone-line"></i> {{ $lead->contact_attempts }}</span>@endif
                @if($lead->next_follow_up_at && !$stage->isClosed())<span class="lead-meta {{ $lead->isFollowUpDue() ? 'lead-due' : '' }} {{ $lead->contact_attempts ? '' : 'ms-auto' }}" title="Follow up {{ $lead->next_follow_up_at->timezone($tz)->format('D, M j g:i A') }}"><i class="ri-alarm-line"></i> {{ $lead->next_follow_up_at->timezone($tz)->format('M j') }}</span>@endif
              </div>
            </a>
          @empty
            <div class="lead-empty" data-empty>{{ $stage->isClosed() ? 'Nothing here yet' : 'Drop a lead here' }}</div>
          @endforelse
          @if($stage->isClosed() && ($counts[$stage->id] ?? 0) > $board[$stage->id]->count())
            <a class="small d-block text-center py-1 see-all" href="{{ request()->fullUrlWithQuery(['view' => 'list', 'stage' => $stage->id]) }}">See all {{ $counts[$stage->id] }}</a>
          @endif
        </div>
      </div>
    @endforeach
  </div>
  <p class="text-muted mt-1"><i class="ri-drag-move-line"></i> Drag a card to another column to change its stage, or open it to log a call, set a reminder or convert it to a booking.</p>

  {{-- Lost needs a reason: asked here instead of a browser prompt. --}}
  <div class="modal fade" id="lostReason" tabindex="-1" aria-labelledby="lostReasonTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="lostReasonTitle">Why was this lead lost?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="d-flex flex-wrap gap-1 mb-2">@foreach(['Price too high', 'Chose another company', 'Dates did not work', 'No reply', 'Tour sold out', 'Just asking'] as $reason)<button type="button" class="btn btn-sm btn-light" data-reason="{{ $reason }}">{{ $reason }}</button>@endforeach</div>
      <input class="form-control" name="lost_reason" maxlength="255" placeholder="Reason" required>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Mark as lost</button></div>
  </form></div></div>
@else
  <div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-hover crm-table mb-0">
      <thead><tr><th>Lead</th><th>Request</th><th>Stage</th><th>Assigned</th><th>Source</th><th>Last contact</th><th>Follow-up</th><th></th></tr></thead>
      <tbody>
      @forelse($leads as $lead)
        <tr>
          <td><span class="fw-semibold">{{ $lead->full_name }}</span><br><span class="text-muted">{{ $lead->reference }} · {{ $lead->created_at->timezone($tz)->format('M j, Y') }}</span></td>
          <td>{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour)<br><span class="text-muted">{{ $lead->tour->name }}@if($lead->departure) · {{ $lead->departure->departs_at->format('M j') }}@endif @if($lead->guests) · {{ $lead->guests }} {{ str('guest')->plural($lead->guests) }}@endif</span>@endif</td>
          <td><span class="badge bg-{{ $lead->stage?->color }}">{{ $lead->stage?->name }}</span></td>
          <td>{{ $lead->assignedUser?->name ?? '—' }}</td>
          <td class="text-muted">{{ $lead->sourceLabel() }}</td>
          <td>{{ $lead->last_contacted_at?->timezone($tz)->format('M j') ?? '—' }}@if($lead->contact_attempts) <span class="text-muted">({{ $lead->contact_attempts }})</span>@endif</td>
          <td class="{{ $lead->isFollowUpDue() ? 'text-danger fw-semibold' : '' }}">{{ $lead->next_follow_up_at?->timezone($tz)->format('M j, g:i A') ?? '—' }}</td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('leads.show', $lead) }}">Details</a></td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-muted">No leads match these filters.</td></tr>
      @endforelse
      </tbody>
    </table></div>
    {{ $leads->links('admin.vendor.pagination.bootstrap-5') }}
  </div></div>
@endif
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
