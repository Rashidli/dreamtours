@include('admin.includes.header')
@php
  $tour = $departure->tour;
  $fin = auth()->user()->can('view-financial-info');
  $counts = \App\Support\Attendance::counts($all->pluck('passenger'));
  $filtered = collect($f)->except('sort')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Passengers — {{ $tour->name }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('tours.edit', $tour) }}#departures">{{ $tour->name }}</a></li><li class="breadcrumb-item active">Passengers</li></ol></div>
  </div></div></div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <span class="fs-5 fw-semibold">{{ \App\Support\DepartureCalendar::range($departure) }}</span> <span class="text-muted">· departs {{ $departure->departs_at->format('g:i A') }}</span>
      <span class="badge rounded-pill bg-{{ $departure->is_bookable ? 'success' : 'secondary' }} ms-1">{{ $departure->status_label }}</span>
      @if($departure->staff->isNotEmpty())<span class="d-block text-muted"><i class="ri-team-line me-1"></i>{{ $departure->staff->map(fn ($s) => $s->user?->name.' ('.strtolower($s->role_label).')')->implode(', ') }}</span>@endif
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <a class="btn btn-outline-primary" href="{{ route('seating.show', $departure) }}"><i class="ri-layout-grid-line me-1"></i>Seating{{ $departure->vehicles->isNotEmpty() ? ' ('.$departure->vehicles->count().' '.str('bus')->plural($departure->vehicles->count()).')' : '' }}</a>
      <a class="btn btn-light" href="{{ route('my-trips.show', $departure) }}"><i class="ri-map-2-line me-1"></i>Leader view</a>
      <a class="btn btn-light" href="{{ route('tours.edit', $tour) }}#departure-{{ $departure->id }}"><i class="ri-arrow-left-line me-1"></i>Back to dates</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    @foreach([
      ['On the list', $counts['expected'], 'of '.$departure->capacity.' seats · '.$departure->held_seats.' held offline', 'secondary'],
      ['Arrived', $counts['arrived'], $counts['boarded'].' boarded', 'info'],
      ['Still missing', $counts['missing'], 'no mark yet or not arrived', 'danger'],
      ['No-show', $counts['no_show'], 'counted, never as missing', 'dark'],
    ] as [$label, $value, $note, $color])
      <div class="col-sm-6 col-xl-3"><div class="crm-tile" style="border-top:3px solid var(--bs-{{ $color }})"><div class="label">{{ $label }}</div><div class="value">{{ $value }}</div><div class="note">{{ $note }}</div></div></div>
    @endforeach
  </div>

  <div class="card"><div class="card-body">
    <h5 class="card-title mb-3">By pickup stop</h5>
    @include('admin.roster._summary')
  </div></div>

  <div class="card crm-filters d-print-none"><div class="card-body">
    <form class="row g-3 align-items-end">
      <div class="col-md-6 col-xl-3"><label class="form-label" for="r-q">Search</label><input class="form-control" id="r-q" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Name, phone or booking"></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="r-pickup">Pickup stop</label><select class="form-select" id="r-pickup" name="pickup"><option value="">All stops</option>@foreach($stops as $s)<option value="{{ $s->id }}" @selected(($f['pickup'] ?? '') == $s->id)>{{ $s->name }}</option>@endforeach<option value="0" @selected(($f['pickup'] ?? null) === '0' || ($f['pickup'] ?? null) === 0)>No pickup stop</option></select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="r-att">Attendance</label><select class="form-select" id="r-att" name="attendance"><option value="">Everyone</option>@foreach(\App\Support\Roster::ATTENDANCE_FILTERS as $value => $label)<option value="{{ $value }}" @selected(($f['attendance'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
      @if($departure->vehicles->count() > 1)<div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="r-bus">Bus</label><select class="form-select" id="r-bus" name="vehicle"><option value="">All buses</option>@foreach($departure->vehicles as $v)<option value="{{ $v->id }}" @selected(($f['vehicle'] ?? '') == $v->id)>{{ $v->label }}</option>@endforeach<option value="0" @selected((string) ($f['vehicle'] ?? '') === '0')>No seat yet</option></select></div>@endif
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="r-meal">Meal</label><select class="form-select" id="r-meal" name="meal"><option value="">Any meal</option>@foreach($meals as $meal)<option @selected(($f['meal'] ?? '') === $meal)>{{ $meal }}</option>@endforeach<option value="—" @selected(($f['meal'] ?? '') === '—')>Not chosen</option></select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="r-bs">Booking status</label><select class="form-select" id="r-bs" name="booking_status"><option value="">Any status</option>@foreach(\Illuminate\Support\Arr::except(\App\Models\Booking::STATUSES, 'cancelled') as $value => $label)<option value="{{ $value }}" @selected(($f['booking_status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-1"><label class="form-label" for="r-sort">Sort</label><select class="form-select" id="r-sort" name="sort">@foreach(\App\Support\Roster::SORTS as $value => $label)@continue($value === 'balance' && !$fin)<option value="{{ $value }}" @selected(($f['sort'] ?? 'pickup') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
        <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Apply</button>
        @if($filtered)<a class="btn btn-light" href="{{ route('tour-departures.passengers', $departure) }}">Reset</a>@endif
        <div class="btn-group">
          <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"><i class="ri-file-excel-2-line me-1"></i>Excel</a>
          <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">CSV</a>
          <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"><i class="ri-file-pdf-2-line me-1"></i>PDF</a>
        </div>
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
      </div>
    </form>
  </div></div>

  <div class="card" id="roster"><div class="card-body">
    <h5 class="card-title mb-3">{{ $rows->count() }} {{ str('passenger')->plural($rows->count()) }}@if($rows->count() !== $all->count()) <span class="text-muted fw-normal">of {{ $all->count() }}</span>@endif</h5>
    <div class="table-responsive"><table class="table crm-table mb-0">
      <thead><tr><th>#</th><th>Passenger</th><th>Pickup · seat</th><th>Meal</th><th>Booking</th><th>Contact</th>@if($fin)<th>Payment</th>@endif<th style="min-width:250px">Attendance</th></tr></thead>
      <tbody>
      @forelse($rows as $i => $row)
        @php([$p, $b] = [$row['passenger'], $row['booking']])
        <tr id="p-{{ $p->id }}" class="{{ $p->attendance_status === 'no_show' ? 'table-danger' : '' }}" style="scroll-margin-top:90px">
          <td>{{ $i + 1 }}</td>
          <td><span class="fw-semibold">{{ $p->first_name }} {{ $p->last_name }}</span>@if($p->category && $p->category !== 'adult') <span class="badge rounded-pill bg-light text-body">{{ $p->category }}</span>@endif @if($p->age)<span class="text-muted">· {{ $p->age }}</span>@endif
            @if($p->notes)<span class="sub"><i class="ri-sticky-note-line me-1"></i>{{ $p->notes }}</span>@endif</td>
          <td>{{ $p->pickupPoint?->name ?? '—' }}<span class="sub">Seat {{ \App\Support\Seating::label($p->departureVehicle, $p->seat_number, $departure->vehicles->count() > 1) ?? '—' }}</span></td>
          <td>{{ $p->meal_selection ?: '—' }}</td>
          <td><a href="{{ route('bookings.show', $b) }}" class="fw-semibold">{{ $b->reference }}</a><span class="sub">{{ \App\Models\Booking::STATUSES[$b->status] ?? $b->status }} · {{ $b->created_at?->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j') }}</span></td>
          <td>@php($phone = $p->phone ?: $b->phone)@if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="text-nowrap">{{ $phone }}</a>@else — @endif
            @if(!$p->phone && trim($b->first_name.' '.$b->last_name) !== trim($p->first_name.' '.$p->last_name))<span class="sub">via {{ trim($b->first_name.' '.$b->last_name) }}</span>@endif</td>
          @if($fin)<td>{{ \App\Models\Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status }}@if((float) $b->outstanding_amount > 0)<span class="sub text-danger">{{ money($b->outstanding_amount, $b->currency) }} due</span>@endif</td>@endif
          <td>@include('admin.roster._mark', ['p' => $p])</td>
        </tr>
      @empty
        <tr><td colspan="{{ $fin ? 8 : 7 }}" class="crm-empty"><i class="ri-group-line"></i>{{ $all->isEmpty() ? 'No passengers on this date yet.' : 'Nobody matches these filters.' }}</td></tr>
      @endforelse
      </tbody>
    </table></div>
    @if($cancelled->isNotEmpty())
      <p class="text-muted mt-3 mb-0">Cancelled bookings (not on the list): {{ $cancelled->map(fn ($b) => $b->reference.' ('.$b->participant_count.')')->implode(', ') }}</p>
    @endif
  </div></div>
</div></div></div>
@include('admin.includes.footer')
