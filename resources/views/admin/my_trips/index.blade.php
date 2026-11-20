@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-mytrips.css') }}?v={{ @filemtime(public_path('assets/css/admin-mytrips.css')) }}">
@php
  // Quick-contact helpers: tel: keeps + and digits; WhatsApp needs the country code (10-digit numbers are North American).
  $tel = fn (?string $n) => preg_replace('/[^\d+]/', '', (string) $n);
  $wa = function (?string $n) { $d = preg_replace('/\D/', '', (string) $n); return $d === '' ? null : 'https://wa.me/'.(strlen($d) === 10 ? '1'.$d : $d); };
  $mapUrl = fn ($stop) => $stop->map_url ?: 'https://www.google.com/maps/search/?api=1&query='.urlencode(trim($stop->name.', '.$stop->address, ', '));
  $whenLabel = function ($d) {
      $day = $d->departs_at->copy()->startOfDay();
      if ($day->isToday()) return 'Today';
      if ($day->isTomorrow()) return 'Tomorrow';
      return $day->isFuture() ? 'In '.$day->diffInDays(now()->startOfDay(), true).' days' : null;
  };
  // Upcoming: the first trip is the "next trip" card with everything needed on the road; the rest are compact rows.
  $next = $past ? null : $trips->first();
  $later = $past ? $trips : $trips->slice(1);
  $officePhone = setting('phone');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid mt-page">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-baseline gap-2"><h4 class="mb-0">My trips</h4><span class="text-muted">{{ $trips->count() }} {{ $past ? 'past' : 'upcoming' }}</span></div>
                        @if($officePhone)<a class="btn btn-light" href="tel:{{ $tel(setting('phone_link') ?: $officePhone) }}"><i class="ri-phone-line me-1"></i>Call office</a>@endif
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<nav class="mt-seg" aria-label="Trips">
  <a class="mt-seg-item {{ $past ? '' : 'active' }}" href="{{ route('my-trips.index') }}" @if(!$past) aria-current="page" @endif><i class="ri-calendar-event-line"></i>Upcoming</a>
  <a class="mt-seg-item {{ $past ? 'active' : '' }}" href="{{ route('my-trips.index', ['show' => 'past']) }}" @if($past) aria-current="page" @endif><i class="ri-history-line"></i>Past</a>
</nav>

@if($next)
  @php
    $d = $next->first()->departure;
    $when = $whenLabel($d);
    $stops = $d->pickupPoints->isNotEmpty() ? $d->pickupPoints : ($d->tour?->pickupPoints ?? collect());
    $team = $d->staff->where('user_id', '!=', auth()->id())->unique('user_id');
    $seats = $d->bookedSeats();
  @endphp
  <div class="card mt-next">
    <div class="card-body">
      <div class="mt-next-kicker">{{ $when === 'Today' ? 'Today’s trip' : 'Next trip' }}</div>
      <div class="mt-hero-date">@if($when)<span class="badge rounded-pill {{ $when === 'Today' ? 'bg-success' : 'bg-info' }}">{{ $when }}</span>@endif {{ \App\Support\DepartureCalendar::range($d) }}</div>
      <h2 class="mt-next-title">{{ $d->tour?->name }}</h2>
      <div class="mt-next-facts">
        <span><i class="ri-time-line"></i>Departs <b>{{ $d->departs_at->format('g:i A') }}</b></span>
        <span><i class="ri-group-line"></i><b>{{ $seats }}</b> {{ str('passenger')->plural($seats) }}</span>
        <span>@foreach($next as $a)<span class="badge bg-primary bg-opacity-10 text-primary me-1"><i class="{{ \App\Models\DepartureStaff::ROLES[$a->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $a->role_label }}</span>@endforeach</span>
      </div>

      <div class="row g-3 mt-2">
        <div class="col-lg-7">
          <div class="mt-sub-title">Pickup stops</div>
          <ol class="mt-timeline">
          @forelse($stops as $stop)
            <li class="mt-stop">
              <span class="mt-stop-dot">{{ $loop->iteration }}</span>
              <div class="mt-stop-body">
                <div class="mt-stop-head"><span class="mt-stop-name">{{ $stop->name }}</span><a class="btn btn-light mt-map-btn" href="{{ $mapUrl($stop) }}" target="_blank" rel="noopener" aria-label="Directions to {{ $stop->name }}"><i class="ri-direction-line"></i>Map</a></div>
                @if($stop->address)<div class="text-muted">{{ $stop->address }}</div>@endif
                @if($stop->instructions)<div class="mt-stop-hint"><i class="ri-information-line"></i>{{ $stop->instructions }}</div>@endif
              </div>
            </li>
          @empty
            <li class="text-muted">No pickup stops set yet. Ask the office where to meet.</li>
          @endforelse
          </ol>
        </div>
        <div class="col-lg-5">
          <div class="mt-sub-title">Trip team</div>
          @forelse($team as $s)
            <div class="mt-person">
              <span class="mt-person-icon"><i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }}"></i></span>
              <div class="mt-person-body">
                <div class="fw-semibold">{{ $s->user?->name }}</div>
                <div class="text-muted">{{ $s->role_label }}</div>
                @if($s->user?->phone)<div class="mt-contact-more"><a class="btn btn-light" href="tel:{{ $tel($s->user->phone) }}"><i class="ri-phone-line"></i>Call</a>@if($w = $wa($s->user->phone))<a class="btn btn-light" href="{{ $w }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i>WhatsApp</a>@endif</div>@endif
              </div>
            </div>
          @empty
            <p class="text-muted mb-0">Only you are assigned so far.</p>
          @endforelse
        </div>
      </div>

      <a class="btn btn-primary btn-lg mt-big w-100 mt-3" href="{{ route('my-trips.show', $d) }}#roster"><i class="ri-checkbox-multiple-line"></i>Passenger list &amp; check-in</a>
      <a class="mt-next-more" href="{{ route('my-trips.show', $d) }}">Full trip details: itinerary, notes, contacts <i class="ri-arrow-right-s-line"></i></a>
    </div>
  </div>
@endif

@if($next && $later->isNotEmpty())<h5 class="mt-list-title">Later</h5>@endif
@if($later->isNotEmpty())
<div class="card mt-list"><div class="card-body p-0">
  @foreach($later as $departureId => $assignments)
    @php
      $d = $assignments->first()->departure;
      $when = $whenLabel($d);
      $team = $d->staff->where('user_id', '!=', auth()->id())->unique('user_id');
    @endphp
    <a class="mt-row {{ $past ? 'is-past' : '' }}" href="{{ route('my-trips.show', $d) }}">
      <span class="mt-cal"><span class="m">{{ $d->departs_at->format('M') }}</span><span class="d">{{ $d->departs_at->format('j') }}</span><span class="w">{{ $d->departs_at->format('D') }}</span></span>
      <span class="mt-row-body">
        <span class="mt-row-title">{{ $d->tour?->name }}</span>
        <span class="mt-row-meta">
          <span><i class="ri-time-line"></i>{{ $d->departs_at->format('g:i A') }}@if($d->returns_at && !$d->returns_at->isSameDay($d->departs_at)) · until {{ $d->returns_at->format('M j') }}@endif</span>
          <span><i class="ri-group-line"></i>{{ $d->bookedSeats() }} {{ str('passenger')->plural($d->bookedSeats()) }}</span>
          @foreach($assignments as $a)<span class="badge bg-primary bg-opacity-10 text-primary"><i class="{{ \App\Models\DepartureStaff::ROLES[$a->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $a->role_label }}</span>@endforeach
        </span>
        @if($team->isNotEmpty())<span class="mt-row-team"><i class="ri-team-line"></i>With {{ $team->map(fn ($s) => $s->user?->name)->filter()->implode(', ') }}</span>@endif
      </span>
      <span class="mt-row-end">@if($when)<span class="badge rounded-pill {{ $when === 'Today' ? 'bg-success' : 'bg-light text-body' }}">{{ $when }}</span>@endif<i class="ri-arrow-right-s-line"></i></span>
    </a>
  @endforeach
</div></div>
@endif

@if($trips->isEmpty())
  <div class="card"><div class="card-body mt-empty">
    <span class="mt-empty-icon"><i class="ri-map-2-line"></i></span>
    @if($past)
      <h5>No past trips yet</h5>
      <p>Trips you have led or driven will be listed here after their date.</p>
      <a class="btn btn-light" href="{{ route('my-trips.index') }}">See upcoming trips</a>
    @else
      <h5>No upcoming trips assigned to you</h5>
      <p>When the office assigns you to a departure as leader, driver or guide, it appears here straight away.</p>
      @if($officePhone)<a class="btn btn-light" href="tel:{{ $tel(setting('phone_link') ?: $officePhone) }}"><i class="ri-phone-line me-1"></i>Call the office · {{ $officePhone }}</a>@endif
      @can('list-bookings')<p class="mt-empty-office"><i class="ri-eye-line me-1"></i>Office staff: open any trip with <b>Trip page</b> on a tour’s departure dates to see what the leader sees.</p>@endcan
    @endif
  </div></div>
@endif
</div></div></div>@include('admin.includes.footer')
