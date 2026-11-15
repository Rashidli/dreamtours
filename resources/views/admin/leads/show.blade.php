@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.leads._styles')
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $canEdit = auth()->user()->can('edit-leads');
  $phone = $lead->phone ?: $lead->whatsapp;
  $wa = preg_replace('/\D/', '', (string) ($lead->whatsapp ?: $lead->phone));
  $open = $stages->where('kind', 'open')->merge($stages->where('kind', 'won'));
  $lostStage = $stages->firstWhere('kind', 'lost');
  $spamStage = $stages->firstWhere('kind', 'spam');
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Lead {{ $lead->reference }}</h4>
                        <div class="page-title-right">
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
<div class="card"><div class="card-body">
  <div class="d-flex flex-wrap gap-3 justify-content-between align-items-start">
    <div>
      <h3 class="mb-1">{{ $lead->full_name }} <span class="badge bg-{{ $lead->stage->color }} align-middle fs-6">{{ $lead->stage->name }}</span></h3>
      <div class="text-muted">{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}@if($lead->tour) · {{ $lead->tour->name }}@endif · received {{ $lead->created_at->timezone($tz)->format('M j, Y g:i A') }} ({{ $lead->created_at->diffForHumans() }})</div>
      @if($lead->stage->kind === 'lost' && $lead->lost_reason)<div class="text-danger small mt-1"><i class="ri-close-circle-line"></i> Lost: {{ $lead->lost_reason }}</div>@endif
    </div>
    <div class="d-flex flex-wrap gap-2">
      @if($phone)<a class="btn btn-outline-secondary" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><i class="ri-phone-line"></i> Call</a>@endif
      @if(strlen($wa) >= 7)<a class="btn btn-outline-success" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i> WhatsApp</a>@endif
      @if($lead->email)<a class="btn btn-outline-secondary" href="mailto:{{ $lead->email }}"><i class="ri-mail-line"></i> Email</a>@endif
      @if($lead->booking)
        <a class="btn btn-success" href="{{ route('bookings.show', $lead->booking) }}"><i class="ri-calendar-check-line"></i> Booking {{ $lead->booking->reference }}</a>
      @elseif($canEdit)
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#convertLead"><i class="ri-arrow-right-circle-line"></i> Convert to booking</button>
      @endif
    </div>
  </div>

  @if($canEdit)
  <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top stage-steps">
    <span class="text-muted me-1">Stage:</span>
    @foreach($open as $stage)
      <form method="post" action="{{ route('leads.stage', $lead) }}">@csrf @method('PATCH')<input type="hidden" name="lead_stage_id" value="{{ $stage->id }}">
        <button class="btn btn-sm {{ $lead->lead_stage_id === $stage->id ? 'btn-'.$stage->color : 'btn-outline-'.$stage->color }}" @disabled($lead->lead_stage_id === $stage->id)>{{ $stage->name }}</button></form>
    @endforeach
    <span class="ms-auto"></span>
    @if($lostStage && $lead->lead_stage_id !== $lostStage->id)<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#markLost"><i class="ri-close-circle-line"></i> {{ $lostStage->name }}</button>@endif
    @if($spamStage && $lead->lead_stage_id !== $spamStage->id)<form method="post" action="{{ route('leads.stage', $lead) }}" onsubmit="return confirm('Mark this lead as spam?')">@csrf @method('PATCH')<input type="hidden" name="lead_stage_id" value="{{ $spamStage->id }}"><button class="btn btn-sm btn-outline-dark"><i class="ri-spam-2-line"></i> {{ $spamStage->name }}</button></form>@endif
  </div>
  @endif
</div></div>

<div class="row">
  {{-- Left: the facts. --}}
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h6 class="text-uppercase text-muted small mb-3">Request</h6>
      <dl class="row mb-0">
        <dt class="col-5 fw-normal text-muted">Type</dt><dd class="col-7">{{ \App\Models\Lead::TYPES[$lead->type] ?? $lead->type }}</dd>
        <dt class="col-5 fw-normal text-muted">Tour</dt><dd class="col-7">{{ $lead->tour?->name ?? '—' }}</dd>
        <dt class="col-5 fw-normal text-muted">Departure</dt><dd class="col-7">{{ $lead->departure?->departs_at?->format('D, M j, Y g:i A') ?? '—' }}</dd>
        <dt class="col-5 fw-normal text-muted">Guests</dt><dd class="col-7">{{ $lead->guests ?? '—' }}</dd>
        <dt class="col-5 fw-normal text-muted">Pick-up</dt><dd class="col-7">{{ $lead->pickupPoint?->name ?? '—' }}</dd>
        @if($lead->requestBooking && !$lead->booking_id)<dt class="col-5 fw-normal text-muted">Original request</dt><dd class="col-7"><a href="{{ route('bookings.show', $lead->requestBooking) }}">{{ $lead->requestBooking->reference }}</a></dd>@endif
        @if($lead->visaEnquiry)<dt class="col-5 fw-normal text-muted">Visa enquiry</dt><dd class="col-7"><a href="{{ route('visa-enquiries.show', $lead->visaEnquiry) }}">{{ $lead->visaEnquiry->reference }}</a></dd>@endif
      </dl>
      @if($lead->message)<div class="bg-light rounded p-2 mt-2 small" style="white-space:pre-line">{{ $lead->message }}</div>@endif
    </div></div>

    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between"><h6 class="text-uppercase text-muted small mb-3">Contact</h6>@if($canEdit)<a href="#" class="small" data-bs-toggle="modal" data-bs-target="#editLead">Edit</a>@endif</div>
      <dl class="row mb-0">
        <dt class="col-5 fw-normal text-muted">Email</dt><dd class="col-7 text-break">{{ $lead->email ?: '—' }}</dd>
        <dt class="col-5 fw-normal text-muted">Phone</dt><dd class="col-7">{{ $lead->phone ?: '—' }}</dd>
        <dt class="col-5 fw-normal text-muted">WhatsApp</dt><dd class="col-7">{{ $lead->whatsapp ?: '—' }}</dd>
        <dt class="col-5 fw-normal text-muted">Customer</dt><dd class="col-7">@if($lead->customer)@can('list-customers')<a href="{{ route('customers.show', $lead->customer) }}">{{ $lead->customer->full_name }} →</a>@else{{ $lead->customer->full_name }}@endcan @else — @endif</dd>
      </dl>
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="text-uppercase text-muted small mb-3">Source</h6>
      <dl class="row mb-0">
        <dt class="col-5 fw-normal text-muted">Source</dt><dd class="col-7">{{ \App\Models\Lead::SOURCES[$lead->source] ?? $lead->source }}</dd>
        @foreach(['utm_source' => 'UTM source', 'utm_medium' => 'Medium', 'utm_campaign' => 'Campaign', 'utm_term' => 'Keyword', 'utm_content' => 'Ad'] as $field => $label)
          @if($lead->{$field})<dt class="col-5 fw-normal text-muted">{{ $label }}</dt><dd class="col-7 text-break">{{ $lead->{$field} }}</dd>@endif
        @endforeach
        @if($lead->landing_page)<dt class="col-5 fw-normal text-muted">Landing page</dt><dd class="col-7 text-break"><a href="{{ $lead->landing_page }}" target="_blank" rel="noopener">{{ str(parse_url($lead->landing_page, PHP_URL_PATH) ?: '/')->limit(40) }}</a></dd>@endif
        @if($lead->referrer)<dt class="col-5 fw-normal text-muted">Came from</dt><dd class="col-7 text-break">{{ parse_url($lead->referrer, PHP_URL_HOST) ?: $lead->referrer }}</dd>@endif
      </dl>
    </div></div>

    @if($canEdit)
    <div class="card"><div class="card-body">
      <h6 class="text-uppercase text-muted small mb-2">Assigned to</h6>
      <form method="post" action="{{ route('leads.assign', $lead) }}">@csrf @method('PATCH')
        <select class="form-select" name="assigned_user_id" onchange="this.form.submit()" aria-label="Assigned to"><option value="">Nobody yet</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($lead->assigned_user_id === $user->id)>{{ $user->name }}{{ $user->id === auth()->id() ? ' (me)' : '' }}</option>@endforeach</select>
      </form>
      @if(!$lead->assigned_user_id)<form method="post" action="{{ route('leads.assign', $lead) }}" class="mt-2">@csrf @method('PATCH')<input type="hidden" name="assigned_user_id" value="{{ auth()->id() }}"><button class="btn btn-sm btn-light w-100">Assign to me</button></form>@endif
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="text-uppercase text-muted small mb-2">Follow-up reminder</h6>
      @if($lead->next_follow_up_at)
        <div class="alert {{ $lead->isFollowUpDue() ? 'alert-danger' : 'alert-info' }} py-2 small mb-2"><i class="ri-alarm-line"></i> {{ $lead->next_follow_up_at->timezone($tz)->format('D, M j, g:i A') }}{{ $lead->isFollowUpDue() ? ' — overdue' : '' }}@if($lead->follow_up_note)<br>{{ $lead->follow_up_note }}@endif</div>
      @endif
      <form method="post" action="{{ route('leads.follow-up', $lead) }}">@csrf @method('PATCH')
        <div class="d-flex flex-wrap gap-1 mb-2">@foreach(['today' => 'Today 5 PM', 'tomorrow' => 'Tomorrow', '3days' => 'In 3 days', 'week' => 'Next week'] as $value => $label)<button class="btn btn-sm btn-light" name="when" value="{{ $value }}">{{ $label }}</button>@endforeach</div>
        <div class="input-group mb-2"><input class="form-control" type="datetime-local" name="next_follow_up_at" aria-label="Custom date and time"><button class="btn btn-outline-primary" name="when" value="custom">Set</button></div>
        <input class="form-control mb-2" name="follow_up_note" maxlength="255" placeholder="What to do (optional)" value="{{ $lead->follow_up_note }}">
        @if($lead->next_follow_up_at)<button class="btn btn-sm btn-link text-danger p-0" name="when" value="clear">Clear reminder</button>@endif
      </form>
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="text-uppercase text-muted small mb-2">Tags</h6>
      <form method="post" action="{{ route('leads.tags', $lead) }}">@csrf @method('PUT')
        @forelse($tags as $tag)<label class="me-2 small"><input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked($lead->tags->contains($tag))> {{ $tag->name }}</label>@empty<span class="text-muted">No tags yet.</span>@endforelse
        @if($tags->isNotEmpty())<div><button class="btn btn-sm btn-light mt-2">Save tags</button></div>@endif
      </form>
    </div></div>
    @endif
  </div>

  {{-- Right: what happened and what to do next. --}}
  <div class="col-lg-8">
    @if($canEdit)
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3">Log a contact or note</h5>
      <form method="post" action="{{ route('leads.note', $lead) }}" id="note-form">@csrf
        <div class="d-flex flex-wrap gap-1 mb-2 kind-pick" role="radiogroup" aria-label="Type">
          @foreach(\App\Models\LeadNote::KINDS as $kind => [$label, $icon])
            <input type="radio" class="btn-check" name="kind" id="kind-{{ $kind }}" value="{{ $kind }}" @checked(old('kind', 'call') === $kind)><label class="btn btn-sm btn-outline-primary" for="kind-{{ $kind }}"><i class="{{ $icon }}"></i> {{ $label }}</label>
          @endforeach
        </div>
        <div class="d-flex flex-wrap gap-3 mb-2 small" id="outcomes">@foreach(\App\Models\LeadNote::OUTCOMES as $value => $label)<label><input type="radio" name="outcome" value="{{ $value }}" @checked(old('outcome') === $value)> {{ $label }}</label>@endforeach</div>
        <textarea class="form-control mb-2" name="body" rows="3" placeholder="What did you talk about? Next steps?">{{ old('body') }}</textarea>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Calls, WhatsApp, email and SMS count as contact attempts{{ in_array($lead->stage->key, ['new', 'assigned'], true) ? ' and move the lead to “Contacted”' : '' }}.</span>
          <button class="btn btn-primary">Save</button>
        </div>
      </form>
    </div></div>
    @endif

    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3">Timeline <small class="text-muted fw-normal">({{ $lead->notes->count() }} {{ str('entry')->plural($lead->notes->count()) }}, {{ $lead->contact_attempts }} contact {{ str('attempt')->plural($lead->contact_attempts) }})</small></h5>
      @forelse($lead->notes as $note)
        <div class="timeline-item">
          <span class="timeline-icon"><i class="{{ \App\Models\LeadNote::KINDS[$note->kind][1] ?? 'ri-sticky-note-line' }}"></i></span>
          <div><strong>{{ \App\Models\LeadNote::KINDS[$note->kind][0] ?? $note->kind }}</strong>@if($note->outcome) · <span class="{{ $note->outcome === 'reached' ? 'text-success' : 'text-muted' }}">{{ \App\Models\LeadNote::OUTCOMES[$note->outcome] ?? $note->outcome }}</span>@endif
            <span class="text-muted">· {{ $note->user?->name ?? 'System' }} · <span title="{{ $note->created_at->timezone($tz)->format('M j, Y g:i A') }}">{{ $note->created_at->diffForHumans() }}</span></span></div>
          @if($note->body)<div class="mt-1" style="white-space:pre-line">{{ $note->body }}</div>@endif
        </div>
      @empty
        <div class="text-center text-muted py-4"><i class="ri-chat-history-line fs-2 d-block mb-1"></i>No calls or notes yet. Log the first contact above.</div>
      @endforelse
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="text-uppercase text-muted small mb-2">History</h6>
      @forelse($activity as $log)
        <div class="d-flex gap-3 small py-1 border-top"><span class="text-muted text-nowrap" style="width:130px">{{ $log->created_at->timezone($tz)->format('M j, g:i A') }}</span><span><strong>{{ $log->actor_name }}</strong> · {{ str($log->action)->replace('_', ' ') }}@if(isset($log->changes['stage'])) — {{ $log->changes['stage'][0] ?? '—' }} → {{ $log->changes['stage'][1] }}@endif</span></div>
      @empty
        <p class="text-muted mb-0">Nothing yet.</p>
      @endforelse
      @can('delete-leads')<form method="post" action="{{ route('leads.destroy', $lead) }}" class="text-end mt-3" onsubmit="return confirm('Delete lead {{ $lead->reference }}? The original request and any booking are kept.')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Delete lead</button></form>@endcan
    </div></div>
  </div>
</div>

@if($canEdit)
{{-- Convert: everything known is copied; staff only pick the date and guests. --}}
<div class="modal fade" id="convertLead" tabindex="-1" aria-labelledby="convertLeadTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('leads.convert', $lead) }}">@csrf
  <div class="modal-header"><h5 class="modal-title" id="convertLeadTitle">Convert to booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <p class="text-muted">{{ $lead->full_name }}'s contact details{{ $lead->message ? ', message' : '' }} and pick-up are copied to the booking. You can add passengers and adjust the price on the booking page.</p>
    <label class="form-label">Departure</label>
    <select class="form-select mb-3" name="tour_departure_id" required>
      <option value="">Choose a date…</option>
      @foreach($departures->groupBy(fn ($d) => $d->tour?->name) as $tourName => $dates)
        <optgroup label="{{ $tourName }}">@foreach($dates as $departure)<option value="{{ $departure->id }}" @selected(old('tour_departure_id', $lead->tour_departure_id) == $departure->id)>{{ $departure->departs_at->format('D, M j, Y g:i A') }} — {{ $departure->available_seats }} seats left · {{ money($departure->from_price, $departure->currency) }}</option>@endforeach</optgroup>
      @endforeach
    </select>
    <label class="form-label">Guests</label>
    <input class="form-control" type="number" min="1" max="99" name="guests" value="{{ old('guests', $lead->guests ?: 1) }}" required>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create booking</button></div>
</form></div></div>

<div class="modal fade" id="markLost" tabindex="-1" aria-labelledby="markLostTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('leads.stage', $lead) }}">@csrf @method('PATCH')
  <input type="hidden" name="lead_stage_id" value="{{ $lostStage?->id }}">
  <div class="modal-header"><h5 class="modal-title" id="markLostTitle">Why was this lead lost?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <div class="d-flex flex-wrap gap-1 mb-2">@foreach(['Price too high', 'Chose another company', 'Dates did not work', 'No reply', 'Tour sold out', 'Just asking'] as $reason)<button type="button" class="btn btn-sm btn-light" onclick="this.closest('form').lost_reason.value = this.textContent">{{ $reason }}</button>@endforeach</div>
    <input class="form-control" name="lost_reason" maxlength="255" placeholder="Reason" required>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Mark as lost</button></div>
</form></div></div>

<div class="modal fade" id="editLead" tabindex="-1" aria-labelledby="editLeadTitle" aria-hidden="true"><div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="{{ route('leads.update', $lead) }}">@csrf @method('PUT')
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
