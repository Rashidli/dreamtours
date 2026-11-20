@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-dashboard.css') }}?v={{ @filemtime(public_path('assets/css/admin-dashboard.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $user = auth()->user();
  $hour = (int) now($tz)->format('G');
  $money = $user->can('view-financial-info');
  // Tour names are often typed in capitals; show them in a readable case on the dashboard.
  $niceName = function (?string $name, int $limit = 60) {
      $name = trim((string) $name);
      if ($name !== '' && $name === mb_strtoupper($name)) {
          $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
      }
      return \Illuminate\Support\Str::limit($name, $limit);
  };
  $initials = fn (?string $n) => collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
  // Tiles from App\Support\Dashboard: [label, value, note, colour, icon, url].
  // Red / amber ones with something in them become "needs attention" actions; the rest are figures.
  $tiles = collect($dash['tiles']);
  $attention = $tiles->filter(fn ($t) => in_array($t[3], ['danger', 'warning'], true) && $t[1] > 0)->values();
  $figures = $tiles->reject(fn ($t) => in_array($t[3], ['danger', 'warning', 'secondary'], true))->values();
  if ($dash['payments'] !== null) {
      $figures->push(['Received · 7 days', money($dash['payments']['recentTotal']), $dash['payments']['recent']->count().' confirmed '.str('payment')->plural($dash['payments']['recent']->count()), 'success', 'ri-money-dollar-circle-line', $dash['payments']['recentUrl']]);
  }
  $quiet = $tiles->filter(fn ($t) => in_array($t[3], ['danger', 'warning', 'secondary'], true) && !$t[1])->values();
  $stageMap = [0 => ['No bus yet', 'danger'], 1 => ['Seats to assign', 'warning'], 2 => ['Seating draft', 'warning'], 3 => ['Seating published', 'success'], 4 => ['—', 'secondary']];
@endphp

<div class="db-head">
  <div>
    <h1 class="db-title">Good {{ $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening') }}, {{ str($user->name)->before(' ') }}</h1>
    <p class="db-sub">{{ now($tz)->format('l, F j, Y') }} · Toronto time</p>
  </div>
  <div class="db-actions">
    <a class="btn btn-light" href="{{ route('front.home') }}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> Open website</a>
    @if(Route::has('bookings.create'))@can('create-bookings')<a class="btn btn-light" href="{{ route('bookings.create') }}"><i class="ri-calendar-check-line"></i> New booking</a>@endcan @endif
    @can('create-leads')<a class="btn btn-primary" href="{{ route('leads.create') }}"><i class="ri-add-line"></i> New lead</a>@endcan
  </div>
</div>
@include('admin.includes.flash')

{{-- What needs someone today. Every line opens exactly those records (CRM §2). --}}
@if($tiles->isNotEmpty())
<div class="card db-attention">
  <div class="db-card-head"><h2 class="db-card-title"><i class="ri-flashlight-line"></i> Needs attention</h2>@if($attention->isEmpty())<span class="badge bg-success">All caught up</span>@else<span class="badge bg-danger">{{ $attention->count() }}</span>@endif</div>
  @forelse($attention as [$label, $value, $note, $colour, $icon, $url])
    <a href="{{ $url }}" class="db-todo db-todo-{{ $colour }}">
      <span class="db-todo-icon"><i class="{{ $icon }}"></i></span>
      <span class="db-todo-text"><strong>{{ $value }} {{ mb_strtolower($label) }}</strong><span>{{ $note }}</span></span>
      <span class="btn btn-light btn-sm db-todo-go">Review <i class="ri-arrow-right-s-line"></i></span>
    </a>
  @empty
    <div class="db-clear"><i class="ri-checkbox-circle-line"></i><span><strong>Nothing urgent right now.</strong> No overdue follow-ups, balances, payments to verify or failed emails.</span></div>
  @endforelse
  @if($quiet->isNotEmpty() && $attention->isNotEmpty())
    <div class="db-quiet">@foreach($quiet as [$label])<span><i class="ri-check-line"></i> {{ $label }}: none</span>@endforeach</div>
  @endif
</div>
@endif

@if($figures->isNotEmpty())
<div class="card db-stats" style="--db-cols: {{ min(4, $figures->count()) }}">
  @foreach($figures as [$label, $value, $note, $colour, $icon, $url])
    <a href="{{ $url }}" class="db-stat">
      <span class="db-stat-label"><i class="{{ $icon }}"></i> {{ $label }}</span>
      <span class="db-stat-value">{{ $value }}</span>
      <span class="db-stat-note">{{ $note }}</span>
    </a>
  @endforeach
</div>
@endif

<div class="row g-3">
  @if($dash['trips'] !== null)
  <div class="col-xl-8">
    <div class="card db-trips" id="trips">
      <div class="db-card-head">
        <h2 class="db-card-title"><i class="ri-bus-line"></i> Upcoming trips <span class="text-muted fw-normal">· next {{ \App\Support\Dashboard::TRIP_DAYS }} days</span></h2>
        @can('list-vehicle-layouts')<a href="{{ route('vehicle-layouts.index') }}" class="btn btn-light btn-sm"><i class="ri-layout-grid-line"></i> Seating overview</a>@endcan
      </div>
      @forelse($dash['trips'] as $d)
        @php
          // Seats taken = booked passengers + seats held offline, so the bar matches "left".
          $taken = max(0, $d->capacity - $d->available_seats);
          $pct = $d->capacity ? min(100, round($taken / $d->capacity * 100)) : 0;
          $needsLeader = $d->passengers_count > 0 && $d->departs_at->lt(now()->addDays(7));
          [$sLabel, $sColour] = $stageMap[\App\Http\Controllers\Admin\VehicleLayoutController::stage($d)];
          $when = $d->departs_at->isToday() ? 'Today' : ($d->departs_at->isTomorrow() ? 'Tomorrow' : 'in '.$d->departs_at->startOfDay()->diffInDays(now()->startOfDay(), true).' days');
          $leaders = $d->staff->map(fn ($s) => $s->user?->name)->filter()->unique()->implode(', ');
        @endphp
        <div class="db-trip">
          <div class="db-date {{ $d->departs_at->isToday() || $d->departs_at->isTomorrow() ? 'is-soon' : '' }}" aria-hidden="true"><span>{{ $d->departs_at->format('D') }}</span><strong>{{ $d->departs_at->format('j') }}</strong><span>{{ $d->departs_at->format('M') }}</span></div>
          <div class="db-trip-main">
            <a href="{{ route('tour-departures.passengers', $d) }}" class="db-trip-name">{{ $niceName($d->tour?->name) ?: 'Tour #'.$d->tour_id.' (no name)' }}</a>
            <div class="db-trip-meta"><span><i class="ri-time-line"></i> {{ $d->departs_at->format('g:i A') }} · {{ $when }}</span>@if($leaders)<span><i class="ri-flag-line"></i> {{ $leaders }}</span>@elseif($needsLeader)<span class="text-warning fw-semibold"><i class="ri-flag-line"></i> No leader yet</span>@else<span><i class="ri-flag-line"></i> No leader yet</span>@endif</div>
            <div class="db-occ">
              <div class="db-bar"><div class="db-bar-fill {{ $pct >= 90 ? 'is-full' : ($pct >= 70 ? 'is-high' : '') }}" style="width: {{ $pct }}%"></div></div>
              <span class="db-occ-text" title="{{ $d->passengers_count }} booked{{ $taken > $d->passengers_count ? ', '.($taken - $d->passengers_count).' held offline' : '' }}"><strong>{{ $taken }}</strong> / {{ $d->capacity }} taken · <span class="{{ $d->available_seats ? '' : 'text-danger fw-semibold' }}">{{ $d->available_seats ? $d->available_seats.' left' : 'Full' }}</span></span>
            </div>
          </div>
          <div class="db-trip-side">
            @if($d->passengers_count)<span class="badge bg-{{ $sColour }}">{{ $sLabel }}</span>@endif
            @if($money && $d->unpaid_count)<a class="db-unpaid" href="{{ route('payments.index', ['tab' => 'outstanding', 'trip_from' => $d->departs_at->toDateString(), 'trip_to' => $d->departs_at->toDateString(), 'tour' => $d->tour_id]) }}"><i class="ri-error-warning-line"></i> {{ $d->unpaid_count }} unpaid</a>@endif
            <div class="db-trip-btns">
              <a class="btn btn-light btn-sm" href="{{ route('tour-departures.passengers', $d) }}" title="Passenger list" aria-label="Passenger list"><i class="ri-group-line"></i></a>
              @if(Route::has('seating.show'))<a class="btn btn-light btn-sm" href="{{ route('seating.show', $d) }}" title="Seating" aria-label="Seating"><i class="ri-layout-grid-line"></i></a>@endif
            </div>
          </div>
        </div>
      @empty
        <div class="db-empty"><i class="ri-calendar-line"></i><strong>No departures in the next {{ \App\Support\Dashboard::TRIP_DAYS }} days</strong>@can('list-tours')<span><a href="{{ route('tours.index', ['when' => 'upcoming']) }}">See all upcoming dates</a></span>@endcan</div>
      @endforelse
    </div>
  </div>
  @endif

  <div class="{{ $dash['trips'] !== null ? 'col-xl-4' : 'col-xl-6' }} db-side">
    @if($dash['followUps'] !== null)
    <div class="card">
      <div class="db-card-head"><h2 class="db-card-title"><i class="ri-alarm-line"></i> Follow-ups due</h2><a href="{{ route('leads.index', ['view' => 'list', 'due' => 1]) }}" class="db-link">See all</a></div>
      @forelse($dash['followUps'] as $lead)
        @php
          $due = $lead->next_follow_up_at->timezone($tz);
        @endphp
        <a href="{{ route('leads.show', $lead) }}" class="db-row">
          <span class="db-avatar">{{ $initials($lead->full_name) }}</span>
          <span class="db-row-main"><strong>{{ $lead->full_name }}</strong><span>{{ $lead->tour?->name ? $niceName($lead->tour->name, 34) : ($lead->follow_up_note ?: 'Follow up') }}</span></span>
          <span class="db-row-end"><span class="text-danger">{{ $due->isToday() ? $due->format('g:i A') : $due->format('M j') }}</span><span>{{ $lead->assignedUser?->name ? str($lead->assignedUser->name)->before(' ') : 'Unassigned' }}</span></span>
        </a>
      @empty
        <div class="db-empty db-empty-sm"><i class="ri-check-double-line"></i><strong>Nothing due</strong><span>Every lead has a plan.</span></div>
      @endforelse
    </div>
    @endif

    @if($dash['payments'] !== null)
    <div class="card">
      <div class="db-card-head"><h2 class="db-card-title"><i class="ri-money-dollar-circle-line"></i> Recent payments</h2><a href="{{ $dash['payments']['recentUrl'] }}" class="db-link">{{ money($dash['payments']['recentTotal']) }} · 7 days</a></div>
      @forelse($dash['payments']['recent'] as $p)
        <a href="{{ route('bookings.show', $p->booking_id) }}" class="db-row">
          <span class="db-avatar db-avatar-money"><i class="ri-arrow-down-line"></i></span>
          <span class="db-row-main"><strong>{{ trim($p->booking?->first_name.' '.$p->booking?->last_name) ?: $p->booking?->reference }}</strong><span>{{ $p->booking?->reference }} · {{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</span></span>
          <span class="db-row-end"><strong>{{ money($p->amount, $p->currency) }}</strong><span>{{ $p->paid_on->format('M j') }}</span></span>
        </a>
      @empty
        <div class="db-empty db-empty-sm"><i class="ri-inbox-line"></i><strong>No payments this week</strong><span>Confirmed payments show up here.</span></div>
      @endforelse
    </div>
    @endif
  </div>

  {{-- Website content shortcuts, each only for people who can manage it. --}}
  @php
    $content = array_filter([
      $user->can('list-tours') ? ['Tours', $stats['tours'], $stats['published_tours'].' published', route('tours.index'), 'ri-suitcase-line'] : null,
      $user->can('list-pages') ? ['Pages', $stats['pages'], 'Pages & SEO', route('cms-pages.index'), 'ri-pages-line'] : null,
      $user->can('list-gallery-albums') ? ['Gallery', $stats['gallery_albums'], 'albums', route('domain-content.index', 'gallery-albums'), 'ri-gallery-line'] : null,
      $user->can('list-visa-services') ? ['Visa services', $stats['visa_services'], 'countries', route('domain-content.index', 'visa-services'), 'ri-passport-line'] : null,
      $user->can('list-contacts') ? ['Contact requests', $stats['contacts'], $stats['unread_contacts'].' unread', route('contacts.index'), 'ri-mail-line'] : null,
      $user->can('list-users') ? ['Team', $stats['users'], 'staff accounts', route('users.index'), 'ri-team-line'] : null,
    ]);
  @endphp
  @if($content)
  <div class="col-12">
    <div class="card db-content">
      <div class="db-card-head"><h2 class="db-card-title"><i class="ri-layout-masonry-line"></i> Website & content</h2></div>
      <div class="db-content-grid">
        @foreach($content as [$label, $value, $note, $url, $icon])
          <a href="{{ $url }}" class="db-content-item"><span class="db-content-icon"><i class="{{ $icon }}"></i></span><span><strong>{{ $label }}</strong><span>{{ $value }} · {{ $note }}</span></span></a>
        @endforeach
      </div>
    </div>
  </div>
  @endif
</div>
</div></div></div>@include('admin.includes.footer')
