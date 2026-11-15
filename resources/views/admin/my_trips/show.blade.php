@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tour = $departure->tour;
  $office = auth()->user()->can('list-bookings');
  $pickup = request('pickup');
  $rows = $passengers->when($pickup !== null && $pickup !== '', fn ($r) => $r->filter(fn ($row) => (string) ($row['passenger']->pickup_point_id ?? 0) === $pickup))->values();
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

@if($mine->isEmpty())
  <div class="alert alert-info"><i class="ri-eye-line me-1"></i>You are viewing this trip as office staff. Assigned leaders and drivers see exactly this page.</div>
@endif

<div class="row g-3 mb-3">
  @foreach([
    ['Date', \App\Support\DepartureCalendar::range($departure), 'primary'],
    ['Departs', $departure->departs_at->format('g:i A'), 'primary'],
    ['Passengers', $passengers->count(), 'success'],
    ['Pickup stops', $stops->count(), 'secondary'],
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
            <div class="text-muted">{{ $s->role_label }}@if($s->note) · {{ $s->note }}@endif</div>
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
    <div class="card" id="roster"><div class="card-body">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Passengers <span class="text-muted fw-normal">({{ $rows->count() }}{{ $rows->count() !== $passengers->count() ? ' of '.$passengers->count() : '' }})</span></h5>
        <div class="d-flex gap-2 d-print-none">
          <form><select class="form-select" name="pickup" onchange="this.form.submit()" aria-label="Pickup stop"><option value="">All pickup stops</option>@foreach($stops as $stop)<option value="{{ $stop->id }}" @selected($pickup === (string) $stop->id)>{{ $stop->name }}</option>@endforeach @if(($byStop[0] ?? collect())->isNotEmpty())<option value="0" @selected($pickup === '0')>No pickup stop</option>@endif</select></form>
          <button class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
      </div>
      <div class="table-responsive"><table class="table table-hover crm-table mb-0">
        <thead><tr><th>#</th><th>Passenger</th><th>Pickup</th><th>Seat</th><th>Meal</th><th>Contact</th></tr></thead>
        <tbody>
        @forelse($rows as $i => $row)
          @php([$p, $b] = [$row['passenger'], $row['booking']])
          <tr>
            <td>{{ $i + 1 }}</td>
            <td><span class="fw-semibold">{{ $p->first_name }} {{ $p->last_name }}</span>@if($p->category && $p->category !== 'adult') <span class="badge rounded-pill bg-light text-body">{{ $p->category }}</span>@endif
              <span class="sub">@if($office)<a href="{{ route('bookings.show', $b) }}">{{ $b->reference }}</a>@else{{ $b->reference }}@endif @if($p->notes)· {{ $p->notes }}@endif</span></td>
            <td>{{ $p->pickupPoint?->name ?? '—' }}</td>
            <td>{{ $p->seat_number ?: '—' }}</td>
            <td>{{ $p->meal_selection ?: '—' }}</td>
            <td>@php($phone = $p->phone ?: $b->phone)
              @if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="text-nowrap">{{ $phone }}</a>@else — @endif
              @if(!$p->phone && trim($b->first_name.' '.$b->last_name) !== trim($p->first_name.' '.$p->last_name))<span class="sub">via {{ trim($b->first_name.' '.$b->last_name) }}</span>@endif</td>
          </tr>
        @empty
          <tr><td colspan="6" class="crm-empty"><i class="ri-group-line"></i>No passengers {{ $pickup !== null && $pickup !== '' ? 'at this stop' : 'on this date yet' }}.</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div></div>
  </div>
</div>
</div></div></div>@include('admin.includes.footer')
