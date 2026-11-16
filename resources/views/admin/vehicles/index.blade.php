@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Vehicles & seating</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Vehicles & seating</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

@if($departures !== null)
@php
  $stages = [0 => ['No bus yet', 'danger', 'ri-error-warning-line'], 1 => ['Seats to assign', 'warning', 'ri-user-add-line'], 2 => ['Draft', 'warning', 'ri-draft-line'], 3 => ['Published', 'success', 'ri-checkbox-circle-line'], 4 => ['No passengers yet', 'secondary', 'ri-time-line']];
  $byStage = $departures->countBy(fn ($d) => \App\Http\Controllers\Admin\VehicleLayoutController::stage($d));
@endphp
<div class="row g-3 mb-3">
  @foreach([0, 1, 2, 3] as $st)
    <div class="col-6 col-xl-3"><div class="crm-tile" style="border-top:3px solid var(--bs-{{ $stages[$st][1] }})"><div class="label"><i class="{{ $stages[$st][2] }} me-1"></i>{{ $stages[$st][0] }}</div><div class="value">{{ $byStage[$st] ?? 0 }}</div><div class="note">upcoming {{ str('date')->plural($byStage[$st] ?? 0) }}</div></div></div>
  @endforeach
</div>
<div class="card"><div class="card-body">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h5 class="card-title mb-0">Upcoming departures</h5>
    <div class="btn-group">
      <a class="btn btn-sm {{ request()->boolean('all') ? 'btn-outline-primary' : 'btn-primary' }}" href="{{ route('vehicle-layouts.index') }}">With passengers</a>
      <a class="btn btn-sm {{ request()->boolean('all') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('vehicle-layouts.index', ['all' => 1]) }}">All dates</a>
    </div>
  </div>
  <div class="table-responsive"><table class="table table-hover crm-table mb-0">
    <thead><tr><th>Date</th><th>Tour</th><th class="text-end">Seated</th><th class="text-end">Buses</th><th>Seating</th><th></th></tr></thead>
    <tbody>
    @forelse($departures as $d)
      @php([$label, $colour, $icon] = $stages[\App\Http\Controllers\Admin\VehicleLayoutController::stage($d)])
      <tr>
        <td class="text-nowrap"><span class="fw-semibold">{{ $d->departs_at->format('D, M j, Y') }}</span><span class="sub">{{ $d->departs_at->format('g:i A') }} · {{ $d->departs_at->isToday() ? 'today' : $d->departs_at->startOfDay()->diffForHumans(now()->startOfDay(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}</span></td>
        <td>{{ $d->tour?->name }}</td>
        <td class="amount fw-normal {{ $d->passengers_count && $d->seated_count < $d->passengers_count ? 'text-danger' : '' }}">{{ $d->seated_count }} / {{ $d->passengers_count }}</td>
        <td class="amount fw-normal">{{ $d->vehicles_count }}</td>
        <td><span class="badge rounded-pill bg-{{ $colour }} {{ $colour === 'warning' ? 'text-dark' : '' }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</span></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('seating.show', $d) }}"><i class="ri-layout-grid-line"></i> Open seating</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="crm-empty"><i class="ri-calendar-check-line"></i>{{ request()->boolean('all') ? 'No upcoming dates.' : 'No upcoming dates with passengers yet.' }}</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div></div>
<h5 class="mt-4 mb-2">Seating templates</h5>
@endif

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">Seating templates for each kind of vehicle. Add one to a departure date to seat its passengers; the date keeps its own copy, so changing a template later never moves anyone.</p>
  @can('create-vehicle-layouts')<a class="btn btn-primary text-nowrap" href="{{ route('vehicle-layouts.create') }}"><i class="ri-add-line me-1"></i>New template</a>@endcan
</div>

<div class="row g-3">
@forelse($layouts as $layout)
  <div class="col-sm-6 col-xl-4">
    <div class="card h-100 mb-0 {{ $layout->is_active ? '' : 'opacity-75' }}"><div class="card-body d-flex gap-3">
      <div class="flex-shrink-0">@include('admin.vehicles._plan', ['plan' => $layout->layout, 'small' => true])</div>
      <div class="d-flex flex-column flex-grow-1">
        <h5 class="mb-1">{{ $layout->name }}</h5>
        <div class="text-muted mb-2">{{ $layout->seat_count }} passenger seats{{ $layout->transportation ? ' · '.$layout->transportation->name : '' }}@unless($layout->is_active) · <span class="badge bg-light text-muted">hidden</span>@endunless</div>
        <div class="mt-auto d-flex flex-wrap gap-1">
          @can('edit-vehicle-layouts')<a class="btn btn-sm btn-outline-primary" href="{{ route('vehicle-layouts.edit', $layout) }}"><i class="ri-pencil-line"></i> Edit</a>@endcan
          @can('create-vehicle-layouts')<a class="btn btn-sm btn-light" href="{{ route('vehicle-layouts.create', ['copy' => $layout->id]) }}"><i class="ri-file-copy-line"></i> Copy</a>@endcan
          @can('delete-vehicle-layouts')<form method="post" action="{{ route('vehicle-layouts.destroy', $layout) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Remove “'.$layout->name.'”? Dates that already use it keep their seats.') }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Remove"><i class="ri-delete-bin-line"></i></button></form>@endcan
        </div>
      </div>
    </div></div>
  </div>
@empty
  <div class="col-12"><div class="card"><div class="card-body crm-empty"><i class="ri-bus-2-line"></i>No templates yet.</div></div></div>
@endforelse
</div>
</div></div></div>@include('admin.includes.footer')
