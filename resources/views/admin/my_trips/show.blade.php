@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tour = $departure->tour;
  $office = auth()->user()->can('list-bookings');
  $counts = \App\Support\Attendance::counts($all->pluck('passenger'));
  $filtered = collect($f)->except('sort')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $tour->name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('my-trips.index') }}">My trips</a></li>
                                <li class="breadcrumb-item active">{{ $departure->departs_at->format('M j, Y') }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

@include('admin.includes.flash')
@include('admin.includes.validation_errors')
@if($mine->isEmpty())
  <div class="alert alert-info"><i class="ri-eye-line me-1"></i>You are viewing this trip as office staff. Assigned leaders and drivers see exactly this page.</div>
@endif

<div class="row g-3 mb-3">
  @foreach([
    ['Date', \App\Support\DepartureCalendar::range($departure), 'primary'],
    ['Departs', $departure->departs_at->format('g:i A'), 'primary'],
    ['Arrived', $counts['arrived'].' / '.$counts['expected'], 'info'],
    ['Still missing', $counts['missing'], $counts['missing'] ? 'danger' : 'success'],
  ] as [$label, $value, $color])
    <div class="col-6 col-xl-3"><div class="crm-tile" style="border-top:3px solid var(--bs-{{ $color }})"><div class="label">{{ $label }}</div><div class="value">{{ $value }}</div></div></div>
  @endforeach
</div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3"><i class="ri-team-line me-1"></i>Trip team</h5>
      @forelse($departure->staff as $s)
        <div class="d-flex gap-2 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
          <i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }} fs-4 text-primary"></i>
          <div>
            <div class="fw-semibold">{{ $s->user?->name }}@if($s->user_id === auth()->id()) <span class="text-muted fw-normal">(you)</span>@endif</div>
            <div class="text-muted">{{ $s->role_label }}@if($s->vehicle) · {{ $s->vehicle->label }}@endif @if($s->note) · {{ $s->note }}@endif</div>
            @if($s->user?->phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $s->user->phone) }}" class="d-block"><i class="ri-phone-line me-1"></i>{{ $s->user->phone }}</a>@endif
            @if($s->user?->email)<a href="mailto:{{ $s->user->email }}" class="d-block text-break"><i class="ri-mail-line me-1"></i>{{ $s->user->email }}</a>@endif
          </div>
        </div>
      @empty
        <p class="text-muted mb-0">Nobody is assigned yet.</p>
      @endforelse
    </div></div>

    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3"><i class="ri-map-pin-2-line me-1"></i>Pickup stops</h5>
      @forelse($stops as $i => $stop)
        @php($n = ($byStop[$stop->id] ?? collect())->count())
        <div class="d-flex gap-2 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
          <span class="badge rounded-pill bg-primary align-self-start mt-1">{{ $i + 1 }}</span>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between gap-2"><a class="fw-semibold text-body" href="{{ route('my-trips.show', [$departure, 'pickup' => $stop->id]) }}#roster">{{ $stop->name }}</a><span class="text-nowrap">{{ $n }} {{ str('passenger')->plural($n) }}</span></div>
            <div class="text-muted">{{ $stop->address }}</div>
            @if($stop->instructions)<div class="text-muted">{{ $stop->instructions }}</div>@endif
            @if($stop->map_url)<a href="{{ $stop->map_url }}" target="_blank" rel="noopener"><i class="ri-map-2-line me-1"></i>Map</a>@endif
          </div>
        </div>
      @empty
        <p class="text-muted mb-0">No pickup stops set for this date.</p>
      @endforelse
      @if(($byStop[0] ?? collect())->isNotEmpty())<p class="text-warning mt-3 mb-0"><i class="ri-error-warning-line me-1"></i>{{ $byStop[0]->count() }} without a pickup stop.</p>@endif
    </div></div>

    @if($tour->itineraryDays->isNotEmpty())
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3"><i class="ri-route-line me-1"></i>Itinerary</h5>
      @foreach($tour->itineraryDays->sortBy(['day_number', 'sort_order']) as $day)
        <div class="{{ $loop->last ? '' : 'mb-3' }}"><div class="fw-semibold">Day {{ $day->day_number }}@if($day->title) · {{ $day->title }}@endif</div>@if($day->description)<div class="text-muted">{{ str(strip_tags($day->description))->limit(220) }}</div>@endif</div>
      @endforeach
    </div></div>
    @endif
  </div>

  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3"><i class="ri-checkbox-multiple-line me-1"></i>Check-in by stop</h5>
      @include('admin.roster._summary', ['link' => fn (array $q) => route('my-trips.show', [$departure] + $q)])
    </div></div>

    <div class="card" id="roster" style="scroll-margin-top:80px"><div class="card-body">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Passengers <span class="text-muted fw-normal">({{ $rows->count() }}{{ $rows->count() !== $all->count() ? ' of '.$all->count() : '' }})</span></h5>
        <div class="d-flex gap-2 d-print-none">
          <a class="btn btn-outline-primary" href="{{ route('seating.show', $departure) }}"><i class="ri-layout-grid-line me-1"></i>Seat chart</a>
          <div class="btn-group"><a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"><i class="ri-file-pdf-2-line me-1"></i>PDF</a><a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}">Excel</a></div>
          <button class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
      </div>
      <form class="row g-2 mb-3 crm-filters d-print-none" action="{{ route('my-trips.show', $departure) }}#roster">
        <div class="col-12 col-md-4"><input class="form-control" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search name, phone or booking" aria-label="Search"></div>
        <div class="col-6 col-md-3"><select class="form-select" name="pickup" aria-label="Pickup stop" onchange="this.form.submit()"><option value="">All stops</option>@foreach($stops as $stop)<option value="{{ $stop->id }}" @selected(($f['pickup'] ?? '') == $stop->id)>{{ $stop->name }}</option>@endforeach @if(($byStop[0] ?? collect())->isNotEmpty())<option value="0" @selected((string) ($f['pickup'] ?? '') === '0')>No pickup stop</option>@endif</select></div>
        <div class="col-6 col-md-3"><select class="form-select" name="attendance" aria-label="Attendance" onchange="this.form.submit()"><option value="">Everyone</option>@foreach(\App\Support\Roster::ATTENDANCE_FILTERS as $value => $label)<option value="{{ $value }}" @selected(($f['attendance'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Go</button>@if($filtered)<a class="btn btn-light" href="{{ route('my-trips.show', $departure) }}#roster" title="Reset">✕</a>@endif</div>
      </form>
      <div class="list-group list-group-flush">
      @forelse($rows as $i => $row)
        @php([$p, $b] = [$row['passenger'], $row['booking']])
        @php($phone = $p->phone ?: $b->phone)
        <div class="list-group-item px-0 {{ $p->attendance_status === 'no_show' ? 'bg-danger bg-opacity-10' : '' }}" id="p-{{ $p->id }}" style="scroll-margin-top:80px">
          <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
              <span class="text-muted me-1">{{ $i + 1 }}.</span><span class="fw-semibold fs-6">{{ $p->first_name }} {{ $p->last_name }}</span>@if($p->category && $p->category !== 'adult') <span class="badge rounded-pill bg-light text-body">{{ $p->category }}</span>@endif
              <div class="text-muted">
                <i class="ri-map-pin-2-line"></i> {{ $p->pickupPoint?->name ?? 'No stop' }} · Seat {{ \App\Support\Seating::label($p->departureVehicle, $p->seat_number, $departure->vehicles->count() > 1) ?? '—' }}@if($p->meal_selection) · {{ $p->meal_selection }}@endif
                · @if($office)<a href="{{ route('bookings.show', $b) }}">{{ $b->reference }}</a>@else{{ $b->reference }}@endif
              </div>
              @if($p->notes)<div class="text-muted"><i class="ri-sticky-note-line"></i> {{ $p->notes }}</div>@endif
              @if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><i class="ri-phone-line"></i> {{ $phone }}</a>@if(!$p->phone && trim($b->first_name.' '.$b->last_name) !== trim($p->first_name.' '.$p->last_name)) <span class="text-muted">via {{ trim($b->first_name.' '.$b->last_name) }}</span>@endif @endif
            </div>
            <div class="text-md-end" style="min-width:min(100%,280px)">@include('admin.roster._mark', ['p' => $p, 'size' => ''])</div>
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
