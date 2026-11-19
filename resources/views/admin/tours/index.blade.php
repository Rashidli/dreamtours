@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  // Query-string helpers for the Excel-style header menus and the active-filter chips.
  $query = collect(request()->query())->except('page');
  $urlWith = fn (array $changes) => route('tours.index', collect($query)->merge($changes)->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->all());
  $without = fn (string $key, $value = null) => route('tours.index', collect($query)->map(function ($v, $k) use ($key, $value) {
      if ($k !== $key) return $v;
      return $value === null ? null : array_values(array_filter((array) $v, fn ($x) => (string) $x !== (string) $value));
  })->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->all());
  $sortUrl = fn (string $column, string $direction) => $urlWith(['sort' => $column, 'dir' => $direction]);
  $headSort = fn (string $column) => $urlWith(['sort' => $column, 'dir' => ($sort === $column && $dir === 'asc') ? 'desc' : 'asc']);
  $sortIcon = fn (string $column) => $sort === $column ? ($dir === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line') : 'ri-arrow-up-down-line';
  $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => now()->startOfYear()->month($m)->format('F')]);
  $when = request('when', 'all');
  $statusColors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
  $destinationNames = $destinations->pluck('name', 'id')->put(0, 'No destination');
  $categoryNames = $categories->pluck('name', 'id')->put(0, 'No category');

  // Active filters as removable chips.
  $chips = collect();
  if (request()->filled('q')) $chips->push(['Search: “'.request('q').'”', $without('q')]);
  foreach ($picked['status'] as $v) $chips->push(['Status: '.($statusLabels[$v] ?? $v), $without('status', $v)]);
  foreach ($picked['destination_id'] as $v) $chips->push(['Destination: '.($destinationNames[$v] ?? '#'.$v), $without('destination_id', $v)]);
  foreach ($picked['category_id'] as $v) $chips->push(['Category: '.($categoryNames[$v] ?? '#'.$v), $without('category_id', $v)]);
  if (request()->boolean('home')) $chips->push(['On the home page', $without('home')]);
  if (request()->boolean('carousel')) $chips->push(['In the carousel', $without('carousel')]);
  if (in_array($when, ['upcoming', 'past', 'none'], true)) $chips->push([['upcoming' => 'Upcoming dates', 'past' => 'Past dates', 'none' => 'No dates'][$when], $without('when')]);
  if (request()->filled('date_from')) $chips->push(['From '.\Illuminate\Support\Carbon::parse(request('date_from'))->format('M j, Y'), $without('date_from')]);
  if (request()->filled('date_to')) $chips->push(['To '.\Illuminate\Support\Carbon::parse(request('date_to'))->format('M j, Y'), $without('date_to')]);
  if (request()->filled('date')) $chips->push(['On '.\Illuminate\Support\Carbon::parse(request('date'))->format('M j, Y'), $without('date')]);
  if (request()->filled('month')) $chips->push(['Month: '.$months[(int) request('month')], $without('month')]);
  if (request()->filled('year')) $chips->push(['Year: '.request('year'), $without('year')]);
  $columnActive = [
    'name' => request()->boolean('home') || request()->boolean('carousel'),
    'destination' => !empty($picked['destination_id']),
    'category' => !empty($picked['category_id']),
    'dates' => collect(request()->only(['date_from', 'date_to', 'date', 'month', 'year']))->filter()->isNotEmpty(),
    'status' => !empty($picked['status']),
  ];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-baseline gap-2"><h4 class="mb-0">Tours & cruises</h4><span class="text-muted">{{ $tours->total() }} {{ Str::plural('tour', $tours->total()) }}{{ $chips->isNotEmpty() ? ' match the filters' : '' }}</span></div>
                        @can('create-tours')<a class="btn btn-primary" href="{{route('tours.create')}}"><i class="ri-add-line"></i> New tour</a>@endcan
                    </div>
                </div>
            </div>
<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success m-3 mb-0">{{session('message')}}</div>@endif

  {{-- Shopify-style index filters: view tabs, then a full-width search (Enter searches). --}}
  <form id="tour-list-filters" method="get" action="{{ route('tours.index') }}">
    @if($sort)<input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="dir" value="{{ $dir }}">@endif
    @if(request()->filled('date'))<input type="hidden" name="date" value="{{ request('date') }}">@endif
    <div class="tl-tabs-bar">
      <div class="tl-tabs" role="radiogroup" aria-label="Departure dates">@foreach(['all' => 'All', 'upcoming' => 'Upcoming', 'past' => 'Past', 'none' => 'No dates'] as $value => $label)<input type="radio" class="btn-check" name="when" id="when-{{ $value }}" value="{{ $value }}" @checked($when === $value) onchange="this.form.submit()"><label class="tl-tab" for="when-{{ $value }}">{{ $label }}</label>@endforeach</div>
      <div class="d-flex align-items-center gap-2 ms-auto"><label class="text-muted text-nowrap mb-0" for="per-page">Rows</label><select class="form-select form-select-sm" id="per-page" name="per_page" style="width:auto" onchange="this.form.submit()">@foreach([20, 50, 100] as $n)<option value="{{ $n }}" @selected((int) request('per_page', 20) === $n)>{{ $n }}</option>@endforeach</select></div>
    </div>
    <div class="tl-search-bar">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search tours by name" aria-label="Search tours by name">@if(request()->filled('q'))<a href="{{ $without('q') }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
      <button type="submit" class="visually-hidden">Search</button>
      <span class="tl-hint">Click a column title to sort · <i class="ri-filter-3-line"></i> to filter</span>
    </div>
  </form>

  @if($chips->isNotEmpty())
    <div class="tl-chips tl-chips-bar">
      @foreach($chips as [$label, $removeUrl])<a href="{{ $removeUrl }}" class="tl-chip" title="Remove this filter">{{ $label }} <i class="ri-close-line"></i></a>@endforeach
      <a href="{{ route('tours.index', array_filter(['sort' => $sort, 'dir' => $sort ? $dir : null, 'per_page' => request('per_page')])) }}" class="btn btn-link btn-sm text-danger">Clear all filters</a>
    </div>
  @endif

  <div class="table-responsive"><table class="table table-hover align-middle tl-table">
    <thead><tr>
      {{-- Tour --}}
      <th><div class="tl-th">
        <a href="{{ $headSort('name') }}" class="tl-sort {{ $sort === 'name' ? 'active' : '' }}">Tour <i class="{{ $sortIcon('name') }}"></i></a>
        <div class="tl-menu-wrap"><button type="button" class="tl-filter-btn {{ $columnActive['name'] ? 'active' : '' }}" data-tl-menu aria-haspopup="true" aria-expanded="false" aria-label="Filter tours"><i class="ri-filter-3-line"></i></button>
          <div class="tl-menu" role="dialog">
            <a class="dropdown-item" href="{{ $sortUrl('name', 'asc') }}"><i class="ri-sort-asc"></i> Sort A → Z</a><a class="dropdown-item" href="{{ $sortUrl('name', 'desc') }}"><i class="ri-sort-desc"></i> Sort Z → A</a>
            <div class="dropdown-divider"></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" form="tour-list-filters" name="home" value="1" id="f-home" @checked(request()->boolean('home'))><label class="form-check-label" for="f-home">On the home page</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" form="tour-list-filters" name="carousel" value="1" id="f-carousel" @checked(request()->boolean('carousel'))><label class="form-check-label" for="f-carousel">In the homepage carousel</label></div>
            <div class="tl-menu-actions"><a class="btn btn-light" href="{{ route('tours.index', collect($query)->except(['home', 'carousel'])->all()) }}">Clear</a><button class="btn btn-primary" form="tour-list-filters">Apply</button></div>
          </div></div>
      </div></th>

      {{-- Destination --}}
      <th><div class="tl-th">
        <a href="{{ $headSort('destination') }}" class="tl-sort {{ $sort === 'destination' ? 'active' : '' }}">Destination <i class="{{ $sortIcon('destination') }}"></i></a>
        <div class="tl-menu-wrap"><button type="button" class="tl-filter-btn {{ $columnActive['destination'] ? 'active' : '' }}" data-tl-menu aria-haspopup="true" aria-expanded="false" aria-label="Filter by destination"><i class="ri-filter-3-line"></i></button>
          <div class="tl-menu" role="dialog">
            <a class="dropdown-item" href="{{ $sortUrl('destination', 'asc') }}"><i class="ri-sort-asc"></i> Sort A → Z</a><a class="dropdown-item" href="{{ $sortUrl('destination', 'desc') }}"><i class="ri-sort-desc"></i> Sort Z → A</a>
            <div class="dropdown-divider"></div>
            @include('admin.tours._list_checklist', ['name' => 'destination_id', 'options' => $destinations->map(fn ($d) => [$d->id, $d->name, $d->tours_count])->push([0, 'No destination', null]), 'picked' => $picked['destination_id']])
          </div></div>
      </div></th>

      {{-- Categories --}}
      <th><div class="tl-th">
        <a href="{{ $headSort('category') }}" class="tl-sort {{ $sort === 'category' ? 'active' : '' }}">Categories <i class="{{ $sortIcon('category') }}"></i></a>
        <div class="tl-menu-wrap"><button type="button" class="tl-filter-btn {{ $columnActive['category'] ? 'active' : '' }}" data-tl-menu aria-haspopup="true" aria-expanded="false" aria-label="Filter by category"><i class="ri-filter-3-line"></i></button>
          <div class="tl-menu" role="dialog">
            <a class="dropdown-item" href="{{ $sortUrl('category', 'asc') }}"><i class="ri-sort-asc"></i> Sort A → Z</a><a class="dropdown-item" href="{{ $sortUrl('category', 'desc') }}"><i class="ri-sort-desc"></i> Sort Z → A</a>
            <div class="dropdown-divider"></div>
            @include('admin.tours._list_checklist', ['name' => 'category_id', 'options' => $categories->map(fn ($c) => [$c->id, $c->name, $c->tours_count])->push([0, 'No category', null]), 'picked' => $picked['category_id']])
          </div></div>
      </div></th>

      {{-- Dates --}}
      <th><div class="tl-th">
        <a href="{{ $headSort('dates') }}" class="tl-sort {{ $sort === 'dates' ? 'active' : '' }}">Dates <i class="{{ $sortIcon('dates') }}"></i></a>
        <div class="tl-menu-wrap"><button type="button" class="tl-filter-btn {{ $columnActive['dates'] ? 'active' : '' }}" data-tl-menu aria-haspopup="true" aria-expanded="false" aria-label="Filter by departure date"><i class="ri-filter-3-line"></i></button>
          <div class="tl-menu tl-menu-wide" role="dialog">
            <a class="dropdown-item" href="{{ $sortUrl('dates', 'asc') }}"><i class="ri-sort-asc"></i> Earliest date first</a><a class="dropdown-item" href="{{ $sortUrl('dates', 'desc') }}"><i class="ri-sort-desc"></i> Latest date first</a>
            <div class="dropdown-divider"></div>
            <div class="tf-label mb-2">Departs between</div>
            <div class="row g-2 mb-2"><div class="col-6"><label class="form-label mb-1" for="f-from">From</label><input type="date" class="form-control" id="f-from" form="tour-list-filters" name="date_from" value="{{ request('date_from') }}"></div><div class="col-6"><label class="form-label mb-1" for="f-to">To</label><input type="date" class="form-control" id="f-to" form="tour-list-filters" name="date_to" value="{{ request('date_to') }}"></div></div>
            <div class="d-flex flex-wrap gap-1 mb-3">
              @foreach(['This month' => [now()->startOfMonth(), now()->endOfMonth()], 'Next month' => [now()->addMonthNoOverflow()->startOfMonth(), now()->addMonthNoOverflow()->endOfMonth()], 'Next 30 days' => [now(), now()->addDays(30)], 'Next 90 days' => [now(), now()->addDays(90)]] as $presetLabel => [$presetFrom, $presetTo])
                <button type="button" class="btn btn-outline-secondary btn-sm" data-date-preset data-from="{{ $presetFrom->format('Y-m-d') }}" data-to="{{ $presetTo->format('Y-m-d') }}">{{ $presetLabel }}</button>
              @endforeach
            </div>
            <div class="tf-label mb-2">Or month / year</div>
            <div class="row g-2"><div class="col-7"><select class="form-select" form="tour-list-filters" name="month" aria-label="Departure month"><option value="">Any month</option>@foreach($months as $m => $monthName)<option value="{{ $m }}" @selected((int) request('month') === $m)>{{ $monthName }}</option>@endforeach</select></div><div class="col-5"><select class="form-select" form="tour-list-filters" name="year" aria-label="Departure year"><option value="">Any year</option>@foreach($years as $y)<option value="{{ $y }}" @selected((int) request('year') === $y)>{{ $y }}</option>@endforeach</select></div></div>
            <div class="tl-menu-actions"><a class="btn btn-light" href="{{ route('tours.index', collect($query)->except(['date_from', 'date_to', 'date', 'month', 'year'])->all()) }}">Clear</a><button class="btn btn-primary" form="tour-list-filters">Apply</button></div>
          </div></div>
      </div></th>

      {{-- Status --}}
      <th><div class="tl-th">
        <a href="{{ $headSort('status') }}" class="tl-sort {{ $sort === 'status' ? 'active' : '' }}">Status <i class="{{ $sortIcon('status') }}"></i></a>
        <div class="tl-menu-wrap"><button type="button" class="tl-filter-btn {{ $columnActive['status'] ? 'active' : '' }}" data-tl-menu aria-haspopup="true" aria-expanded="false" aria-label="Filter by status"><i class="ri-filter-3-line"></i></button>
          <div class="tl-menu" role="dialog">
            <a class="dropdown-item" href="{{ $sortUrl('status', 'asc') }}"><i class="ri-sort-asc"></i> Sort A → Z</a><a class="dropdown-item" href="{{ $sortUrl('status', 'desc') }}"><i class="ri-sort-desc"></i> Sort Z → A</a>
            <div class="dropdown-divider"></div>
            @include('admin.tours._list_checklist', ['name' => 'status', 'options' => collect($statusLabels)->map(fn ($label, $value) => [$value, $label, null])->values(), 'picked' => $picked['status'], 'searchable' => false])
          </div></div>
      </div></th>

      <th><a href="{{ $headSort('created') }}" class="tl-sort {{ $sort === 'created' ? 'active' : '' }}">Added <i class="{{ $sortIcon('created') }}"></i></a></th>
      <th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    @forelse($tours as $tour)
      @php
        $next = $tour->departures->first(fn ($d) => $d->departs_at->gte(now()));
        $last = $tour->departures->last(fn ($d) => $d->departs_at->lt(now()));
      @endphp
      <tr>
        <td><a href="{{ route('tours.edit', $tour) }}" class="fw-semibold text-body">{{ $tour->name }}</a>
          <div class="d-flex flex-wrap gap-1 mt-1">@if($tour->show_on_home)<span class="badge bg-info bg-opacity-10 text-info">Home</span>@endif @if($tour->show_in_carousel)<span class="badge bg-info bg-opacity-10 text-info">Carousel</span>@endif @if($tour->is_private)<span class="badge bg-secondary bg-opacity-10 text-secondary">Private</span>@endif @if($tour->is_cruise)<span class="badge bg-primary bg-opacity-10 text-primary">Cruise</span>@endif</div></td>
        <td>{{ $tour->destination?->name ?: '—' }}</td>
        <td>@forelse($tour->categories as $category)<span class="badge bg-light text-body me-1">{{ $category->name }}</span>@empty<span class="text-muted">—</span>@endforelse</td>
        <td>
          <div>{{ $tour->departures_count }} {{ str('date')->plural($tour->departures_count) }}</div>
          @if($filtersDepartures)
            @foreach($tour->departures->filter(fn ($d) => $matchesDeparture($d->departs_at)) as $d)<span class="badge {{ $d->departs_at->isPast() ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-success bg-opacity-10 text-success' }} me-1">{{ $d->departs_at->format('M j, Y') }}</span>@endforeach
          @elseif($next)<span class="text-success">Next: {{ $next->departs_at->format('M j, Y') }}</span>
          @elseif($last)<span class="text-muted">Last: {{ $last->departs_at->format('M j, Y') }}</span>
          @endif
        </td>
        <td><span class="badge bg-{{ $statusColors[$tour->status] ?? 'secondary' }} bg-opacity-10 text-{{ $statusColors[$tour->status] ?? 'secondary' }}">{{ $statusLabels[$tour->status] ?? $tour->status }}</span></td>
        <td class="text-nowrap text-muted">{{ $tour->created_at?->format('M j, Y') }}</td>
        <td class="text-nowrap text-end">@can('edit-tours')<a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $tour->name }}" href="{{route('tours.edit',$tour)}}"><i class="ri-pencil-line"></i></a>@endcan @can('create-tours')<button type="button" class="btn btn-sm btn-outline-secondary" title="Duplicate" aria-label="Duplicate {{ $tour->name }}" data-bs-toggle="modal" data-bs-target="#tour-duplicate" data-duplicate-url="{{ route('tours.duplicate', $tour) }}" data-duplicate-name="{{ $tour->name }}" data-duplicate-days="{{ $tour->itinerary_days_count }}" data-duplicate-items="{{ $tour->content_items_count }}" data-duplicate-dates="{{ $tour->departures->filter(fn ($d) => $d->departs_at?->isFuture())->count() }}"><i class="ri-file-copy-line"></i></button>@endcan @can('delete-tours')<form class="d-inline" method="post" action="{{route('tours.destroy',$tour)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $tour->name }}" onclick="return confirm('Delete tour?')"><i class="ri-delete-bin-line"></i></button></form>@endcan</td>
      </tr>
    @empty
      <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-inbox-line fs-2 d-block mb-1"></i>{{ $chips->isNotEmpty() ? 'No tours match these filters.' : 'No tours yet.' }} @if($chips->isNotEmpty())<a href="{{ route('tours.index') }}">Clear all filters</a>@endif</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{$tours->links('admin.vendor.pagination.bootstrap-5')}}</div>
</div></div></div></div></div>
<script>
// Header filter menus with their own toggle (not Bootstrap's dropdown: the admin loads Bootstrap JS 5.0.2
// while the theme CSS is 5.1.3). An open menu is moved to <body> so the scrolling table, the theme's
// dropdown styles and other click handlers cannot hide it; its inputs still belong to #tour-list-filters.
(function () {
  const openMenus = new Set();
  const close = function (menu) {
    menu.classList.remove('tl-open');
    if (menu._tlHome) menu._tlHome.appendChild(menu);
    if (menu._tlBtn) menu._tlBtn.setAttribute('aria-expanded', 'false');
    openMenus.delete(menu);
  };
  const closeAll = function (except) { Array.from(openMenus).forEach(function (m) { if (m !== except) close(m); }); };
  const place = function (menu) {
    const r = menu._tlBtn.getBoundingClientRect();
    const width = menu.offsetWidth;
    let left = r.left; if (left + width > window.innerWidth - 8) left = Math.max(8, r.right - width);
    const top = r.bottom + 6;
    menu.style.left = left + 'px'; menu.style.top = top + 'px';
    menu.style.maxHeight = Math.max(220, window.innerHeight - top - 12) + 'px';
  };
  const open = function (btn, menu) {
    closeAll(menu);
    menu._tlBtn = btn; menu._tlHome = btn.parentElement;
    document.body.appendChild(menu);
    menu.classList.add('tl-open');
    btn.setAttribute('aria-expanded', 'true');
    openMenus.add(menu);
    place(menu);
    const field = menu.querySelector('input[type="search"], input[type="date"], select');
    if (field) field.focus({ preventScroll: true });
  };
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-tl-menu]');
    if (btn) {
      e.preventDefault();
      const menu = btn._tlMenu || (btn._tlMenu = btn.parentElement.querySelector('.tl-menu'));
      if (menu.classList.contains('tl-open')) close(menu); else open(btn, menu);
      return;
    }
    if (!e.target.closest('.tl-menu')) closeAll();
  }, true);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
  const reposition = function () { openMenus.forEach(place); };
  window.addEventListener('resize', reposition);
  window.addEventListener('scroll', function (e) { if (!(e.target.closest && e.target.closest('.tl-menu'))) reposition(); }, true);
})();
document.addEventListener('click', function (e) {
  const preset = e.target.closest('[data-date-preset]');
  if (preset) { const menu = preset.closest('.tl-menu'); menu.querySelector('[name="date_from"]').value = preset.dataset.from; menu.querySelector('[name="date_to"]').value = preset.dataset.to; }
});
// Checklists: search box and Select all.
document.addEventListener('input', function (e) {
  if (!e.target.matches('[data-checklist-search]')) return;
  const q = e.target.value.toLowerCase();
  e.target.closest('.tl-checklist').querySelectorAll('[data-option]').forEach(function (row) { row.hidden = q && !row.dataset.option.includes(q); });
});
document.addEventListener('change', function (e) {
  if (e.target.matches('[data-checklist-all]')) {
    e.target.closest('.tl-checklist').querySelectorAll('[data-option]:not([hidden]) input').forEach(function (box) { box.checked = e.target.checked; });
  }
});
</script>
@include('admin.tours._duplicate_modal')@include('admin.includes.footer')
