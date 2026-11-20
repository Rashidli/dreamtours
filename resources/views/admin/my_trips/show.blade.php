@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-mytrips.css') }}?v={{ @filemtime(public_path('assets/css/admin-mytrips.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid mt-page">
@php
  $tour = $departure->tour;
  // Quick-contact helpers: tel: keeps + and digits; WhatsApp needs the country code (10-digit numbers are North American).
  $tel = fn (?string $n) => preg_replace('/[^\d+]/', '', (string) $n);
  $wa = function (?string $n) { $d = preg_replace('/\D/', '', (string) $n); return $d === '' ? null : 'https://wa.me/'.(strlen($d) === 10 ? '1'.$d : $d); };
  $mapUrl = fn ($stop) => $stop->map_url ?: 'https://www.google.com/maps/search/?api=1&query='.urlencode(trim($stop->name.', '.$stop->address, ', '));
  $officePhone = setting('phone');
  $officeWa = setting('whatsapp') ?: setting('phone_link') ?: $officePhone;
  $day = $departure->departs_at->copy()->startOfDay();
  $when = $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : ($day->isFuture() ? 'In '.$day->diffInDays(now()->startOfDay(), true).' days' : 'Past trip'));
  $notes = collect([
    'Your note from the office' => $mine->pluck('note')->filter()->unique()->implode(' · '),
    'Important notes' => $tour->important_notes,
    'Boarding rules' => $tour->boarding_rules,
    'Baggage rules' => $tour->baggage_rules,
  ])->map(fn ($v) => trim(strip_tags((string) $v)))->filter();
  $office = auth()->user()->can('list-bookings');
  $counts = \App\Support\Attendance::counts($all->pluck('passenger'));
  $alertMissing = $counts['missing'] && $when !== 'Past trip';
  $filtered = collect($f)->except('sort')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box mt-title">
                        <a class="mt-back" href="{{ route('my-trips.index') }}"><i class="ri-arrow-left-line"></i>My trips</a>
                        <h4 class="mb-0">{{ $tour->name }}@if($tour->trashed()) <span class="badge bg-secondary align-middle">Deleted tour</span>@endif</h4>
                    </div>
                </div>
            </div>

@include('admin.includes.flash')
@include('admin.includes.validation_errors')
@if($mine->isEmpty())
  <div class="alert alert-info mt-office-note"><i class="ri-eye-line me-1"></i>You are viewing this trip as office staff. Assigned leaders and drivers see exactly this page.</div>
@endif

{{-- Trip at a glance: when, how many, and the one action leaders need most (check-in). --}}
<div class="card mt-hero">
  <div class="card-body">
    <div class="mt-hero-top">
      <div>
        <div class="mt-hero-date">@if($when)<span class="badge rounded-pill {{ $when === 'Today' ? 'bg-success' : ($when === 'Past trip' ? 'bg-secondary' : 'bg-info') }}">{{ $when }}</span>@endif {{ \App\Support\DepartureCalendar::range($departure) }}</div>
        <div class="mt-hero-time"><i class="ri-time-line"></i>Departs {{ $departure->departs_at->format('g:i A') }}@if($departure->returns_at)<span class="mt-hero-return">Back about {{ $departure->returns_at->format($departure->returns_at->isSameDay($departure->departs_at) ? 'g:i A' : 'M j, g:i A') }}</span>@endif</div>
        @if($mine->isNotEmpty())<div class="mt-hero-roles">You are @foreach($mine as $a)<span class="badge bg-primary bg-opacity-10 text-primary"><i class="{{ \App\Models\DepartureStaff::ROLES[$a->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $a->role_label }}</span>@endforeach</div>@endif
      </div>
    </div>
    <div class="mt-stats">
      <a class="mt-stat" href="#roster"><span class="label">Passengers</span><span class="value">{{ $counts['expected'] }}</span></a>
      <a class="mt-stat" href="{{ route('my-trips.show', [$departure, 'attendance' => 'arrived']) }}#roster"><span class="label">Arrived</span><span class="value text-info">{{ $counts['arrived'] }}<span class="of">/ {{ $counts['expected'] }}</span></span></a>
      <a class="mt-stat {{ $alertMissing ? 'is-alert' : '' }}" href="{{ route('my-trips.show', [$departure, 'attendance' => 'missing']) }}#roster"><span class="label">Still missing</span><span class="value {{ $alertMissing ? 'text-danger' : ($counts['missing'] ? '' : 'text-success') }}">{{ $counts['missing'] }}</span></a>
    </div>
    <div class="mt-hero-actions">
      <a class="btn btn-primary btn-lg mt-big" href="#roster"><i class="ri-checkbox-multiple-line"></i>Passenger list &amp; check-in</a>
      @if($officePhone)<a class="btn btn-light btn-lg mt-big" href="tel:{{ $tel(setting('phone_link') ?: $officePhone) }}"><i class="ri-phone-line"></i>Call office</a>@endif
    </div>
    <nav class="mt-jump d-print-none" aria-label="Sections">
      <a href="#mt-pickups"><i class="ri-map-pin-2-line"></i>Pickups</a>
      <a href="#mt-team"><i class="ri-team-line"></i>Team &amp; contacts</a>
      @if($notes->isNotEmpty())<a href="#mt-notes"><i class="ri-information-line"></i>Notes</a>@endif
      @if($tour->itineraryDays->isNotEmpty())<a href="#mt-itinerary"><i class="ri-route-line"></i>Itinerary</a>@endif
      <a href="#roster"><i class="ri-group-line"></i>Passengers</a>
    </nav>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-4 mt-side">
    <div class="card mt-section" id="mt-team"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-team-line"></i></div><div><h5>Team &amp; contacts</h5><p>Tap a number to call.</p></div></div>
      @forelse($departure->staff as $s)
        <div class="mt-person">
          <span class="mt-person-icon"><i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }}"></i></span>
          <div class="mt-person-body">
            <div class="fw-semibold">{{ $s->user?->name }}@if($s->user_id === auth()->id()) <span class="text-muted fw-normal">(you)</span>@endif</div>
            <div class="text-muted">{{ $s->role_label }}@if($s->vehicle) · {{ $s->vehicle->label }}@endif @if($s->note) · {{ $s->note }}@endif</div>
            @if($s->user?->phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $s->user->phone) }}" class="d-block"><i class="ri-phone-line me-1"></i>{{ $s->user->phone }}</a>@endif
            <div class="mt-contact-more">@if($s->user?->phone && $s->user_id !== auth()->id() && ($w = $wa($s->user->phone)))<a class="btn btn-light" href="{{ $w }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i>WhatsApp</a>@endif @if($s->user?->email)<a href="mailto:{{ $s->user->email }}" class="btn btn-light text-break"><i class="ri-mail-line"></i>Email</a>@endif</div>
          </div>
        </div>
      @empty
        <p class="text-muted mb-3">Nobody is assigned yet.</p>
      @endforelse
      <div class="mt-person mt-person-office">
        <span class="mt-person-icon"><i class="ri-customer-service-2-line"></i></span>
        <div class="mt-person-body">
          <div class="fw-semibold">{{ setting('site_name', 'DreamTour') }} office</div>
          <div class="text-muted">{{ setting('working_hours') ?: 'Office' }}</div>
          <div class="mt-contact-more">@if($officePhone)<a class="btn btn-light" href="tel:{{ $tel(setting('phone_link') ?: $officePhone) }}"><i class="ri-phone-line"></i>{{ $officePhone }}</a>@endif @if($officeWa && ($w = $wa($officeWa)))<a class="btn btn-light" href="{{ $w }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i>WhatsApp</a>@endif</div>
        </div>
      </div>
      <a class="mt-emergency" href="tel:911"><i class="ri-alarm-warning-line"></i><span><b>Emergency: 911</b><span class="d-block">Police, fire, ambulance. Then call the office.</span></span></a>
    </div></div>

    <div class="card mt-section" id="mt-pickups"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-map-pin-2-line"></i></div><div><h5>Pickup stops</h5><p>In pickup order · departs {{ $departure->departs_at->format('g:i A') }}</p></div></div>
      <ol class="mt-timeline">
      @forelse($stops as $i => $stop)
        @php($n = ($byStop[$stop->id] ?? collect())->count())
        <li class="mt-stop">
          <span class="mt-stop-dot">{{ $i + 1 }}</span>
          <div class="mt-stop-body">
            <div class="mt-stop-head"><span class="mt-stop-name">{{ $stop->name }}</span><span class="badge rounded-pill {{ $n ? 'bg-info' : 'bg-light text-body' }}">{{ $n }} {{ str('passenger')->plural($n) }}</span></div>
            @if($stop->address)<div class="text-muted">{{ $stop->address }}</div>@endif
            @if($stop->instructions)<div class="mt-stop-hint"><i class="ri-information-line"></i>{{ $stop->instructions }}</div>@endif
            <div class="mt-stop-actions d-print-none"><a class="btn btn-light" href="{{ $mapUrl($stop) }}" target="_blank" rel="noopener"><i class="ri-direction-line"></i>Directions</a>@if($n)<a class="btn btn-light" href="{{ route('my-trips.show', [$departure, 'pickup' => $stop->id]) }}#roster"><i class="ri-group-line"></i>Who boards here</a>@endif</div>
          </div>
        </li>
      @empty
        <li class="text-muted">No pickup stops set for this date. Ask the office where to meet.</li>
      @endforelse
      </ol>
      @if(($byStop[0] ?? collect())->isNotEmpty())<a class="mt-warn" href="{{ route('my-trips.show', [$departure, 'pickup' => 0]) }}#roster"><i class="ri-error-warning-line"></i>{{ $byStop[0]->count() }} {{ str('passenger')->plural($byStop[0]->count()) }} without a pickup stop · show who</a>@endif
    </div></div>

    @if($notes->isNotEmpty())
    <div class="card mt-section" id="mt-notes"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-information-line"></i></div><div><h5>Important notes</h5><p>Read before the trip.</p></div></div>
      @foreach($notes as $label => $text)
        <div class="mt-note {{ $loop->first && $mine->pluck('note')->filter()->isNotEmpty() ? 'is-mine' : '' }}"><div class="fw-semibold">{{ $label }}</div><div class="mt-note-text">{{ $text }}</div></div>
      @endforeach
    </div></div>
    @endif

    @if($tour->itineraryDays->isNotEmpty())
    <div class="card mt-section" id="mt-itinerary"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-route-line"></i></div><div><h5>Itinerary</h5><p>{{ $tour->itineraryDays->unique('day_number')->count() }} {{ str('day')->plural($tour->itineraryDays->unique('day_number')->count()) }} at a glance</p></div></div>
      @foreach($tour->itineraryDays->sortBy(['day_number', 'sort_order']) as $day)
        <div class="mt-day"><span class="mt-day-num">Day {{ $day->day_number }}</span><div><div class="fw-semibold">{{ $day->title ?: 'Day '.$day->day_number }}</div>@if($day->description)<div class="text-muted">{{ str(strip_tags($day->description))->limit(220) }}</div>@endif</div></div>
      @endforeach
    </div></div>
    @endif
  </div>

  <div class="col-xl-8">
    <div class="card mt-section mt-summary"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-checkbox-multiple-line"></i></div><div><h5>Check-in by stop</h5><p>Tap a stop or a missing count to see those passengers.</p></div></div>
      @include('admin.roster._summary', ['link' => fn (array $q) => route('my-trips.show', [$departure] + $q)])
    </div></div>

    <div class="card mt-section mt-roster" id="roster"><div class="card-body">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3 mt-roster-head">
        <h5 class="mb-0">Passengers <span class="text-muted fw-normal">({{ $rows->count() }}{{ $rows->count() !== $all->count() ? ' of '.$all->count() : '' }})</span></h5>
        <div class="d-flex gap-2 d-print-none">
          <a class="btn btn-outline-primary" href="{{ route('seating.show', $departure) }}"><i class="ri-layout-grid-line me-1"></i>Seat chart</a>
          <div class="btn-group"><a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"><i class="ri-file-pdf-2-line me-1"></i>PDF</a><a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}">Excel</a></div>
          <button class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
      </div>
      <form class="row g-2 mb-3 crm-filters mt-filters d-print-none" action="{{ route('my-trips.show', $departure) }}#roster">
        <div class="col-12 col-md-4"><input class="form-control" type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search name, phone or booking" aria-label="Search"></div>
        <div class="col-6 col-md-3"><select class="form-select" name="pickup" aria-label="Pickup stop" onchange="this.form.submit()"><option value="">All stops</option>@foreach($stops as $stop)<option value="{{ $stop->id }}" @selected(($f['pickup'] ?? '') == $stop->id)>{{ $stop->name }}</option>@endforeach @if(($byStop[0] ?? collect())->isNotEmpty())<option value="0" @selected((string) ($f['pickup'] ?? '') === '0')>No pickup stop</option>@endif</select></div>
        <div class="col-6 col-md-3"><select class="form-select" name="attendance" aria-label="Attendance" onchange="this.form.submit()"><option value="">Everyone</option>@foreach(\App\Support\Roster::ATTENDANCE_FILTERS as $value => $label)<option value="{{ $value }}" @selected(($f['attendance'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="ri-search-line me-1"></i>Search</button>@if($filtered)<a class="btn btn-light" href="{{ route('my-trips.show', $departure) }}#roster" title="Show everyone" aria-label="Clear filters"><i class="ri-close-line"></i></a>@endif</div>
      </form>
      <div class="list-group list-group-flush">
      @forelse($rows as $i => $row)
        @php([$p, $b] = [$row['passenger'], $row['booking']])
        @php($phone = $p->phone ?: $b->phone)
        <div class="list-group-item mt-pax {{ $p->attendance_status === 'no_show' ? 'is-noshow' : '' }} {{ $p->attendance_status ? 'is-'.$p->attendance_status : '' }}" id="p-{{ $p->id }}">
          <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
              <span class="text-muted me-1">{{ $i + 1 }}.</span><span class="fw-semibold mt-pax-name">{{ $p->first_name }} {{ $p->last_name }}</span>@if($p->category && $p->category !== 'adult') <span class="badge rounded-pill bg-light text-body">{{ $p->category }}</span>@endif
              <div class="text-muted">
                <i class="ri-map-pin-2-line"></i> {{ $p->pickupPoint?->name ?? 'No stop' }} · Seat {{ \App\Support\Seating::label($p->departureVehicle, $p->seat_number, $departure->vehicles->count() > 1) ?? '—' }}@if($p->meal_selection) · {{ $p->meal_selection }}@endif
                · @if($office)<a href="{{ route('bookings.show', $b) }}">{{ $b->reference }}</a>@else{{ $b->reference }}@endif
              </div>
              @if($p->notes)<div class="text-muted"><i class="ri-sticky-note-line"></i> {{ $p->notes }}</div>@endif
              @if($phone)<a class="mt-tel" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><i class="ri-phone-line"></i> {{ $phone }}</a>@if(!$p->phone && trim($b->first_name.' '.$b->last_name) !== trim($p->first_name.' '.$p->last_name)) <span class="text-muted">via {{ trim($b->first_name.' '.$b->last_name) }}</span>@endif @endif
            </div>
            <div class="mt-pax-mark">@include('admin.roster._mark', ['p' => $p, 'size' => ''])</div>
          </div>
        </div>
      @empty
        <div class="crm-empty"><i class="ri-group-line"></i>{{ $all->isEmpty() ? 'No passengers on this date yet.' : 'Nobody matches.' }}</div>
      @endforelse
      </div>
    </div></div>
  </div>
</div>
</div></div></div>@include('admin.includes.footer')
