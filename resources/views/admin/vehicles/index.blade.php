@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-vehicles.css') }}?v={{ @filemtime(public_path('assets/css/admin-vehicles.css')) }}">
@php
  // Seating stage of a date => [label, badge tone, icon, tab label]
  $stages = [
    0 => ['No bus yet', 'danger', 'ri-error-warning-line', 'Needs a bus'],
    1 => ['Seats to assign', 'warning', 'ri-user-add-line', 'Seats to assign'],
    2 => ['Draft', 'info', 'ri-file-edit-line', 'Draft'],
    3 => ['Published', 'success', 'ri-checkbox-circle-line', 'Published'],
    4 => ['No passengers yet', 'secondary', 'ri-time-line', 'No passengers'],
  ];
  $all = request()->boolean('all');
  $stageOf = fn ($d) => \App\Http\Controllers\Admin\VehicleLayoutController::stage($d);
  $byStage = $departures ? $departures->countBy($stageOf) : collect();
  $activeLayouts = $layouts->where('is_active', true)->count();
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="vb-title"><h4>Vehicles & seating</h4><span class="text-muted">@if($departures !== null){{ $departures->count() }} upcoming {{ str('date')->plural($departures->count()) }} · @endif{{ $layouts->count() }} {{ str('template')->plural($layouts->count()) }}</span></div>
                        @can('create-vehicle-layouts')<a class="btn btn-primary" href="{{ route('vehicle-layouts.create') }}"><i class="ri-add-line me-1"></i>New template</a>@endcan
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

@if($departures !== null)
<div class="card tl-card vb-list-card" id="vb-dates"><div class="card-body p-0">
  <div class="tl-tabs-bar">
    <div class="tl-tabs" role="tablist" aria-label="Seating stage">
      <button type="button" class="tl-tab border-0 active" data-stage="" role="tab">All<span class="vb-tab-count">{{ $departures->count() }}</span></button>
      @foreach($all ? [0, 1, 2, 3, 4] : [0, 1, 2, 3] as $st)
        <button type="button" class="tl-tab border-0" data-stage="{{ $st }}" role="tab">{{ $stages[$st][3] }}<span class="vb-tab-count">{{ $byStage[$st] ?? 0 }}</span></button>
      @endforeach
    </div>
    <div class="vb-seg" role="group" aria-label="Which dates">
      <a class="{{ $all ? '' : 'active' }}" href="{{ route('vehicle-layouts.index') }}">With passengers</a>
      <a class="{{ $all ? 'active' : '' }}" href="{{ route('vehicle-layouts.index', ['all' => 1]) }}">All dates</a>
    </div>
  </div>
  @if($departures->count() > 6)
  <div class="tl-search-bar">
    <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" id="vb-q" placeholder="Filter by tour or date" aria-label="Filter by tour or date" autocomplete="off"></label>
  </div>
  @endif
  <div class="table-responsive"><table class="table table-hover align-middle tl-table mb-0">
    <thead><tr><th>Date</th><th class="d-none d-sm-table-cell">Tour</th><th class="d-none d-md-table-cell">Seated</th><th class="d-none d-lg-table-cell text-end">Buses</th><th class="d-none d-sm-table-cell">Seating</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
    @forelse($departures as $d)
      @php
        $st = $stageOf($d);
        [$label, $tone, $icon] = $stages[$st];
        $pct = $d->passengers_count ? round($d->seated_count / $d->passengers_count * 100) : 0;
        $when = $d->departs_at->isToday() ? 'today' : $d->departs_at->copy()->startOfDay()->diffForHumans(now()->startOfDay(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]);
      @endphp
      <tr data-stage="{{ $st }}" data-q="{{ strtolower($d->tour?->name.' '.$d->departs_at->format('D M j Y F')) }}">
        <td class="vb-date-cell"><div class="vb-date"><div class="vb-date-tile"><span>{{ $d->departs_at->format('M') }}</span><b>{{ $d->departs_at->format('j') }}</b></div><div class="min-w-0"><span class="fw-semibold text-nowrap">{{ $d->departs_at->format('D, M j') }}</span><span class="vb-sub text-nowrap">{{ $d->departs_at->format('g:i A') }} · {{ $when }}</span>
          <span class="d-sm-none"><span class="vb-tour d-block mt-1">{{ $d->tour?->name }}</span><span class="vb-sub"><span class="badge bg-{{ $tone }}">{{ $label }}</span> · {{ $d->seated_count }}/{{ $d->passengers_count }} seated</span></span></div></div></td>
        <td class="d-none d-sm-table-cell"><span class="vb-tour">{{ $d->tour?->name }}</span></td>
        <td class="d-none d-md-table-cell"><div class="vb-meter" title="{{ $d->seated_count }} of {{ $d->passengers_count }} passengers have a seat"><div class="progress"><div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($d->vehicles_count ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $pct }}%"></div></div><b>{{ $d->seated_count }} / {{ $d->passengers_count }}</b></div></td>
        <td class="d-none d-lg-table-cell text-end" style="font-variant-numeric: tabular-nums">{{ $d->vehicles_count }}</td>
        <td class="d-none d-sm-table-cell"><span class="badge bg-{{ $tone }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</span></td>
        <td class="text-end"><a class="btn {{ in_array($st, [0, 1, 2], true) ? 'btn-primary' : 'btn-light' }} text-nowrap" href="{{ route('seating.show', $d) }}"><span class="d-none d-sm-inline">{{ match ($st) { 0 => 'Add a bus', 1 => 'Seat people', 2 => 'Review & publish', default => 'Open' } }}</span><i class="ri-arrow-right-s-line ms-sm-1" aria-hidden="true"></i><span class="visually-hidden d-sm-none">Open seating</span></a></td>
      </tr>
    @empty
      <tr class="vb-empty-row"><td colspan="6"><i class="ri-calendar-check-line"></i>{{ $all ? 'No upcoming dates.' : 'No upcoming dates with passengers yet.' }}@unless($all) <a href="{{ route('vehicle-layouts.index', ['all' => 1]) }}">Show all dates</a>@endunless</td></tr>
    @endforelse
    @if($departures->isNotEmpty())
      <tr class="vb-empty-row d-none" id="vb-no-match"><td colspan="6"><i class="ri-checkbox-circle-line"></i>No dates in this view.</td></tr>
    @endif
    </tbody>
  </table></div>
</div></div>
@endif

<div class="vb-section-title">
  <div><h5>Seating templates</h5><p>One plan for each kind of vehicle. Add a template to a departure date to seat its passengers — the date keeps its own copy, so changing a template later never moves anyone.</p></div>
</div>
<div class="card tl-card vb-list-card"><div class="card-body p-0">
  <div class="table-responsive"><table class="table table-hover align-middle tl-table mb-0">
    <thead><tr><th>Template</th><th class="d-none d-md-table-cell">Plan</th><th class="d-none d-lg-table-cell">Vehicle type</th><th class="d-none d-sm-table-cell">Offered for new buses</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
    @forelse($layouts as $layout)
      <tr class="{{ $layout->is_active ? '' : 'vb-hidden-row' }}">
        <td>
          @can('edit-vehicle-layouts')<a class="vb-template-name" href="{{ route('vehicle-layouts.edit', $layout) }}">{{ $layout->name }}</a>@else<span class="vb-template-name">{{ $layout->name }}</span>@endcan
          <span class="vb-sub">{{ $layout->seat_count }} passenger {{ str('seat')->plural($layout->seat_count) }}<span class="d-sm-none">{{ $layout->is_active ? '' : ' · hidden' }}</span></span>
        </td>
        <td class="d-none d-md-table-cell"><span class="vb-thumb">@include('admin.vehicles._plan', ['plan' => $layout->layout, 'small' => true])</span></td>
        <td class="d-none d-lg-table-cell">{{ $layout->transportation?->name ?? '—' }}</td>
        <td class="d-none d-sm-table-cell">@if($layout->is_active)<span class="badge bg-success">Offered</span>@else<span class="badge bg-secondary">Hidden</span>@endif</td>
        <td><div class="vb-row-actions">
          @can('edit-vehicle-layouts')<a class="btn btn-light" href="{{ route('vehicle-layouts.edit', $layout) }}"><i class="ri-pencil-line me-1"></i>Edit</a>@endcan
          @can('create-vehicle-layouts')<a class="btn btn-light" href="{{ route('vehicle-layouts.create', ['copy' => $layout->id]) }}" title="Start a new template from this one" aria-label="Copy {{ $layout->name }}"><i class="ri-file-copy-line"></i><span class="d-none d-lg-inline ms-1">Copy</span></a>@endcan
          @can('delete-vehicle-layouts')<form method="post" action="{{ route('vehicle-layouts.destroy', $layout) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Remove “'.$layout->name.'”? Dates that already use it keep their seats.') }})">@csrf @method('DELETE')<button class="btn btn-light text-danger" title="Remove template" aria-label="Remove {{ $layout->name }}"><i class="ri-delete-bin-line"></i></button></form>@endcan
        </div></td>
      </tr>
    @empty
      <tr class="vb-empty-row"><td colspan="5"><i class="ri-bus-line"></i>No seating templates yet. Create one for each vehicle you use — a minivan, a sprinter, a coach.@can('create-vehicle-layouts') <a href="{{ route('vehicle-layouts.create') }}">New template</a>@endcan</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div></div>
@if($layouts->isNotEmpty() && !$activeLayouts)
  <div class="alert alert-warning mt-3"><i class="ri-error-warning-line me-1"></i>Every template is hidden, so no bus can be added to a date. Edit one and switch on “Offer this template”.</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
  const box = document.getElementById('vb-dates');
  if (!box) return;
  const rows = [...box.querySelectorAll('tbody tr[data-stage]')], q = document.getElementById('vb-q'), none = document.getElementById('vb-no-match');
  let stage = '';
  const apply = () => {
    const text = q ? q.value.trim().toLowerCase() : '';
    let shown = 0;
    rows.forEach(r => { const ok = (stage === '' || r.dataset.stage === stage) && (!text || r.dataset.q.includes(text)); r.classList.toggle('d-none', !ok); shown += ok; });
    if (none) none.classList.toggle('d-none', shown > 0);
  };
  box.querySelectorAll('[data-stage].tl-tab').forEach(t => t.addEventListener('click', () => {
    stage = t.dataset.stage;
    box.querySelectorAll('.tl-tab').forEach(x => { x.classList.toggle('active', x === t); x.setAttribute('aria-selected', x === t); });
    apply();
  }));
  if (q) q.addEventListener('input', apply);
});
</script>
</div></div></div>@include('admin.includes.footer')
