@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $labels = ['categories' => 'Tour categories', 'destinations' => 'Destinations', 'transportations' => 'Transportation', 'pickup-points' => 'Pickup points'];
  $singular = ['categories' => 'category', 'destinations' => 'destination', 'transportations' => 'vehicle', 'pickup-points' => 'pickup point'];
  $icons = ['categories' => 'ri-price-tag-3-line', 'destinations' => 'ri-map-pin-line', 'transportations' => 'ri-bus-2-line', 'pickup-points' => 'ri-map-pin-user-line'];
  $title = $labels[$resource] ?? str($resource)->replace('-', ' ')->title();
  $one = $singular[$resource] ?? str($resource)->singular()->replace('-', ' ');
  $isPickup = $resource === 'pickup-points';
  $hasTours = in_array($resource, ['categories', 'destinations'], true);
  $status = (string) request('status', '');
  $filtered = request()->filled('q') || $status !== '';
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4>{{ $title }}</h4><span class="cat-count">{{ $items->total() }} {{ Str::plural($one, $items->total()) }}{{ $filtered ? ' match' : '' }}</span></div>
    @can('create-'.$permission)<a href="{{ route('tour-catalogue.create', $resource) }}" class="btn btn-primary"><i class="ri-add-line"></i> New {{ $one }}</a>@endcan
  </div>
  @if($resource === 'categories')<p class="cat-intro">How long or what kind of trip it is — One-Day, Multi-Day, International, plus services such as Private tours and Cruises. A tour can be in several. Where it goes is set separately under <a href="{{ route('tour-catalogue.index', 'destinations') }}">Destinations</a>.</p>@endif
  @if($resource === 'destinations')<p class="cat-intro">Where a trip goes — continents, countries, provinces and cities, nested with “Parent destination” (e.g. Europe › France › Paris). Each tour picks one destination on its edit page; how long it is comes from <a href="{{ route('tour-catalogue.index', 'categories') }}">Tour categories</a>, so a tour can be Multi-Day and in Europe.</p>@endif

  @include('admin.includes.flash')

  <div class="card tl-card"><div class="card-body p-0">
    <form method="get" action="{{ route('tour-catalogue.index', $resource) }}">
      <div class="tl-tabs-bar">
        <div class="tl-tabs" role="radiogroup" aria-label="Status">@foreach(['' => 'All', '1' => 'Active', '0' => 'Inactive'] as $value => $tabLabel)<input type="radio" class="btn-check" name="status" id="status-{{ $value === '' ? 'all' : $value }}" value="{{ $value }}" @checked($status === (string) $value) onchange="this.form.submit()"><label class="tl-tab" for="status-{{ $value === '' ? 'all' : $value }}">{{ $tabLabel }}</label>@endforeach</div>
      </div>
      <div class="tl-search-bar">
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ Str::plural($one) }} by name" aria-label="Search by name">@if(request()->filled('q'))<a href="{{ route('tour-catalogue.index', ['resource' => $resource] + ($status !== '' ? ['status' => $status] : [])) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <button type="submit" class="visually-hidden">Search</button>
      </div>
    </form>

    <div class="table-responsive"><table class="table table-hover align-middle tl-table mb-0">
      <thead><tr>
        <th>{{ $isPickup ? 'Pickup point' : 'Name' }}</th>
        <th class="{{ $isPickup ? '' : 'd-none d-md-table-cell' }}">{{ $isPickup ? 'Address' : 'Type' }}</th>
        @if($resource === 'transportations')<th class="d-none d-md-table-cell">Seats</th>@endif
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      @forelse($items as $item)
        <tr>
          <td>
            <div class="cat-name">
              @unless($isPickup)
                @if($item->image)<img src="{{ $item->image }}" alt="" class="cat-thumb" loading="lazy">@else<span class="cat-thumb-empty"><i class="{{ $icons[$resource] ?? 'ri-image-line' }}"></i></span>@endif
              @endunless
              <div class="cat-name-text">
                @can('edit-'.$permission)<a href="{{ route('tour-catalogue.edit', [$resource, $item]) }}" class="fw-semibold">@else<span class="fw-semibold">@endcan
                  @if($resource === 'destinations' && $item->parent)<span class="cat-path">{{ $item->parent->parent ? $item->parent->parent->name.' › ' : '' }}{{ $item->parent->name }} › </span>@endif{{ $item->name }}
                @can('edit-'.$permission)</a>@else</span>@endcan
                @if($hasTours)<div class="cat-sub">{{ $item->tours_count }} {{ Str::plural('tour', $item->tours_count) }}</div>@endif
                @if($isPickup && $item->map_url)<div class="cat-sub"><a href="{{ $item->map_url }}" target="_blank" rel="noopener"><i class="ri-map-2-line"></i> Open map</a></div>@endif
              </div>
            </div>
          </td>
          <td class="{{ $isPickup ? '' : 'd-none d-md-table-cell' }}">{{ $isPickup ? ($item->address ?: '—') : (\App\Http\Controllers\Admin\TourCatalogueController::TYPES[$resource][$item->type] ?? ($item->type ?: '—')) }}</td>
          @if($resource === 'transportations')<td class="d-none d-md-table-cell">{{ $item->seat_count ?: '—' }}</td>@endif
          <td>@if($item->is_active)<span class="badge bg-success bg-opacity-10 text-success">Active</span>@else<span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>@endif</td>
          <td class="cat-actions">
            @can('edit-'.$permission)<a href="{{ route('tour-catalogue.edit', [$resource, $item]) }}" class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $item->name }}"><i class="ri-pencil-line"></i></a>@endcan
            @can('delete-'.$permission)<form action="{{ route('tour-catalogue.destroy', [$resource, $item]) }}" method="post">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $item->name }}" onclick="return confirm('Delete “{{ addslashes($item->name) }}”?{{ $hasTours && $item->tours_count ? ' It is used by '.$item->tours_count.' '.Str::plural('tour', $item->tours_count).'.' : '' }}')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="5">
          <div class="cat-empty">
            <div class="cat-empty-icon"><i class="{{ $filtered ? 'ri-search-line' : ($icons[$resource] ?? 'ri-folder-line') }}"></i></div>
            @if($filtered)
              <h5>No {{ Str::plural($one) }} match</h5><p>Try another name or status.</p>
              <a class="btn btn-light" href="{{ route('tour-catalogue.index', $resource) }}">Clear filters</a>
            @else
              <h5>No {{ Str::plural($one) }} yet</h5><p>Add the first one — it becomes available on the tour edit page.</p>
              @can('create-'.$permission)<a href="{{ route('tour-catalogue.create', $resource) }}" class="btn btn-primary"><i class="ri-add-line"></i> New {{ $one }}</a>@endcan
            @endif
          </div>
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $items->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>
@include('admin.includes.footer')
