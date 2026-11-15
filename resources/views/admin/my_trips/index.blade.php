@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">My trips</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">My trips</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link {{ $past ? '' : 'active' }}" href="{{ route('my-trips.index') }}"><i class="ri-calendar-event-line me-1"></i>Upcoming</a></li>
  <li class="nav-item"><a class="nav-link {{ $past ? 'active' : '' }}" href="{{ route('my-trips.index', ['show' => 'past']) }}"><i class="ri-history-line me-1"></i>Past</a></li>
</ul>

<div class="row g-3">
@forelse($trips as $departureId => $assignments)
  @php($d = $assignments->first()->departure)
  <div class="col-md-6 col-xl-4">
    <div class="card h-100 mb-0"><div class="card-body d-flex flex-column">
      <div class="d-flex justify-content-between gap-2 mb-1">
        <span class="text-muted">{{ $d->departs_at->format('D, M j, Y') }} · {{ $d->departs_at->format('g:i A') }}</span>
        @if($d->departs_at->isToday())<span class="badge rounded-pill bg-success">Today</span>@elseif(!$past)<span class="badge rounded-pill bg-light text-body">{{ $d->departs_at->startOfDay()->diffForHumans(now()->startOfDay(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}</span>@endif
      </div>
      <h5 class="mb-2">{{ $d->tour?->name }}</h5>
      <div class="mb-2">@foreach($assignments as $a)<span class="badge bg-primary bg-opacity-10 text-primary me-1"><i class="{{ \App\Models\DepartureStaff::ROLES[$a->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $a->role_label }}</span>@endforeach</div>
      <p class="text-muted mb-3"><i class="ri-group-line me-1"></i>{{ $d->bookedSeats() }} {{ str('passenger')->plural($d->bookedSeats()) }}
        @if(($team = $d->staff->where('user_id', '!=', auth()->id())->unique('user_id'))->isNotEmpty())<span class="d-block"><i class="ri-team-line me-1"></i>With {{ $team->map(fn ($s) => $s->user?->name)->filter()->implode(', ') }}</span>@endif
      </p>
      <a class="btn btn-primary mt-auto" href="{{ route('my-trips.show', $d) }}"><i class="ri-arrow-right-line me-1"></i>Open trip</a>
    </div></div>
  </div>
@empty
  <div class="col-12"><div class="card"><div class="card-body crm-empty"><i class="ri-map-2-line"></i>{{ $past ? 'No past trips yet.' : 'No upcoming trips are assigned to you. When the office assigns you to a departure it appears here straight away.' }}</div></div></div>
@endforelse
</div>
</div></div></div>@include('admin.includes.footer')
