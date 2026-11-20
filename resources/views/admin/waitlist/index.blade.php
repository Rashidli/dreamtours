@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-waitlist.css') }}?v={{ @filemtime(public_path('assets/css/admin-waitlist.css')) }}">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $active = $f['show'] === 'active';
  $statuses = \App\Models\WaitlistEntry::STATUSES;
  $tabUrl = fn (string $show) => route('waitlist.index', array_filter(['departure' => $f['departure'], 'show' => $show === 'closed' ? 'closed' : null]));
  $digits = fn (?string $phone) => preg_replace('/\D/', '', (string) $phone);
  // WhatsApp needs the country code: a bare 10-digit number is North American (+1).
  $wa = function (?string $phone) use ($digits) { $n = $digits($phone); return strlen($n) === 10 ? '1'.$n : $n; };
  $initials = fn ($e) => mb_strtoupper(mb_substr($e->first_name, 0, 1).mb_substr($e->last_name, 0, 1));

  // Per date: seats free now, and the first person (in queue order) whose party fits.
  $summary = $groups->map(function ($entries) {
      $left = max(0, $entries->first()->departure->available_seats);
      return ['left' => $left, 'fit' => $entries->first(fn ($e) => $e->guests <= $left)];
  });
  $all = $groups->flatten(1);
  $people = $all->sum('guests');
  $openDates = $summary->filter(fn ($s) => $s['left'] > 0 && $s['fit'])->count();
  $offeredCount = $all->where('status', 'offered')->count();

  // The date filter: upcoming dates, plus the filtered date if it is no longer upcoming.
  $filterDates = $departures->keyBy('id');
  foreach ($groups as $entries) { $filterDates->put($entries->first()->departure->id, $entries->first()->departure); }
  $filterDates = $filterDates->sortBy('departs_at');
  $currentDate = $f['departure'] ? $filterDates->get((int) $f['departure']) : null;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="row"><div class="col-12">
    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex flex-wrap align-items-baseline gap-2"><h4 class="mb-0">Waitlist</h4>
        <span class="text-muted">@if($all->isEmpty()){{ $active ? 'Nobody waiting' : 'Nothing closed yet' }}@elseif($active){{ $all->count() }} {{ Str::plural('party', $all->count()) }} · {{ $people }} {{ Str::plural('seat', $people) }} wanted on {{ $groups->count() }} {{ Str::plural('date', $groups->count()) }}@else{{ $all->count() }} closed {{ Str::plural('entry', $all->count()) }}@endif</span></div>
      @can('edit-bookings')<button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#add-waitlist"><i class="ri-user-add-line me-1"></i>Add to waitlist</button>@endcan
    </div>
  </div></div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

  @if($active && $groups->isNotEmpty())
    {{-- What needs doing first: dates where a seat has opened up for someone in the queue. --}}
    <div class="card wl-stats">
      <div class="wl-stat {{ $openDates ? 'is-go' : '' }}"><span class="wl-stat-label"><i class="ri-checkbox-circle-line"></i> Seats open now</span><span class="wl-stat-value">{{ $openDates }}</span><span class="wl-stat-note">{{ $openDates ? Str::plural('date', $openDates).' where the next party fits — offer it' : 'All waitlisted dates are still full' }}</span></div>
      <div class="wl-stat"><span class="wl-stat-label"><i class="ri-calendar-line"></i> Dates with a waitlist</span><span class="wl-stat-value">{{ $groups->count() }}</span><span class="wl-stat-note">Next: {{ $groups->first()->first()->departure->departs_at->format('M j') }}</span></div>
      <div class="wl-stat"><span class="wl-stat-label"><i class="ri-team-line"></i> People waiting</span><span class="wl-stat-value">{{ $people }}</span><span class="wl-stat-note">in {{ $all->count() }} {{ Str::plural('party', $all->count()) }}</span></div>
      <div class="wl-stat {{ $offeredCount ? 'is-warn' : '' }}"><span class="wl-stat-label"><i class="ri-mail-send-line"></i> Offered, not booked</span><span class="wl-stat-value">{{ $offeredCount }}</span><span class="wl-stat-note">{{ $offeredCount ? 'Follow up, then press Book' : 'No open offers' }}</span></div>
    </div>
  @endif

  {{-- Tabs and filters --}}
  <div class="card tl-card wl-filters"><div class="card-body p-0">
    <div class="tl-tabs-bar">
      <div class="tl-tabs"><a class="tl-tab {{ $active ? 'active' : '' }}" href="{{ $tabUrl('active') }}">Waiting</a><a class="tl-tab {{ $active ? '' : 'active' }}" href="{{ $tabUrl('closed') }}">Booked & removed</a></div>
      <button type="button" class="btn btn-link wl-how-toggle ms-auto" data-bs-toggle="collapse" data-bs-target="#wl-how" aria-expanded="false" aria-controls="wl-how"><i class="ri-information-line"></i><span> How it works</span></button>
    </div>
    <div class="collapse" id="wl-how"><ol class="wl-how">
      <li><strong>Guests join</strong> from the tour page when a date is full — or you add them here.</li>
      <li><strong>Seats free up</strong>: you get an alert about the first person whose party fits (shown in green below).</li>
      <li><strong>Offer the seat</strong> by phone or email, then press <b>Book</b>: the booking form opens with their details and the entry closes once the booking is saved.</li>
    </ol></div>
    <form class="tl-search-bar wl-filter-bar" method="get" action="{{ route('waitlist.index') }}">
      @unless($active)<input type="hidden" name="show" value="closed">@endunless
      <select class="form-select wl-date-select" name="departure" aria-label="Filter by date" onchange="this.form.submit()">
        <option value="">All dates</option>
        @foreach($filterDates->groupBy(fn ($d) => $d->tour?->name) as $tourName => $dates)<optgroup label="{{ $tourName }}">@foreach($dates as $d)<option value="{{ $d->id }}" @selected((int) $f['departure'] === $d->id)>{{ $d->departs_at->format('D, M j, Y') }}{{ $groups->has($d->id) ? ' · '.$groups[$d->id]->count().($active ? ' waiting' : ' closed') : '' }}</option>@endforeach</optgroup>@endforeach
      </select>
      <noscript><button class="btn btn-light">Apply</button></noscript>
      @if($groups->isNotEmpty())
        <div class="wl-seg" role="radiogroup" aria-label="Status">
          @foreach(($active ? ['all' => 'All', 'waiting' => 'Waiting', 'offered' => 'Offered'] : ['all' => 'All', 'booked' => 'Booked', 'removed' => 'Removed']) as $value => $label)<input type="radio" class="btn-check" name="wl-status" id="wl-status-{{ $value }}" value="{{ $value }}" form="wl-client" @checked($value === 'all')><label class="wl-seg-item" for="wl-status-{{ $value }}">{{ $label }}</label>@endforeach
        </div>
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" id="wl-search" form="wl-client" placeholder="Find a name, email or phone" aria-label="Find a name, email or phone"></label>
      @endif
    </form>
    @if($currentDate)
      <div class="tl-chips tl-chips-bar"><a href="{{ route('waitlist.index', array_filter(['show' => $active ? null : 'closed'])) }}" class="tl-chip" title="Show all dates">Date: {{ $currentDate->tour?->name }} · {{ $currentDate->departs_at->format('M j, Y') }} <i class="ri-close-line"></i></a></div>
    @endif
  </div></div>
  <form id="wl-client" onsubmit="return false"></form>

  @forelse($groups as $depId => $entries)
    @php
      $d = $entries->first()->departure;
      $left = $summary[$depId]['left'];
      $fit = $active ? $summary[$depId]['fit'] : null;
      $wanted = $entries->sum('guests');
      $days = (int) now()->startOfDay()->diffInDays($d->departs_at->copy()->startOfDay(), false);
    @endphp
    <section class="card wl-group {{ $fit ? 'has-open' : '' }}" data-wl-group>
      <header class="wl-group-head">
        <div class="wl-date {{ $days >= 0 && $days <= 7 ? 'is-soon' : '' }}" aria-hidden="true"><span>{{ $d->departs_at->format('D') }}</span><strong>{{ $d->departs_at->format('j') }}</strong><span>{{ $d->departs_at->format('M') }}</span></div>
        <div class="wl-group-main">
          <h2 class="wl-group-title">{{ $d->tour?->name ?? 'Tour #'.$d->tour_id }}</h2>
          <div class="wl-group-meta"><span><i class="ri-time-line"></i> {{ $d->departs_at->format('D, M j, Y · g:i A') }}{{ $days > 1 ? ' · in '.$days.' days' : ($days === 1 ? ' · tomorrow' : ($days === 0 ? ' · today' : '')) }}</span><span><i class="ri-team-line"></i> {{ $entries->count() }} {{ $active ? Str::plural('party', $entries->count()).' waiting' : 'closed' }}@if($active) · {{ $wanted }} {{ Str::plural('seat', $wanted) }} wanted @endif</span></div>
        </div>
        <div class="wl-group-side">
          @if($active)
            <span class="wl-seats {{ $left ? ($fit ? 'is-open' : 'is-some') : 'is-full' }}">{{ $left ? $left.' '.Str::plural('seat', $left).' free now' : 'Full' }}</span>
          @endif
          <a class="btn btn-light" href="{{ route('tour-departures.passengers', $d) }}"><i class="ri-group-line me-1"></i>Passengers</a>
        </div>
      </header>
      @if($fit && $fit->status === 'offered')
        <div class="wl-callout"><i class="ri-checkbox-circle-line"></i><span><strong>Seat offered</strong> to #{{ $entries->search($fit) + 1 }} {{ $fit->full_name }}{{ $fit->offered_at ? ' on '.$fit->offered_at->timezone($tz)->format('M j') : '' }}. Press <b>Book</b> once they confirm.</span></div>
      @elseif($fit)
        <div class="wl-callout"><i class="ri-checkbox-circle-line"></i><span><strong>A seat has opened up.</strong> Offer it to #{{ $entries->search($fit) + 1 }} {{ $fit->full_name }} ({{ $fit->guests }} {{ Str::plural('seat', $fit->guests) }}) first.</span></div>
      @elseif($active && $left)
        <div class="wl-callout is-some"><i class="ri-information-line"></i><span>{{ $left }} {{ Str::plural('seat', $left) }} free, but no party in the queue is that small yet.</span></div>
      @endif

      <ul class="wl-list">
      @foreach($entries->values() as $i => $e)
        @php
          [$sLabel, $sColour] = $statuses[$e->status] ?? [ucfirst($e->status), 'secondary'];
          $fits = $active && $e->guests <= $left;
          $since = $e->created_at->timezone($tz);
          $waNum = $wa($e->phone);
          $searchText = mb_strtolower($e->full_name.' '.$e->email.' '.$e->phone.' '.$digits($e->phone));
        @endphp
        <li class="wl-row {{ $fits ? 'is-fit' : '' }}" data-wl-row data-status="{{ $e->status }}" data-search="{{ $searchText }}">
          <div class="wl-pos" title="{{ $active ? 'Position in the queue' : '' }}">@if($active)<span>#</span>{{ $i + 1 }}@else{{ $initials($e) }}@endif</div>
          <div class="wl-main">
            <div class="wl-name-line"><span class="wl-name">{{ $e->full_name }}</span><span class="wl-party"><i class="ri-user-line"></i>{{ $e->guests }} {{ Str::plural('seat', $e->guests) }}</span><span class="badge bg-{{ $sColour }}">{{ $sLabel }}</span>@if($fits)<span class="badge bg-success bg-opacity-10 text-success">Fits now</span>@endif</div>
            <div class="wl-sub">
              @if($active)
                <span title="{{ $since->format('M j, Y g:i A') }}">Waiting since {{ $since->format('M j') }} ({{ $since->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }})</span>
              @else
                <span>Joined {{ $since->format('M j, Y') }}</span>
              @endif
              <span>{{ $e->source === 'website' ? 'From the website' : 'Added by staff' }}</span>
              @if($e->offered_at)<span class="wl-offered"><i class="ri-mail-send-line"></i> Offered {{ $e->offered_at->timezone($tz)->format('M j, g:i A') }}{{ $e->offeredBy ? ' by '.$e->offeredBy->name : '' }}</span>@endif
              @if($e->booking)<span><i class="ri-calendar-check-line"></i> <a href="{{ route('bookings.show', $e->booking) }}">{{ $e->booking->reference }}</a></span>@endif
              @if($e->removed_reason)<span>Reason: {{ $e->removed_reason }}</span>@endif
            </div>
            @if($e->note)<div class="wl-note"><i class="ri-sticky-note-line"></i> {{ Str::limit($e->note, 90) }}</div>@endif
          </div>
          <div class="wl-actions">
            <div class="wl-contact">
              @if($e->phone)<a class="btn btn-light wl-icon-btn" href="tel:{{ $digits($e->phone) }}" title="Call {{ $e->phone }}" aria-label="Call {{ $e->full_name }}"><i class="ri-phone-line"></i></a>@endif
              @if(strlen($waNum) >= 7)<a class="btn btn-light wl-icon-btn" href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener" title="WhatsApp {{ $e->phone }}" aria-label="WhatsApp {{ $e->full_name }}"><i class="ri-whatsapp-line"></i></a>@endif
              @if($e->email)<a class="btn btn-light wl-icon-btn" href="mailto:{{ $e->email }}" title="Email {{ $e->email }}" aria-label="Email {{ $e->full_name }}"><i class="ri-mail-line"></i></a>@endif
            </div>
            <div class="wl-do">
              @if($active)
                @can('edit-bookings')
                  <div class="btn-group">
                    <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">{{ $e->status === 'offered' ? 'Offer again' : 'Offer seat' }}</button>
                    <div class="dropdown-menu dropdown-menu-end">
                      <form method="post" action="{{ route('waitlist.offer', $e) }}">@csrf<button class="dropdown-item"><i class="ri-phone-line me-1"></i>I offered it by phone</button></form>
                      @if($e->email)<form method="post" action="{{ route('waitlist.offer', $e) }}" data-wl-confirm="Email the seat offer to {{ $e->email }}?">@csrf<input type="hidden" name="email" value="1"><button class="dropdown-item"><i class="ri-mail-send-line me-1"></i>Email the offer</button></form>@endif
                    </div>
                  </div>
                  @can('create-bookings')<a class="btn {{ $fits || $e->status === 'offered' ? 'btn-primary' : 'btn-light' }}" href="{{ route('bookings.create', ['waitlist' => $e->id]) }}"><i class="ri-calendar-check-line me-1"></i>Book</a>@endcan
                @endcan
              @endif
              <button type="button" class="btn btn-light" data-wl-details="#wl-d-{{ $e->id }}">Details</button>
              @if($active)
                @can('edit-bookings')
                  <div class="btn-group">
                    <button type="button" class="btn btn-light wl-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for {{ $e->full_name }}" title="Move or remove"><i class="ri-more-2-fill"></i></button>
                    <div class="dropdown-menu dropdown-menu-end">
                      <form method="post" action="{{ route('waitlist.move', $e) }}">@csrf<input type="hidden" name="direction" value="up"><button class="dropdown-item" @disabled($i === 0)><i class="ri-arrow-up-line me-1"></i>Move up the queue</button></form>
                      <form method="post" action="{{ route('waitlist.move', $e) }}">@csrf<input type="hidden" name="direction" value="down"><button class="dropdown-item" @disabled($loop->last)><i class="ri-arrow-down-line me-1"></i>Move down the queue</button></form>
                      <div class="dropdown-divider"></div>
                      <button type="button" class="dropdown-item text-danger" data-wl-remove="{{ route('waitlist.remove', $e) }}" data-what="{{ $e->full_name }} · {{ $e->guests }} {{ Str::plural('seat', $e->guests) }} · {{ $d->departs_at->format('M j') }}"><i class="ri-delete-bin-line me-1"></i>Remove from the waitlist…</button>
                    </div>
                  </div>
                @endcan
              @endif
            </div>
          </div>

          <template id="wl-d-{{ $e->id }}">
            <div class="modal-header"><h5 class="modal-title">{{ $e->full_name }}@if($active) · #{{ $i + 1 }} in the queue @endif</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
              <div class="d-flex flex-wrap gap-2 mb-3"><span class="badge bg-{{ $sColour }}">{{ $sLabel }}</span>@if($fits)<span class="badge bg-success bg-opacity-10 text-success">Their party fits the {{ $left }} free {{ Str::plural('seat', $left) }}</span>@endif</div>
              <dl class="wl-dl">
                <dt>Date</dt><dd>{{ $d->departs_at->format('D, M j, Y · g:i A') }}<span class="wl-dd-sub">{{ $d->tour?->name }}</span></dd>
                <dt>Seats wanted</dt><dd>{{ $e->guests }}@if($active)<span class="wl-dd-sub">{{ $left ? $left.' free now' : 'Date is full' }}</span>@endif</dd>
                <dt>Phone</dt><dd>@if($e->phone)<a href="tel:{{ $digits($e->phone) }}">{{ $e->phone }}</a>@else — @endif</dd>
                <dt>Email</dt><dd>@if($e->email)<a href="mailto:{{ $e->email }}">{{ $e->email }}</a>@else — @endif</dd>
                <dt>Joined</dt><dd>{{ $since->format('D, M j, Y g:i A') }}<span class="wl-dd-sub">{{ $e->source === 'website' ? 'From the website' : 'Added by staff' }}</span></dd>
                @if($e->offered_at)<dt>Seat offered</dt><dd>{{ $e->offered_at->timezone($tz)->format('D, M j, Y g:i A') }}{{ $e->offeredBy ? ' by '.$e->offeredBy->name : '' }}</dd>@endif
                @if($e->booking)<dt>Booking</dt><dd><a href="{{ route('bookings.show', $e->booking) }}">{{ $e->booking->reference }}</a></dd>@endif
                @if($e->removed_reason)<dt>Removed because</dt><dd>{{ $e->removed_reason }}</dd>@endif
                <dt>Note</dt><dd>{{ $e->note ?: '—' }}</dd>
              </dl>
            </div>
            <div class="modal-footer wl-modal-foot">
              @if($e->phone)<a class="btn btn-light" href="tel:{{ $digits($e->phone) }}"><i class="ri-phone-line me-1"></i>Call</a>@endif
              @if(strlen($waNum) >= 7)<a class="btn btn-light" href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line me-1"></i>WhatsApp</a>@endif
              @if($e->email)<a class="btn btn-light" href="mailto:{{ $e->email }}"><i class="ri-mail-line me-1"></i>Email</a>@endif
              @if($active)
                @can('create-bookings')<a class="btn btn-primary ms-auto" href="{{ route('bookings.create', ['waitlist' => $e->id]) }}"><i class="ri-calendar-check-line me-1"></i>Book</a>@endcan
              @elseif($e->booking)<a class="btn btn-primary ms-auto" href="{{ route('bookings.show', $e->booking) }}">Open booking</a>@endif
            </div>
          </template>
        </li>
      @endforeach
      </ul>
    </section>
  @empty
    <div class="card"><div class="wl-empty">
      <i class="ri-list-ordered"></i>
      @if($active)
        <strong>{{ $currentDate ? 'Nobody is waiting for this date' : 'Nobody is waiting for a seat' }}</strong>
        <span>When a date sells out, guests can join its waitlist from the tour page. They show up here in queue order, and you get an alert when a seat frees up for one of them.</span>
        <span class="wl-empty-actions">@if($currentDate)<a class="btn btn-light" href="{{ route('waitlist.index') }}">Show all dates</a>@endif @can('edit-bookings')<button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#add-waitlist"><i class="ri-user-add-line me-1"></i>Add someone</button>@endcan</span>
      @else
        <strong>{{ $currentDate ? 'No booked or removed entries for this date' : 'No booked or removed entries yet' }}</strong>
        <span>People land here once their waitlist entry is turned into a booking or removed.</span>
        <span class="wl-empty-actions"><a class="btn btn-light" href="{{ $tabUrl('active') }}">See who is waiting</a></span>
      @endif
    </div></div>
  @endforelse
  <div class="card wl-empty-filter d-none" id="wl-nomatch"><div class="wl-empty"><i class="ri-search-line"></i><strong>No one matches</strong><span>Try another name, or show all statuses.</span><span class="wl-empty-actions"><button type="button" class="btn btn-light" data-wl-reset>Clear the search</button></span></div></div>

@can('edit-bookings')
  {{-- Add someone (phone call, walk-in…). Opens again by itself after a validation error. --}}
  <div class="modal fade" id="add-waitlist" tabindex="-1" aria-labelledby="add-waitlist-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" method="post" action="{{ route('waitlist.store') }}">@csrf
      <div class="modal-header"><h5 class="modal-title" id="add-waitlist-title">Add to the waitlist</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body"><div class="row g-3">
        <div class="col-12"><label class="form-label fw-semibold" for="w-dep">Date</label><select class="form-select" id="w-dep" name="tour_departure_id" required><option value="">Choose…</option>@foreach($departures->groupBy(fn ($d) => $d->tour?->name) as $tourName => $dates)<optgroup label="{{ $tourName }}">@foreach($dates as $d)<option value="{{ $d->id }}" @selected(old('tour_departure_id', $f['departure']) == $d->id)>{{ $d->departs_at->format('D, M j, Y') }} · {{ max(0, $d->available_seats) ? max(0, $d->available_seats).' left' : 'full' }}</option>@endforeach</optgroup>@endforeach</select><div class="form-text">Any upcoming date, full or not. They join at the end of the queue.</div></div>
        <div class="col-sm-6"><label class="form-label fw-semibold" for="w-first">First name</label><input class="form-control" id="w-first" name="first_name" value="{{ old('first_name') }}" required maxlength="100"></div>
        <div class="col-sm-6"><label class="form-label fw-semibold" for="w-last">Last name</label><input class="form-control" id="w-last" name="last_name" value="{{ old('last_name') }}" required maxlength="100"></div>
        <div class="col-sm-6"><label class="form-label fw-semibold" for="w-phone">Phone</label><input class="form-control" type="tel" id="w-phone" name="phone" value="{{ old('phone') }}" maxlength="30"></div>
        <div class="col-sm-6"><label class="form-label fw-semibold" for="w-email">Email</label><input class="form-control" type="email" id="w-email" name="email" value="{{ old('email') }}"></div>
        <div class="col-12 mt-1"><div class="form-text mt-0">A phone number or an email is needed so you can offer them the seat.</div></div>
        <div class="col-sm-4"><label class="form-label fw-semibold" for="w-guests">Seats wanted</label><input class="form-control" type="number" min="1" max="50" id="w-guests" name="guests" value="{{ old('guests', 1) }}" required></div>
        <div class="col-sm-8"><label class="form-label fw-semibold" for="w-note">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="w-note" name="note" value="{{ old('note') }}" maxlength="500" placeholder="e.g. Flexible on the date"></div>
      </div></div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-add-line me-1"></i>Add to waitlist</button></div>
    </form>
  </div></div>

  {{-- Remove: a reason is required (kept on the entry). --}}
  <div class="modal fade" id="wl-remove" tabindex="-1" aria-labelledby="wl-remove-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="">@csrf
    <div class="modal-header"><h5 class="modal-title" id="wl-remove-title">Remove from the waitlist?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <p class="fw-semibold mb-3" data-what></p>
      <label class="form-label fw-semibold" for="wl-remove-reason">Reason</label>
      <div class="d-flex flex-wrap gap-1 mb-2">@foreach(['No longer interested', 'Booked another date', 'Could not reach them', 'Added by mistake'] as $reason)<button type="button" class="btn btn-light wl-reason" data-reason="{{ $reason }}">{{ $reason }}</button>@endforeach</div>
      <input class="form-control" id="wl-remove-reason" name="reason" maxlength="255" required placeholder="Pick one above or type your own">
      <p class="text-muted mt-2 mb-0">They leave the queue and move to “Booked & removed”. The people behind them move up.</p>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Remove</button></div>
  </form></div></div>
@endcan

{{-- One shared details modal: each row's "Details" button copies its <template> in. --}}
<div class="modal fade" id="wl-details" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Details
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-wl-details]');
    if (!btn) return;
    const tpl = document.querySelector(btn.dataset.wlDetails);
    const el = document.getElementById('wl-details');
    if (!tpl || !el || !window.bootstrap) return;
    el.querySelector('.modal-content').innerHTML = tpl.innerHTML;
    bootstrap.Modal.getOrCreateInstance(el).show();
  });

  // Confirm before emailing a guest.
  document.querySelectorAll('form[data-wl-confirm]').forEach(f => f.addEventListener('submit', ev => { if (!confirm(f.dataset.wlConfirm)) ev.preventDefault(); }));

  // Remove with a reason.
  const rm = document.getElementById('wl-remove');
  if (rm) {
    const form = rm.querySelector('form');
    rm.querySelectorAll('[data-reason]').forEach(b => b.addEventListener('click', () => { form.reason.value = b.dataset.reason; form.reason.focus(); }));
    document.querySelectorAll('[data-wl-remove]').forEach(btn => btn.addEventListener('click', () => {
      form.action = btn.dataset.wlRemove;
      form.reason.value = '';
      rm.querySelector('[data-what]').textContent = btn.dataset.what;
      bootstrap.Modal.getOrCreateInstance(rm).show();
      setTimeout(() => form.reason.focus(), 300);
    }));
  }

  // Re-open the add form after a validation error.
  @if($errors->any())
  const add = document.getElementById('add-waitlist');
  if (add && window.bootstrap) bootstrap.Modal.getOrCreateInstance(add).show();
  @endif

  // Status + search filter, in the page (no reload).
  const search = document.getElementById('wl-search');
  const noMatch = document.getElementById('wl-nomatch');
  function apply() {
    const q = (search ? search.value : '').trim().toLowerCase();
    const st = (document.querySelector('input[name="wl-status"]:checked') || {}).value || 'all';
    let any = false;
    document.querySelectorAll('[data-wl-group]').forEach(g => {
      let shown = 0;
      g.querySelectorAll('[data-wl-row]').forEach(r => {
        const ok = (st === 'all' || r.dataset.status === st) && (!q || r.dataset.search.includes(q));
        r.classList.toggle('d-none', !ok);
        if (ok) shown++;
      });
      g.classList.toggle('d-none', !shown);
      if (shown) any = true;
    });
    if (noMatch) noMatch.classList.toggle('d-none', any || !document.querySelector('[data-wl-group]'));
  }
  if (search) search.addEventListener('input', apply);
  document.querySelectorAll('input[name="wl-status"]').forEach(r => r.addEventListener('change', apply));
  document.querySelectorAll('[data-wl-reset]').forEach(b => b.addEventListener('click', () => { if (search) search.value = ''; const all = document.getElementById('wl-status-all'); if (all) all.checked = true; apply(); }));
});
</script>
</div></div></div>@include('admin.includes.footer')
