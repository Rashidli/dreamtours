@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-leads.css') }}?v={{ @filemtime(public_path('assets/css/admin-leads.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $canEdit = auth()->user()->can('edit-leads');
  $phone = $lead->phone ?: $lead->whatsapp;
  $wa = preg_replace('/\D/', '', (string) ($lead->whatsapp ?: $lead->phone));
  $open = $stages->where('kind', 'open')->merge($stages->where('kind', 'won'));
  $lostStage = $stages->firstWhere('kind', 'lost');
  $spamStage = $stages->firstWhere('kind', 'spam');
  $stepIndex = $open->values()->search(fn ($s) => $s->id === $lead->lead_stage_id);
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <a class="btn btn-light ld-back" href="{{ route('leads.index') }}" title="Back to leads" aria-label="Back to leads"><i class="ri-arrow-left-line"></i></a>
                            <h4 class="mb-0">Lead {{ $lead->reference }}</h4>
                        </div>
                        <div class="page-title-right d-none d-md-block">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                                <li class="breadcrumb-item active">{{ $lead->reference }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

{{-- Header: who, where it stands, and the actions staff need first. --}}
<div class="card ld-head-card"><div class="card-body">
  <div class="ld-head">
    <div class="ld-head-main">
      <h3 class="ld-head-name">{{ $lead->full_name }} <span class="badge bg-{{ $lead->stage->color }}">{{ $lead->stage->name }}</span></h3>
      <div class="ld-head-meta">{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour) · {{ $lead->tour->name }}@endif · received {{ $lead->created_at->timezone($tz)->format('M j, Y g:i A') }} ({{ $lead->created_at->diffForHumans() }})</div>
      <div class="ld-head-facts">
        @if($lead->phone)<span><i class="ri-phone-line"></i> {{ $lead->phone }}</span>@endif
        @if($lead->email)<span class="text-break"><i class="ri-mail-line"></i> {{ $lead->email }}</span>@endif
        <span><i class="ri-user-line"></i> {{ $lead->assignedUser?->name ?? 'Unassigned' }}</span>
        @if($lead->next_follow_up_at)<span class="{{ $lead->isFollowUpDue() ? 'is-due' : '' }}" title="{{ $lead->follow_up_note }}"><i class="ri-alarm-line"></i> Follow up {{ $lead->next_follow_up_at->timezone($tz)->format('D, M j, g:i A') }}{{ $lead->isFollowUpDue() ? ' (overdue)' : '' }}</span>@elseif($canEdit && !$lead->stage->isClosed())<span><i class="ri-alarm-line"></i> <a href="#follow-up">Set a follow-up</a></span>@endif
      </div>
      @if($lead->stage->kind === 'lost' && $lead->lost_reason)<div class="ld-head-lost"><i class="ri-close-circle-line"></i> Lost: {{ $lead->lost_reason }}</div>@endif
    </div>
    <div class="ld-head-actions">
      @if($phone)<a class="btn btn-light" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><i class="ri-phone-line"></i> Call</a>@endif
      @if(strlen($wa) >= 7)<a class="btn btn-light" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i> WhatsApp</a>@endif
      @if($lead->email)<a class="btn btn-light" href="mailto:{{ $lead->email }}"><i class="ri-mail-line"></i> Email</a>@endif
      @if($lead->booking)
        <a class="btn btn-primary" href="{{ route('bookings.show', $lead->booking) }}"><i class="ri-calendar-check-line"></i> Booking {{ $lead->booking->reference }}</a>
      @elseif($canEdit)
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#convertLead"><i class="ri-arrow-right-circle-line"></i> Convert to booking</button>
      @endif
    </div>
  </div>

  @if($canEdit)
  <div class="stage-steps">
    <span class="ld-steps-label">Stage</span>
    <div class="ld-path">
    @foreach($open as $stage)
      <form method="post" action="{{ route('leads.stage', $lead) }}">@csrf @method('PATCH')<input type="hidden" name="lead_stage_id" value="{{ $stage->id }}">
        <button class="ld-step {{ $lead->lead_stage_id === $stage->id ? 'is-current' : ($stepIndex !== false && $loop->index < $stepIndex ? 'is-done' : '') }}" @disabled($lead->lead_stage_id === $stage->id) title="{{ $lead->lead_stage_id === $stage->id ? 'Current stage' : 'Move to '.$stage->name }}">@if($stepIndex !== false && $loop->index < $stepIndex)<i class="ri-check-line"></i>@else<span class="stage-dot" style="background:{{ \App\Models\LeadStage::HEX[$stage->color] ?? '#6c757d' }}"></span>@endif {{ $stage->name }}</button></form>
    @endforeach
    </div>
    <div class="ld-close-actions">
    @if($lostStage && $lead->lead_stage_id !== $lostStage->id)<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#markLost"><i class="ri-close-circle-line"></i> Mark as {{ Str::lower($lostStage->name) }}</button>@endif
    @if($spamStage && $lead->lead_stage_id !== $spamStage->id)<form method="post" action="{{ route('leads.stage', $lead) }}" onsubmit="return confirm('Mark this lead as spam? It leaves the open pipeline.')">@csrf @method('PATCH')<input type="hidden" name="lead_stage_id" value="{{ $spamStage->id }}"><button class="btn btn-light"><i class="ri-spam-2-line"></i> {{ $spamStage->name }}</button></form>@endif
    </div>
  </div>
  @endif
</div></div>

<div class="row">
  {{-- Side column (right on wide screens): the facts, owner and reminder. --}}
  <div class="col-lg-4 order-lg-2 ld-side">
    <div class="card ld-card"><div class="card-body">
      <h6 class="ld-card-title">Request</h6>
      <dl class="ld-dl mb-0">
        <dt>Type</dt><dd>{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}</dd>
        <dt>Tour</dt><dd>{{ $lead->tour?->name ?? '—' }}</dd>
        <dt>Departure</dt><dd>{{ $lead->departure?->departs_at?->format('D, M j, Y g:i A') ?? '—' }}</dd>
        <dt>Guests</dt><dd>{{ $lead->guests ?? '—' }}</dd>
        <dt>Pick-up</dt><dd>{{ $lead->pickupPoint?->name ?? '—' }}</dd>
        @if($lead->requestBooking && !$lead->booking_id)<dt>Original request</dt><dd><a href="{{ route('bookings.show', $lead->requestBooking) }}">{{ $lead->requestBooking->reference }}</a></dd>@endif
        @if($lead->visaEnquiry)<dt>Visa enquiry</dt><dd><a href="{{ route('visa-enquiries.show', $lead->visaEnquiry) }}">{{ $lead->visaEnquiry->reference }}</a></dd>@endif
      </dl>
      @if($lead->message)<div class="ld-message mt-3">{{ $lead->message }}</div>@endif
    </div></div>

    <div class="card ld-card"><div class="card-body">
      <h6 class="ld-card-title">Contact @if($canEdit)<a href="#" data-bs-toggle="modal" data-bs-target="#editLead"><i class="ri-pencil-line"></i> Edit</a>@endif</h6>
      <dl class="ld-dl mb-0">
        <dt>Email</dt><dd class="text-break">@if($lead->email)<a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>@else — @endif</dd>
        <dt>Phone</dt><dd>@if($lead->phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}">{{ $lead->phone }}</a>@else — @endif</dd>
        <dt>WhatsApp</dt><dd>@if($lead->whatsapp && strlen(preg_replace('/\D/', '', $lead->whatsapp)) >= 7)<a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->whatsapp) }}" target="_blank" rel="noopener">{{ $lead->whatsapp }}</a>@else{{ $lead->whatsapp ?: '—' }}@endif</dd>
        <dt>Customer</dt><dd>@if($lead->customer)@can('list-customers')<a href="{{ route('customers.show', $lead->customer) }}">{{ $lead->customer->full_name }} →</a>@else{{ $lead->customer->full_name }}@endcan @else — @endif</dd>
      </dl>
    </div></div>

    <div class="card ld-card"><div class="card-body">
      <h6 class="ld-card-title">Source</h6>
      <dl class="ld-dl mb-0">
        <dt>Source</dt><dd>{{ \App\Models\Lead::SOURCES[$lead->source] ?? $lead->source }}</dd>
        @foreach(['utm_source' => 'UTM source', 'utm_medium' => 'Medium', 'utm_campaign' => 'Campaign', 'utm_term' => 'Keyword', 'utm_content' => 'Ad'] as $field => $label)
          @if($lead->{$field})<dt>{{ $label }}</dt><dd class="text-break">{{ $lead->{$field} }}</dd>@endif
        @endforeach
        @if($lead->landing_page)<dt>Landing page</dt><dd class="text-break"><a href="{{ $lead->landing_page }}" target="_blank" rel="noopener">{{ str(parse_url($lead->landing_page, PHP_URL_PATH) ?: '/')->limit(40) }}</a></dd>@endif
        @if($lead->referrer)<dt>Came from</dt><dd class="text-break">{{ parse_url($lead->referrer, PHP_URL_HOST) ?: $lead->referrer }}</dd>@endif
      </dl>
    </div></div>

    @if($canEdit)
    <div class="card ld-card"><div class="card-body">
      <h6 class="ld-card-title"><label class="m-0" for="ld-assignee">Assigned to</label></h6>
      <form method="post" action="{{ route('leads.assign', $lead) }}">@csrf @method('PATCH')
        <select class="form-select" id="ld-assignee" name="assigned_user_id" onchange="this.form.submit()" aria-label="Assigned to"><option value="">Nobody yet</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($lead->assigned_user_id === $user->id)>{{ $user->name }}{{ $user->id === auth()->id() ? ' (me)' : '' }}</option>@endforeach</select>
      </form>
      @if(!$lead->assigned_user_id)<form method="post" action="{{ route('leads.assign', $lead) }}" class="mt-2">@csrf @method('PATCH')<input type="hidden" name="assigned_user_id" value="{{ auth()->id() }}"><button class="btn btn-light w-100"><i class="ri-user-received-line"></i> Assign to me</button></form>@endif
      <div class="form-text">Changes are saved as soon as you pick someone.</div>
    </div></div>

    <div class="card ld-card" id="follow-up"><div class="card-body">
      <h6 class="ld-card-title">Follow-up reminder</h6>
      @if($lead->next_follow_up_at)
        <div class="ld-follow-now {{ $lead->isFollowUpDue() ? 'alert-danger' : 'alert-info' }}"><i class="ri-alarm-line"></i><div><span class="fw-semibold">{{ $lead->next_follow_up_at->timezone($tz)->format('D, M j, g:i A') }}{{ $lead->isFollowUpDue() ? ' — overdue' : '' }}</span>@if($lead->follow_up_note)<br>{{ $lead->follow_up_note }}@endif</div></div>
      @endif
      <form method="post" action="{{ route('leads.follow-up', $lead) }}">@csrf @method('PATCH')
        <div class="ld-presets">@foreach(['today' => 'Today 5 PM', 'tomorrow' => 'Tomorrow', '3days' => 'In 3 days', 'week' => 'Next week'] as $value => $label)<button class="btn btn-light" name="when" value="{{ $value }}">{{ $label }}</button>@endforeach</div>
        <div class="input-group mb-2"><input class="form-control" type="datetime-local" name="next_follow_up_at" aria-label="Custom date and time"><button class="btn btn-light" name="when" value="custom">Set</button></div>
        <input class="form-control mb-2" name="follow_up_note" maxlength="255" placeholder="What to do (optional)" value="{{ $lead->follow_up_note }}" aria-label="What to do">
        @if($lead->next_follow_up_at)<button class="btn btn-link text-danger p-0" name="when" value="clear">Clear reminder</button>@endif
      </form>
    </div></div>

    <div class="card ld-card"><div class="card-body">
      <h6 class="ld-card-title">Tags</h6>
      <form method="post" action="{{ route('leads.tags', $lead) }}">@csrf @method('PUT')
        <div class="ld-tags">@forelse($tags as $tag)<label class="ld-tag"><input type="checkbox" class="form-check-input" name="tag_ids[]" value="{{ $tag->id }}" @checked($lead->tags->contains($tag))> {{ $tag->name }}</label>@empty<span class="text-muted">No tags yet.</span>@endforelse</div>
        @if($tags->isNotEmpty())<div><button class="btn btn-light mt-3">Save tags</button></div>@endif
      </form>
    </div></div>
    @endif
  </div>

  {{-- Main column (left on wide screens): what happened and what to do next. --}}
  <div class="col-lg-8 order-lg-1">
    @if($canEdit)
    <div class="card ld-card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-chat-1-line"></i></span><div><h5>Log a contact or note</h5><p>Record every call or message so the next person knows where things stand.</p></div></div>
      <form method="post" action="{{ route('leads.note', $lead) }}" id="note-form">@csrf
        <div class="mb-3 kind-pick" role="radiogroup" aria-label="Type">
          @foreach(\App\Models\LeadNote::KINDS as $kind => [$label, $icon])
            <input type="radio" class="btn-check" name="kind" id="kind-{{ $kind }}" value="{{ $kind }}" @checked(old('kind', 'call') === $kind)><label class="btn btn-light" for="kind-{{ $kind }}"><i class="{{ $icon }}"></i> {{ $label }}</label>
          @endforeach
        </div>
        <div class="mb-3 ld-outcomes" id="outcomes">@foreach(\App\Models\LeadNote::OUTCOMES as $value => $label)<label><input type="radio" class="form-check-input" name="outcome" value="{{ $value }}" @checked(old('outcome') === $value)> {{ $label }}</label>@endforeach</div>
        <textarea class="form-control mb-3" name="body" rows="3" placeholder="What did you talk about? Next steps?" aria-label="Note">{{ old('body') }}</textarea>
        <div class="ld-note-foot">
          <span class="text-muted">Calls, WhatsApp, email and SMS count as contact attempts{{ in_array($lead->stage->key, ['new', 'assigned'], true) ? ' and move the lead to “Contacted”' : '' }}.</span>
          <button class="btn btn-primary">Save</button>
        </div>
      </form>
    </div></div>
    @endif

    <div class="card ld-card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-chat-history-line"></i></span><div><h5>Timeline</h5><p>{{ $lead->notes->count() }} {{ str('entry')->plural($lead->notes->count()) }} · {{ $lead->contact_attempts }} contact {{ str('attempt')->plural($lead->contact_attempts) }}</p></div></div>
      @forelse($lead->notes as $note)
        <div class="timeline-item">
          <span class="timeline-icon"><i class="{{ \App\Models\LeadNote::KINDS[$note->kind][1] ?? 'ri-sticky-note-line' }}"></i></span>
          <div class="ld-tl-head"><span class="fw-semibold">{{ \App\Models\LeadNote::KINDS[$note->kind][0] ?? $note->kind }}</span>@if($note->outcome) · <span class="{{ $note->outcome === 'reached' ? 'text-success fw-semibold' : 'text-muted' }}">{{ \App\Models\LeadNote::OUTCOMES[$note->outcome] ?? $note->outcome }}</span>@endif
            <span class="text-muted">· {{ $note->user?->name ?? 'System' }} · <span title="{{ $note->created_at->timezone($tz)->format('M j, Y g:i A') }}">{{ $note->created_at->diffForHumans() }}</span></span></div>
          @if($note->body)<div class="ld-tl-body">{{ $note->body }}</div>@endif
        </div>
      @empty
        <div class="ld-empty"><i class="ri-chat-history-line"></i><div class="fw-semibold">No calls or notes yet</div>{{ $canEdit ? 'Log the first contact above.' : 'Calls and notes will appear here.' }}</div>
      @endforelse
    </div></div>

    <div class="card ld-card"><div class="card-body">
      <h6 class="ld-card-title">History</h6>
      <ul class="ld-history">
      @forelse($activity as $log)
        <li><time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->timezone($tz)->format('M j, g:i A') }}</time><span><span class="fw-semibold">{{ $log->actor_name }}</span> · {{ str($log->action)->replace('_', ' ') }}@if(isset($log->changes['stage'])) — {{ $log->changes['stage'][0] ?? '—' }} → {{ $log->changes['stage'][1] }}@endif</span></li>
      @empty
        <li class="text-muted">Nothing yet.</li>
      @endforelse
      </ul>
      @can('delete-leads')<div class="ld-danger-zone"><span>Deleting keeps the original request and any booking.</span><form method="post" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete lead {{ $lead->reference }}? The original request and any booking are kept.')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="ri-delete-bin-line"></i> Delete lead</button></form></div>@endcan
    </div></div>
  </div>
</div>

@if($canEdit)
{{-- Convert: everything known is copied; staff only pick the date and guests. --}}
<div class="modal fade" id="convertLead" tabindex="-1" aria-labelledby="convertLeadTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('leads.convert', $lead) }}">@csrf
  <div class="modal-header"><h5 class="modal-title" id="convertLeadTitle">Convert to booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <p class="text-muted">{{ $lead->full_name }}'s contact details{{ $lead->message ? ', message' : '' }} and pick-up are copied to the booking. You can add passengers and adjust the price on the booking page.</p>
    <label class="form-label fw-semibold" for="convert-departure">Departure</label>
    <select class="form-select mb-3" id="convert-departure" name="tour_departure_id" required>
      <option value="">Choose a date…</option>
      @foreach($departures->groupBy(fn ($d) => $d->tour?->name) as $tourName => $dates)
        <optgroup label="{{ $tourName }}">@foreach($dates as $departure)<option value="{{ $departure->id }}" @selected(old('tour_departure_id', $lead->tour_departure_id) == $departure->id)>{{ $departure->departs_at->format('D, M j, Y g:i A') }} — {{ $departure->available_seats }} seats left · {{ money($departure->from_price, $departure->currency) }}</option>@endforeach</optgroup>
      @endforeach
    </select>
    <label class="form-label fw-semibold" for="convert-guests">Guests</label>
    <input class="form-control" id="convert-guests" type="number" min="1" max="99" name="guests" value="{{ old('guests', $lead->guests ?: 1) }}" required>
    @include('admin.includes.capacity_override', ['id' => 'cap-convert'])
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create booking</button></div>
</form></div></div>

<div class="modal fade" id="markLost" tabindex="-1" aria-labelledby="markLostTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('leads.stage', $lead) }}">@csrf @method('PATCH')
  <input type="hidden" name="lead_stage_id" value="{{ $lostStage?->id }}">
  <div class="modal-header"><h5 class="modal-title" id="markLostTitle">Why was this lead lost?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <p class="text-muted mb-2">Pick a common reason or type your own.</p>
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['Price too high', 'Chose another company', 'Dates did not work', 'No reply', 'Tour sold out', 'Just asking'] as $reason)<button type="button" class="btn btn-light ld-reason" onclick="this.closest('form').lost_reason.value = this.textContent">{{ $reason }}</button>@endforeach</div>
    <label class="form-label fw-semibold" for="mark-lost-reason">Reason</label>
    <input class="form-control" id="mark-lost-reason" name="lost_reason" maxlength="255" placeholder="e.g. Booked with a competitor" required>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Mark as lost</button></div>
</form></div></div>

<div class="modal fade" id="editLead" tabindex="-1" aria-labelledby="editLeadTitle" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="post" action="{{ route('leads.update', $lead) }}">@csrf @method('PUT')
  <div class="modal-header"><h5 class="modal-title" id="editLeadTitle">Edit lead details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">@include('admin.leads._fields')</div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
</form></div></div>
<script>
  // Outcomes only make sense for contact attempts, not for plain notes.
  document.querySelectorAll('#note-form [name=kind]').forEach(r => r.addEventListener('change', () => { document.getElementById('outcomes').hidden = document.getElementById('kind-note').checked; }));
  document.getElementById('outcomes').hidden = document.getElementById('kind-note')?.checked;
  @if($errors->has('guests') || $errors->has('tour_departure_id')) document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('convertLead')).show()); @endif
  @if($errors->has('lost_reason')) document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('markLost')).show()); @endif
</script>
@endif
</div></div></div>@include('admin.includes.footer')
