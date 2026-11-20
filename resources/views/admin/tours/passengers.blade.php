@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-roster.css') }}?v={{ @filemtime(public_path('assets/css/admin-roster.css')) }}">
@php
  $tour = $departure->tour;
  // The tour may be soft-deleted (or gone): the list and check-in still work, but nothing links to its edit page.
  $tourGone = !$tour || $tour->trashed();
  $tourName = $tour?->name ?? 'Tour #'.$departure->tour_id;
  $fin = auth()->user()->can('view-financial-info');
  $counts = \App\Support\Attendance::counts($all->pluck('passenger'));
  $filtered = collect($f)->except('sort')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
  // Check-in strip: each count is also the quickest attendance filter (one tap on a phone).
  $att = $f['attendance'] ?? '';
  $attUrl = fn ($value) => request()->fullUrlWithQuery(['attendance' => $value]).'#roster';
  $sortUrl = fn ($value) => request()->fullUrlWithQuery(['sort' => $value]).'#roster';
  $sort = $f['sort'] ?? 'pickup';
  $pct = fn ($n) => $counts['expected'] ? round($n / $counts['expected'] * 100, 2) : 0;
  // "More filters" opens by itself on a phone when one of them is in use.
  $moreUsed = collect($f)->only(['meal', 'booking_status'])->filter(fn ($v) => $v !== null && $v !== '')->count() + (($f['sort'] ?? 'pickup') !== 'pickup' ? 1 : 0);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid rs-page">
  <div class="row"><div class="col-12"><div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="rs-title"><h4 class="mb-0">Passengers</h4>@if($tourGone)<span class="rs-tour">{{ $tourName }} <span class="badge rounded-pill bg-secondary ms-1">Deleted tour</span></span>@else<a class="rs-tour" href="{{ route('tours.edit', $tour) }}">{{ $tour->name }}</a>@endif</div>
    <div class="page-title-right d-none d-md-block"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item">@if($tourGone){{ $tourName }}@else<a href="{{ route('tours.edit', $tour) }}#departures">{{ $tour->name }}</a>@endif</li><li class="breadcrumb-item active">Passengers</li></ol></div>
  </div></div></div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

  <div class="rs-headline">
    <div class="rs-when">
      <span class="rs-date">{{ \App\Support\DepartureCalendar::range($departure) }}</span> <span class="text-muted">· departs {{ $departure->departs_at->format('g:i A') }}</span>
      <span class="badge rounded-pill bg-{{ $departure->is_bookable ? 'success' : 'secondary' }} ms-1">{{ $departure->status_label }}</span>
      @if($departure->staff->isNotEmpty())<span class="d-block text-muted"><i class="ri-team-line me-1"></i>{{ $departure->staff->map(fn ($s) => $s->user?->name.' ('.strtolower($s->role_label).')')->implode(', ') }}</span>@endif
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <a class="btn btn-outline-primary" href="{{ route('seating.show', $departure) }}"><i class="ri-layout-grid-line me-1"></i>Seating{{ $departure->vehicles->isNotEmpty() ? ' ('.$departure->vehicles->count().' '.str('bus')->plural($departure->vehicles->count()).')' : '' }}</a>
      <a class="btn btn-light" href="{{ route('my-trips.show', $departure) }}"><i class="ri-map-2-line me-1"></i>Leader view</a>
      @unless($tourGone)
      <a class="btn btn-light" href="{{ route('tours.edit', $tour) }}#departure-{{ $departure->id }}"><i class="ri-arrow-left-line me-1"></i>Back to dates</a>
      @endunless
    </div>
  </div>

  {{-- Check-in strip: always visible (sticky) while scrolling the list; tap a count to filter by it. --}}
  <div class="card rs-stats" role="navigation" aria-label="Check-in counts">
    <div class="rs-stats-grid">
    @foreach([
      ['', 'On the list', 'Total', $counts['expected'], 'of '.$departure->capacity.' seats · '.$departure->held_seats.' held offline', ''],
      ['arrived', 'Arrived', 'Arrived', $counts['arrived'], $counts['boarded'].' boarded', 'is-info'],
      ['missing', 'Still missing', 'Missing', $counts['missing'], 'no mark yet or not arrived', $counts['missing'] ? 'is-danger' : ($counts['expected'] ? 'is-done' : '')],
      ['no_show', 'No-show', 'No-show', $counts['no_show'], 'counted, never as missing', ''],
    ] as [$value, $label, $short, $number, $note, $tone])
      <a href="{{ $attUrl($value ?: null) }}" class="rs-stat {{ $tone }} {{ $att === $value ? 'active' : '' }}" @if($att === $value) aria-current="true" @endif>
        <span class="rs-stat-label"><span class="d-none d-md-inline">{{ $label }}</span><span class="d-md-none">{{ $short }}</span></span>
        <span class="rs-stat-value">{{ $number }}</span>
        <span class="rs-stat-note">{{ $note }}</span>
      </a>
    @endforeach
    </div>
    <div class="rs-progress" role="img" aria-label="{{ $counts['arrived'] }} of {{ $counts['expected'] }} arrived, {{ $counts['no_show'] }} no-show">
      <span class="rs-seg-boarded" style="width:{{ $pct($counts['boarded']) }}%"></span><span class="rs-seg-checked" style="width:{{ $pct($counts['arrived'] - $counts['boarded']) }}%"></span><span class="rs-seg-noshow" style="width:{{ $pct($counts['no_show']) }}%"></span>
    </div>
  </div>

  <div class="card rs-sum-card"><div class="card-body">
    <div class="rs-card-head"><h5 class="mb-0">By pickup stop</h5><span class="text-muted d-print-none">Tap a stop to see its passengers</span></div>
    @include('admin.roster._summary')
  </div></div>

  <div class="card crm-filters rs-filters d-print-none"><div class="card-body">
    <form class="row g-2 align-items-end" id="rs-filter-form">
      <div class="col-12 col-md-6 col-xl-3"><label class="form-label rs-flabel" for="r-q">Search</label><div class="rs-search"><i class="ri-search-line" aria-hidden="true"></i><input type="search" class="form-control" id="r-q" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Name, phone or booking" autocomplete="off" enterkeyhint="search"></div></div>
      <div class="col-8 col-sm-6 col-md-3 col-xl-2"><label class="form-label rs-flabel" for="r-pickup">Pickup stop</label><select class="form-select" id="r-pickup" name="pickup"><option value="">All stops</option>@foreach($stops as $s)<option value="{{ $s->id }}" @selected(($f['pickup'] ?? '') == $s->id)>{{ $s->name }}</option>@endforeach<option value="0" @selected(($f['pickup'] ?? null) === '0' || ($f['pickup'] ?? null) === 0)>No pickup stop</option></select></div>
      <div class="col-4 col-sm-6 d-md-none"><button type="button" class="btn btn-light w-100 rs-more-btn {{ $moreUsed ? 'active' : '' }}" data-bs-toggle="collapse" data-bs-target="#rs-more" aria-expanded="{{ $moreUsed ? 'true' : 'false' }}" aria-controls="rs-more"><i class="ri-equalizer-line me-1"></i>More @if($moreUsed)<span class="rs-more-count">{{ $moreUsed }}</span>@endif</button></div>
      <div class="col-12 collapse rs-more {{ $moreUsed ? 'show' : '' }}" id="rs-more"><div class="row g-2 align-items-end">
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label rs-flabel" for="r-att">Attendance</label><select class="form-select" id="r-att" name="attendance"><option value="">Everyone</option>@foreach(\App\Support\Roster::ATTENDANCE_FILTERS as $value => $label)<option value="{{ $value }}" @selected(($f['attendance'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
      @if($departure->vehicles->count() > 1)<div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label rs-flabel" for="r-bus">Bus</label><select class="form-select" id="r-bus" name="vehicle"><option value="">All buses</option>@foreach($departure->vehicles as $v)<option value="{{ $v->id }}" @selected(($f['vehicle'] ?? '') == $v->id)>{{ $v->label }}</option>@endforeach<option value="0" @selected((string) ($f['vehicle'] ?? '') === '0')>No seat yet</option></select></div>@endif
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label rs-flabel" for="r-meal">Meal</label><select class="form-select" id="r-meal" name="meal"><option value="">Any meal</option>@foreach($meals as $meal)<option @selected(($f['meal'] ?? '') === $meal)>{{ $meal }}</option>@endforeach<option value="—" @selected(($f['meal'] ?? '') === '—')>Not chosen</option></select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label rs-flabel" for="r-bs">Booking status</label><select class="form-select" id="r-bs" name="booking_status"><option value="">Any status</option>@foreach(\Illuminate\Support\Arr::except(\App\Models\Booking::STATUSES, 'cancelled') as $value => $label)<option value="{{ $value }}" @selected(($f['booking_status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-1 rs-f-sort"><label class="form-label rs-flabel" for="r-sort">Sort</label><select class="form-select" id="r-sort" name="sort">@foreach(\App\Support\Roster::SORTS as $value => $label)@continue($value === 'balance' && !$fin)<option value="{{ $value }}" @selected(($f['sort'] ?? 'pickup') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-12 rs-f-actions">
        <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Apply</button>
        @if($filtered)<a class="btn btn-light" href="{{ route('tour-departures.passengers', $departure) }}">Reset</a>@endif
        <span class="rs-f-spacer"></span>
        <div class="btn-group">
          <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"><i class="ri-file-excel-2-line me-1"></i>Excel</a>
          <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">CSV</a>
          <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"><i class="ri-file-pdf-2-line me-1"></i>PDF</a>
        </div>
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
      </div>
      </div></div>
    </form>
  </div></div>

  <div class="card rs-roster" id="roster"><div class="card-body p-0">
    <div class="rs-card-head rs-roster-head"><h5 class="mb-0">{{ $rows->count() }} {{ str('passenger')->plural($rows->count()) }}@if($rows->count() !== $all->count()) <span class="text-muted fw-normal">of {{ $all->count() }}</span>@endif</h5>@if($filtered)<a class="btn btn-link px-0 d-print-none" href="{{ route('tour-departures.passengers', $departure) }}#roster">Show everyone</a>@endif<span class="rs-live text-muted" id="rs-live" aria-live="polite"></span></div>
    <div class="table-responsive"><table class="table crm-table rs-table mb-0">
      <thead><tr><th>#</th><th><a class="rs-sort {{ $sort === 'name' ? 'active' : '' }}" href="{{ $sortUrl('name') }}">Passenger</a></th><th><a class="rs-sort {{ $sort === 'pickup' ? 'active' : '' }}" href="{{ $sortUrl('pickup') }}">Pickup</a> · <a class="rs-sort {{ $sort === 'seat' ? 'active' : '' }}" href="{{ $sortUrl('seat') }}">seat</a></th><th>Meal</th><th><a class="rs-sort {{ $sort === 'registered' ? 'active' : '' }}" href="{{ $sortUrl('registered') }}">Booking</a></th><th>Contact</th>@if($fin)<th><a class="rs-sort {{ $sort === 'balance' ? 'active' : '' }}" href="{{ $sortUrl('balance') }}">Payment</a></th>@endif<th class="rs-th-att">Attendance</th></tr></thead>
      <tbody>
      @forelse($rows as $i => $row)
        @php
          [$p, $b] = [$row['passenger'], $row['booking']];
          $phone = $p->phone ?: $b->phone;
          $wa = preg_replace('/\D+/', '', (string) ($p->phone ?: ($b->whatsapp ?: $b->phone)));
          $booker = trim($b->first_name.' '.$b->last_name);
        @endphp
        <tr id="p-{{ $p->id }}" class="rs-row is-{{ $p->attendance_status ?? 'expected' }} {{ $p->attendance_status === 'no_show' ? 'table-danger' : '' }}" data-search="{{ mb_strtolower($p->first_name.' '.$p->last_name.' '.$booker.' '.$b->reference) }}">
          <td>{{ $i + 1 }}</td>
          <td><span class="fw-semibold">{{ $p->first_name }} {{ $p->last_name }}</span>@if($p->category && $p->category !== 'adult') <span class="badge rounded-pill bg-light text-body">{{ $p->category }}</span>@endif @if($p->age)<span class="text-muted">· {{ $p->age }}</span>@endif
            @if($p->notes)<span class="sub"><i class="ri-sticky-note-line me-1"></i>{{ $p->notes }}</span>@endif</td>
          <td>{{ $p->pickupPoint?->name ?? '—' }}<span class="sub">Seat {{ \App\Support\Seating::label($p->departureVehicle, $p->seat_number, $departure->vehicles->count() > 1) ?? '—' }}</span></td>
          <td class="{{ $p->meal_selection ? '' : 'rs-none' }}">{{ $p->meal_selection ?: '—' }}</td>
          <td><a href="{{ route('bookings.show', $b) }}" class="fw-semibold">{{ $b->reference }}</a><span class="sub">{{ \App\Models\Booking::STATUSES[$b->status] ?? $b->status }} · {{ $b->created_at?->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j') }}</span></td>
          <td class="rs-contact">@if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="rs-tel text-nowrap"><i class="ri-phone-line" aria-hidden="true"></i><span>{{ $phone }}</span></a>@if(strlen($wa) >= 7)<a href="https://wa.me/{{ $wa }}" class="rs-wa" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp {{ $p->phone ? $p->first_name : $booker }}"><i class="ri-whatsapp-line" aria-hidden="true"></i><span>WhatsApp</span></a>@endif @else — @endif
            @if(!$p->phone && $booker !== trim($p->first_name.' '.$p->last_name))<span class="sub">via {{ $booker }}</span>@endif</td>
          @if($fin)<td class="rs-pay">{{ \App\Models\Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status }}@if((float) $b->outstanding_amount > 0)<span class="sub text-danger">{{ money($b->outstanding_amount, $b->currency) }} due</span>@endif</td>@endif
          <td class="rs-att">@include('admin.roster._mark', ['p' => $p])</td>
        </tr>
      @empty
        <tr class="rs-empty-row"><td colspan="{{ $fin ? 8 : 7 }}" class="crm-empty"><i class="ri-group-line"></i>{{ $all->isEmpty() ? 'No passengers on this date yet.' : 'Nobody matches these filters.' }}@if($all->isNotEmpty())<a class="btn btn-light mt-2 d-table mx-auto" href="{{ route('tour-departures.passengers', $departure) }}#roster">Show everyone</a>@endif</td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="rs-nomatch" id="rs-nomatch" hidden><i class="ri-search-line"></i>No one in this list matches “<span></span>”. Press Enter to search phones and booking numbers too.</div>
    @if($cancelled->isNotEmpty())
      <p class="text-muted rs-cancelled mb-0">Cancelled bookings (not on the list): {{ $cancelled->map(fn ($b) => $b->reference.' ('.$b->participant_count.')')->implode(', ') }}</p>
    @endif
  </div></div>
</div></div></div>
<script>
// Roster UX (no change to what is submitted): instant name filter while typing, auto-apply dropdowns,
// and the "saved" message shown as a short toast on phones.
(function () {
  var form = document.getElementById('rs-filter-form');
  if (form) form.querySelectorAll('select').forEach(function (s) { s.addEventListener('change', function () { form.submit(); }); });
  var q = document.getElementById('r-q'), live = document.getElementById('rs-live'), none = document.getElementById('rs-nomatch');
  var rows = document.querySelectorAll('#roster tr[data-search]');
  if (q && rows.length) q.addEventListener('input', function () {
    var term = q.value.trim().toLowerCase(), shown = 0;
    rows.forEach(function (r) { var hit = !term || r.dataset.search.indexOf(term) !== -1; r.hidden = !hit; if (hit) shown++; });
    live.textContent = term ? shown + ' shown' : '';
    none.hidden = !term || shown > 0;
    none.querySelector('span').textContent = q.value.trim();
  });
  var toast = document.querySelector('.rs-page > .alert-success');
  if (toast && window.matchMedia('(max-width: 767.98px)').matches) setTimeout(function () { toast.classList.remove('show'); setTimeout(function () { toast.remove(); }, 300); }, 4000);
})();
</script>
@include('admin.includes.footer')
