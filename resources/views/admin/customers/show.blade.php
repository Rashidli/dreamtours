@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customerdetail.css') }}?v={{ @filemtime(public_path('assets/css/admin-customerdetail.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php($tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)
@php($bookingTones = ['new' => 'info', 'contacted' => 'info', 'payment_pending' => 'warning', 'confirmed' => 'success', 'completed' => 'secondary', 'cancelled' => 'danger'])
@php($fin = auth()->user()->can('view-financial-info'))
@php($live = !$customer->trashed())
{{-- "Book for this customer" exists on develop (bookings.create); the button appears wherever the route does. --}}
@php($canBook = $live && \Illuminate\Support\Facades\Route::has('bookings.create') && auth()->user()->can('create-bookings'))
@php($tel = preg_replace('/[^\d+]/', '', (string) $customer->phone))
@php($wa = preg_replace('/\D/', '', (string) $customer->whatsapp))
@php($initials = mb_strtoupper(mb_substr($customer->first_name ?? '', 0, 1).mb_substr($customer->last_name ?? '', 0, 1)) ?: '?')
@php($upcoming = $customer->bookings->filter(fn ($b) => $b->status !== 'cancelled' && $b->departure?->departs_at?->isFuture())->sortBy(fn ($b) => $b->departure->departs_at)->values())
@php($owing = $fin ? $customer->bookings->filter(fn ($b) => $b->tour_departure_id && $b->status !== 'cancelled' && (float) $b->outstanding_amount > 0)->sortByDesc(fn ($b) => (float) $b->outstanding_amount)->values() : collect())
@php($leads = $customer->relationLoaded('leads') ? $customer->leads : collect())
{{-- Tour names typed in ALL CAPS read as shouting: show them in title case (same rule as the dashboard). --}}
@php($niceName = fn (?string $n) => $n !== null && $n === mb_strtoupper($n) ? mb_convert_case(mb_strtolower($n), MB_CASE_TITLE) : $n)
@php($openLeads = $leads->filter(fn ($l) => !$l->stage || !$l->stage->isClosed())->values())
@php($nextLead = $openLeads->filter(fn ($l) => $l->next_follow_up_at)->sortBy('next_follow_up_at')->first())
@php($activityShown = 8)

{{-- ========== Header: who is this, how do I reach them ========== --}}
<div class="cd-header">
  <div class="cd-header-main">
    <a class="btn btn-light cd-back" href="{{ route('customers.index') }}" title="Back to customers" aria-label="Back to customers"><i class="ri-arrow-left-line"></i></a>
    <span class="cs-avatar cs-avatar-xl cd-avatar" aria-hidden="true">{{ $initials }}</span>
    <div class="min-w-0">
      <div class="cd-title">
        <h4>{{ $customer->display_name }}</h4>
        @if($customer->status)<span class="badge bg-{{ $customer->status->color }}">{{ $customer->status->name }}</span>@endif
        @if(!$live)<span class="badge bg-secondary">Archived</span>@endif
      </div>
      <div class="cd-meta">
        <span>Customer #{{ $customer->id }}</span>
        <span>Since {{ $customer->created_at?->timezone($tz)->format('M Y') }}</span>
        @if($customer->last_activity_at)<span title="{{ $customer->last_activity_at->timezone($tz)->format('M j, Y g:i A') }}">Active {{ $customer->last_activity_at->diffForHumans() }}</span>@endif
        @if($customer->assignedUser)<span><i class="ri-user-star-line"></i>{{ $customer->assignedUser->name }}</span>@endif
      </div>
      @if($customer->tags->isNotEmpty())<div class="cs-badges mt-2">@foreach($customer->tags as $tag)<span class="badge cs-tag"><i class="ri-price-tag-3-line me-1"></i>{{ $tag->name }}</span>@endforeach</div>@endif
    </div>
  </div>
  <div class="cd-actions">
    @if($tel)<a class="btn btn-light" href="tel:{{ $tel }}"><i class="ri-phone-line"></i><span>Call</span></a>@endif
    @if($wa)<a class="btn btn-light" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i><span>WhatsApp</span></a>@endif
    @if($customer->email)<a class="btn btn-light" href="mailto:{{ $customer->email }}"><i class="ri-mail-line"></i><span>Email</span></a>@endif
    @can('edit-customers')@if($live)<a class="btn btn-light" href="{{ route('customers.edit', $customer) }}"><i class="ri-pencil-line"></i><span>Edit</span></a>@endif @endcan
    @if($canBook)<a class="btn btn-primary" href="{{ route('bookings.create', ['customer' => $customer->id]) }}"><i class="ri-add-line"></i><span>Book for this customer</span></a>@endif
  </div>
</div>

@include('admin.includes.flash')
@if($customer->merged_into_id)<div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-git-merge-line"></i><div>This customer was merged into <a href="{{ route('customers.show', $customer->merged_into_id) }}">{{ $customer->mergedInto?->full_name ?? 'customer #'.$customer->merged_into_id }}</a>. Their bookings and requests are there now.</div></div>
@elseif($customer->trashed())<div class="alert alert-warning d-flex flex-wrap gap-2 justify-content-between align-items-center"><span><i class="ri-inbox-archive-line me-1"></i>This customer is archived. Their bookings and history are kept.</span>@can('delete-customers')<form method="post" action="{{ route('customers.restore', $customer->id) }}">@csrf<button class="btn btn-light"><i class="ri-arrow-go-back-line"></i> Restore</button></form>@endcan</div>@endif

{{-- ========== Key numbers ========== --}}
<div class="card cs-stat-card cd-stats"><div class="cs-stat-row">
  <div class="cs-stat"><p class="cs-stat-label">Trips</p><p class="cs-stat-value">{{ $stats->trips_count }}</p><p class="cd-stat-sub">{{ $customer->bookings->count() }} {{ $customer->bookings->count() === 1 ? 'booking or request' : 'bookings & requests' }}</p></div>
  @if($fin)
  <div class="cs-stat"><p class="cs-stat-label">Lifetime spend</p><p class="cs-stat-value">{{ money($stats->total_paid) }}</p><p class="cd-stat-sub">Paid so far</p></div>
  <div class="cs-stat"><p class="cs-stat-label">Outstanding</p><p class="cs-stat-value {{ $stats->outstanding > 0 ? 'text-danger' : '' }}">{{ money($stats->outstanding) }}</p>
    <p class="cd-stat-sub">@if($stats->outstanding > 0)@can('list-payments')<a href="{{ route('payments.index', ['tab' => 'outstanding']) }}">See payments <i class="ri-arrow-right-s-line"></i></a>@else on {{ $owing->count() }} {{ str('booking')->plural($owing->count()) }}@endcan @else Nothing owed @endif</p></div>
  @endif
  <div class="cs-stat"><p class="cs-stat-label">{{ $upcoming->isNotEmpty() ? 'Next trip' : 'Last trip' }}</p>
    @if($upcoming->isNotEmpty())<p class="cs-stat-value cd-stat-date">{{ $upcoming->first()->departure->departs_at->format('M j, Y') }}</p><p class="cd-stat-sub">{{ $upcoming->first()->departure->departs_at->diffForHumans() }}</p>
    @else<p class="cs-stat-value cd-stat-date">{{ $stats->last_trip_at ? \Illuminate\Support\Carbon::parse($stats->last_trip_at)->format('M j, Y') : '—' }}</p><p class="cd-stat-sub">{{ $stats->last_trip_at ? \Illuminate\Support\Carbon::parse($stats->last_trip_at)->diffForHumans() : 'No trips yet' }}</p>@endif
  </div>
</div></div>

{{-- ========== What to do next ========== --}}
@if($live && ($owing->isNotEmpty() || $upcoming->isNotEmpty() || $openLeads->isNotEmpty() || (!$customer->phone && !$customer->email)))
<div class="card cd-next"><div class="card-body">
  <h5 class="cd-card-title"><i class="ri-flag-line"></i>Needs attention</h5>
  <div class="cd-next-list">
    @foreach($owing->take(3) as $b)
      <a class="cd-next-item" href="{{ auth()->user()->can('list-bookings') ? route('bookings.show', $b) : '#' }}">
        <span class="cd-next-icon tone-danger"><i class="ri-wallet-3-line"></i></span>
        <span class="min-w-0"><b>Collect {{ money($b->outstanding_amount) }}</b> on {{ $b->reference }}<span class="cs-sub">{{ $niceName($b->tour?->name) }}@if($b->departure) · departs {{ $b->departure->departs_at->format('M j') }}@endif @if($b->balance_due_date) · @if($b->balance_due_date->isPast() && !$b->balance_due_date->isToday())<b class="text-danger">overdue since {{ $b->balance_due_date->format('M j') }}</b>@else due {{ $b->balance_due_date->format('M j') }}@endif @endif</span></span>
        <i class="ri-arrow-right-s-line cd-next-go"></i>
      </a>
    @endforeach
    @foreach($upcoming->take(2) as $b)
      @continue($owing->contains('id', $b->id))
      <a class="cd-next-item" href="{{ auth()->user()->can('list-bookings') ? route('bookings.show', $b) : '#' }}">
        <span class="cd-next-icon tone-info"><i class="ri-calendar-event-line"></i></span>
        <span class="min-w-0"><b>Travels {{ $b->departure->departs_at->diffForHumans() }}</b>: {{ $niceName($b->tour?->name) }}<span class="cs-sub">{{ $b->reference }} · {{ $b->departure->departs_at->format('D, M j, Y g:i A') }} · {{ $b->participant_count }} {{ str('guest')->plural($b->participant_count) }}</span></span>
        <i class="ri-arrow-right-s-line cd-next-go"></i>
      </a>
    @endforeach
    @if($openLeads->isNotEmpty())
      @php($lead = $nextLead ?? $openLeads->first())
      <a class="cd-next-item" href="{{ route('leads.show', $lead) }}">
        <span class="cd-next-icon tone-warning"><i class="ri-customer-service-2-line"></i></span>
        <span class="min-w-0"><b>{{ $openLeads->count() }} open {{ str('lead')->plural($openLeads->count()) }}</b>@if($lead->next_follow_up_at): follow up {{ $lead->next_follow_up_at->timezone($tz)->isPast() ? 'overdue since' : 'on' }} {{ $lead->next_follow_up_at->timezone($tz)->format('M j') }}@endif<span class="cs-sub">{{ $niceName($lead->tour?->name) ?? str($lead->type)->replace('_', ' ')->ucfirst() }} · {{ $lead->stage?->name }}</span></span>
        <i class="ri-arrow-right-s-line cd-next-go"></i>
      </a>
    @endif
    @if(!$customer->phone && !$customer->email)
      <div class="cd-next-item">
        <span class="cd-next-icon tone-warning"><i class="ri-error-warning-line"></i></span>
        <span class="min-w-0"><b>No way to reach them</b><span class="cs-sub">Add an email or phone number.</span></span>
      </div>
    @endif
  </div>
</div></div>
@endif

<div class="row gx-lg-4">
  {{-- ========== Left: bookings, leads, activity ========== --}}
  <div class="col-lg-8">
    <div class="card cd-card"><div class="card-body p-0">
      <div class="cd-card-head">
        <h5 class="cd-card-title mb-0"><i class="ri-suitcase-line"></i>Bookings & requests <span class="text-muted fw-normal">{{ $customer->bookings->count() }}</span></h5>
        @if($canBook && $customer->bookings->isNotEmpty())<a class="btn btn-light" href="{{ route('bookings.create', ['customer' => $customer->id]) }}"><i class="ri-add-line me-1"></i>New booking</a>@endif
      </div>
      @forelse($customer->bookings as $booking)
        {{-- Ledger figures (BookingMoney): due after discounts, net paid, and the outstanding it computed. --}}
        @php($balance = $booking->tour_departure_id ? (float) $booking->outstanding_amount : 0)
        @php($due = $booking->tour_departure_id ? (float) bcsub((string) $booking->total_amount, (string) $booking->discount_amount, 2) : 0)
        @php($paidPct = $due > 0 ? min(100, round((float) $booking->amount_paid / $due * 100)) : 0)
        @php($tone = $bookingTones[$booking->status] ?? 'secondary')
        <div class="cd-bk {{ $booking->status === 'cancelled' ? 'is-cancelled' : '' }}">
          <div class="cd-bk-main">
            <div class="cd-bk-ref">
              @can('list-bookings')<a href="{{ route('bookings.show', $booking) }}">{{ $booking->reference }}</a>@else{{ $booking->reference }}@endcan
              <span class="badge bg-{{ $tone }} bg-opacity-10 text-{{ $tone }}">{{ \App\Models\Booking::STATUSES[$booking->status] ?? $booking->status }}</span>
            </div>
            <div class="cd-bk-trip">{{ $niceName($booking->tour?->name) ?? \App\Mail\EnquiryReceived::LABELS[$booking->type] ?? ucfirst($booking->type) }}</div>
            <div class="cs-sub">
              @if($booking->departure?->departs_at)<i class="ri-calendar-event-line"></i> {{ $booking->departure->departs_at->format('D, M j, Y') }}<span class="cd-rel">· {{ $booking->departure->departs_at->diffForHumans() }}</span>@else<i class="ri-time-line"></i> Requested {{ $booking->created_at?->timezone($tz)->format('M j, Y') }}@endif
              · {{ $booking->participant_count }} {{ str('guest')->plural($booking->participant_count) }}
            </div>
          </div>
          @if($fin)
          <div class="cd-bk-money">
            @if($booking->tour_departure_id)
              <div class="cd-bk-total">{{ money($due) }}</div>
              <div class="cd-paybar" title="{{ $paidPct }}% paid"><span style="width: {{ $paidPct }}%"></span></div>
              <div class="cs-sub">{{ money($booking->amount_paid) }} paid @if($balance > 0) · <b class="text-danger">{{ money($balance) }} due</b>@endif</div>
            @else
              <div class="cs-sub">Enquiry · no price yet</div>
            @endif
          </div>
          @endif
          @can('list-bookings')<a class="btn btn-light cd-bk-open" href="{{ route('bookings.show', $booking) }}" aria-label="Open {{ $booking->reference }}"><i class="ri-arrow-right-s-line"></i></a>@endcan
        </div>
      @empty
        <div class="cs-empty cd-empty">
          <i class="ri-suitcase-line"></i>
          <h5>No bookings yet</h5>
          <p>When {{ $customer->first_name ?: 'this customer' }} books a trip or sends a request, it shows up here with what they paid and still owe.</p>
          @if($canBook)<a class="btn btn-primary" href="{{ route('bookings.create', ['customer' => $customer->id]) }}"><i class="ri-add-line me-1"></i>Book their first trip</a>@endif
        </div>
      @endforelse
    </div></div>

    @if($leads->isNotEmpty())
    <div class="card cd-card"><div class="card-body p-0">
      <div class="cd-card-head"><h5 class="cd-card-title mb-0"><i class="ri-customer-service-2-line"></i>Leads <span class="text-muted fw-normal">{{ $leads->count() }}</span></h5></div>
      @foreach($leads as $lead)
        <a class="cd-row-link" href="{{ route('leads.show', $lead) }}">
          <div class="min-w-0">
            <div class="fw-semibold">{{ $niceName($lead->tour?->name) ?? str($lead->type)->replace('_', ' ')->ucfirst() }}</div>
            <div class="cs-sub">Created {{ $lead->created_at?->timezone($tz)->format('M j, Y') }}@if($lead->guests) · {{ $lead->guests }} {{ str('guest')->plural($lead->guests) }}@endif @if($lead->next_follow_up_at) · follow up {{ $lead->next_follow_up_at->timezone($tz)->format('M j') }}@endif</div>
          </div>
          @if($lead->stage)<span class="badge bg-{{ $lead->stage->color }} bg-opacity-10 text-{{ $lead->stage->color }}">{{ $lead->stage->name }}</span>@endif
          <i class="ri-arrow-right-s-line cd-next-go"></i>
        </a>
      @endforeach
    </div></div>
    @endif

    @if($customer->visaEnquiries->isNotEmpty())
    <div class="card cd-card"><div class="card-body p-0">
      <div class="cd-card-head"><h5 class="cd-card-title mb-0"><i class="ri-passport-line"></i>Visa enquiries <span class="text-muted fw-normal">{{ $customer->visaEnquiries->count() }}</span></h5></div>
      @foreach($customer->visaEnquiries as $enquiry)
        <a class="cd-row-link" href="{{ route('visa-enquiries.show', $enquiry) }}">
          <div class="min-w-0"><div class="fw-semibold">{{ $enquiry->reference }}</div><div class="cs-sub">{{ $enquiry->country }} · {{ $enquiry->created_at?->timezone($tz)->format('M j, Y') }}</div></div>
          <i class="ri-arrow-right-s-line cd-next-go"></i>
        </a>
      @endforeach
    </div></div>
    @endif

    @if($companions->isNotEmpty())
    <div class="card cd-card"><div class="card-body">
      <h5 class="cd-card-title"><i class="ri-team-line"></i>Travelled with</h5>
      <p class="cs-badges mb-0">@foreach($companions as $passenger)<span class="cs-person-chip"><i class="ri-user-3-line"></i>{{ $passenger->first_name }} {{ $passenger->last_name }}</span>@endforeach</p>
    </div></div>
    @endif

    <div class="card cd-card"><div class="card-body">
      <h5 class="cd-card-title"><i class="ri-history-line"></i>Activity <span class="text-muted fw-normal">{{ $activity->count() }}{{ $activity->count() === 100 ? '+' : '' }}</span></h5>
      @php($seen = 0)
      @forelse($activity->groupBy(fn ($log) => $log->created_at->timezone($tz)->format('Y-m-d')) as $day => $logs)
        @php($dayDate = \Illuminate\Support\Carbon::parse($day))
        <div class="cd-day {{ !$loop->first && $seen >= $activityShown ? 'd-none cd-more' : '' }}">
        @php($seen += $logs->count())
          <div class="cd-day-label">{{ $dayDate->isToday() ? 'Today' : ($dayDate->isYesterday() ? 'Yesterday' : $dayDate->format('D, M j, Y')) }}</div>
          @foreach($logs as $log)
            @php($a = (string) $log->action)
            @php($icon = str_contains($a, 'created') ? 'ri-add-circle-line' : (str_contains($a, 'deleted') ? 'ri-delete-bin-line' : (str_contains($a, 'email') ? 'ri-mail-send-line' : (str_contains($a, 'payment') ? 'ri-money-dollar-circle-line' : (str_contains($a, 'linked') || str_contains($a, 'merge') ? 'ri-links-line' : (str_contains($a, 'stage') ? 'ri-exchange-line' : 'ri-edit-line'))))))
            <div class="cd-event">
              <span class="cd-event-icon"><i class="{{ $icon }}"></i></span>
              <div class="min-w-0 flex-grow-1">
                <div><strong>{{ $log->actor_name }}</strong> {{ str($a)->replace('_', ' ') }} <span class="text-muted">{{ $log->moduleLabel() }}: {{ $log->subject_label }}</span></div>
              </div>
              <span class="cd-event-time">{{ $log->created_at->timezone($tz)->format('g:i A') }}</span>
            </div>
          @endforeach
        </div>
      @empty
        <div class="cs-empty cs-empty-sm"><i class="ri-history-line"></i>No recorded activity yet. Changes to this customer and their bookings appear here.</div>
      @endforelse
      @if($activity->isNotEmpty() && $seen > $activityShown && $activity->groupBy(fn ($log) => $log->created_at->timezone($tz)->format('Y-m-d'))->count() > 1)
        <button type="button" class="btn btn-light w-100 mt-2" id="cd-more-activity" onclick="document.querySelectorAll('.cd-more').forEach(e => e.classList.remove('d-none')); this.remove();">Show all activity</button>
        <script>if (!document.querySelector('.cd-more')) document.getElementById('cd-more-activity')?.remove();</script>
      @endif
    </div></div>
  </div>

  {{-- ========== Right: contact, consent, notes, details ========== --}}
  <div class="col-lg-4">
    <div class="card cd-card"><div class="card-body">
      <h5 class="cd-card-title"><i class="ri-user-3-line"></i>Contact</h5>
      <ul class="cd-contact">
        <li><i class="ri-mail-line"></i>@if($customer->email)<a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@else<span class="text-muted">No email</span>@endif</li>
        <li><i class="ri-phone-line"></i>@if($customer->phone)<a href="tel:{{ $tel }}">{{ $customer->phone }}</a>@else<span class="text-muted">No phone</span>@endif</li>
        <li><i class="ri-whatsapp-line"></i>@if($customer->whatsapp)<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">{{ $customer->whatsapp }}</a>@else<span class="text-muted">No WhatsApp</span>@endif</li>
        @if($customer->city)<li><i class="ri-map-pin-line"></i>{{ $customer->city }}</li>@endif
        @if($customer->language)<li><i class="ri-translate-2"></i>{{ $customer->language }}</li>@endif
        @can('view-customer-birthdays')<li><i class="ri-cake-2-line"></i>@if($customer->date_of_birth){{ $customer->date_of_birth->format('M j, Y') }} <span class="text-muted">· {{ $customer->date_of_birth->age }} years</span>@else<span class="text-muted">Birthday not set</span>@endif</li>@endcan
      </ul>
    </div></div>

    <div class="card cd-card"><div class="card-body">
      <h5 class="cd-card-title"><i class="ri-mail-send-line"></i>Marketing</h5>
      @if($customer->isMarketingEligible())<span class="badge bg-success bg-opacity-10 text-success cd-consent"><i class="ri-mail-send-line"></i> Can receive promotions</span>
      @elseif($customer->status?->blocks_marketing)<span class="badge bg-dark cd-consent"><i class="ri-mail-forbid-line"></i> Do not contact — no promotions</span>
      @else<span class="badge bg-secondary bg-opacity-10 text-secondary cd-consent"><i class="ri-mail-forbid-line"></i> No marketing consent</span>@endif
      @if($customer->marketing_opt_in_source)<p class="cs-consent-note">Consent: {{ $customer->marketing_opt_in_source }}, {{ $customer->marketing_opt_in_at?->timezone($tz)->format('M j, Y') }}</p>
      @elseif(!$customer->isMarketingEligible() && !$customer->status?->blocks_marketing)<p class="cs-consent-note">Only send booking messages. Ask before adding them to promotions.</p>@endif
    </div></div>

    <div class="card cd-card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start"><h5 class="cd-card-title"><i class="ri-sticky-note-line"></i>Notes</h5>
        @can('edit-customers')@if($live)<a class="cd-card-link" href="{{ route('customers.edit', $customer) }}">{{ $customer->notes ? 'Edit' : 'Add' }}</a>@endif @endcan</div>
      @if($customer->notes)<p class="cs-note mb-0">{!! nl2br(e($customer->notes)) !!}</p>
      @else<p class="text-muted mb-0">No notes yet. Add preferences, allergies or anything the next person should know.</p>@endif
    </div></div>

    <div class="card cd-card"><div class="card-body">
      <h5 class="cd-card-title"><i class="ri-information-line"></i>Details</h5>
      <dl class="cs-dl cd-dl mb-0">
        <dt>Source</dt><dd>{{ \App\Models\Customer::SOURCES[$customer->source] ?? ($customer->source ?: '—') }}</dd>
        <dt>Assigned to</dt><dd>{{ $customer->assignedUser?->name ?? 'Nobody' }}</dd>
        <dt>Customer since</dt><dd>{{ $customer->created_at?->timezone($tz)->format('M j, Y') }}</dd>
        <dt>Last activity</dt><dd>{{ $customer->last_activity_at?->timezone($tz)->format('M j, Y g:i A') ?? '—' }}</dd>
        <dt>Customer ID</dt><dd>#{{ $customer->id }}</dd>
      </dl>
    </div></div>

    {{-- Quiet danger zone: merge and archive, each with a clear explanation and confirmation --}}
    @if($live && (auth()->user()->can('merge-customers') || auth()->user()->can('delete-customers')))
    <div class="card cd-card cd-danger"><div class="card-body">
      <h5 class="cd-card-title"><i class="ri-settings-3-line"></i>Manage record</h5>
      @can('merge-customers')
      <form class="cd-merge" method="get" action="{{ route('customer-merge.compare') }}">
        <input type="hidden" name="keep" value="{{ $customer->id }}">
        <label class="fw-semibold form-label" for="merge-other"><i class="ri-git-merge-line me-1"></i>Duplicate of another customer?</label>
        <div class="input-group">
          <span class="input-group-text">#</span>
          <input class="form-control" type="number" min="1" name="other" id="merge-other" required placeholder="Other customer's ID">
          <button class="btn btn-light">Compare</button>
        </div>
        <div class="form-text">You'll see both records side by side before anything is merged. This record is kept.</div>
      </form>
      @endcan
      @can('delete-customers')
      <form method="post" action="{{ route('customers.destroy', $customer) }}" class="cd-archive" onsubmit="return confirm('Archive {{ addslashes($customer->full_name) }}? Their bookings and history are kept and they can be restored.')">@csrf @method('DELETE')
        <button class="btn btn-outline-danger w-100"><i class="ri-inbox-archive-line me-1"></i>Archive customer</button>
        <div class="form-text">Hides them from lists. Bookings and history are kept; you can restore them later.</div>
      </form>
      @endcan
    </div></div>
    @endif
  </div>
</div>
</div></div></div>@include('admin.includes.footer')
