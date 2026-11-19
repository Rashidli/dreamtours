@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $labels = ['visa-services' => 'Visa support pricing', 'gallery-albums' => 'Gallery albums', 'gallery-categories' => 'Gallery categories'];
  $singular = ['visa-services' => 'visa service', 'gallery-albums' => 'album', 'gallery-categories' => 'category'];
  $icons = ['visa-services' => 'ri-passport-line', 'gallery-albums' => 'ri-gallery-line', 'gallery-categories' => 'ri-price-tag-3-line'];
  $intros = [
    'visa-services' => 'The fee table on the Visa Support page — one row per country. Enquiries sent from that page arrive under Visa enquiries.',
    'gallery-albums' => 'Photo and video albums shown on the Gallery page. Open an album’s photos to upload in bulk, reorder or replace them.',
    'gallery-categories' => 'Groups visitors can filter the Gallery page by. Each album picks one category.',
  ];
  $title = $labels[$resource] ?? str($resource)->replace('-', ' ')->title();
  $one = $singular[$resource] ?? str($resource)->singular()->replace('-', ' ');
  $isVisa = $resource === 'visa-services';
  $isAlbum = $resource === 'gallery-albums';
  $status = (string) request('status', '');
  $filtered = request()->filled('q') || $status !== '';
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4>{{ $title }}</h4><span class="cat-count">{{ $items->total() }} {{ Str::plural($one, $items->total()) }}{{ $filtered ? ' match' : '' }}</span></div>
    @can('create-'.$resource)<a href="{{ route('domain-content.create', $resource) }}" class="btn btn-primary"><i class="ri-add-line"></i> New {{ $one }}</a>@endcan
  </div>
  @isset($intros[$resource])<p class="cat-intro">{{ $intros[$resource] }}@if($isVisa) <a href="{{ route('visa-enquiries.index') }}">Open visa enquiries</a>@endif</p>@endisset

  @include('admin.includes.flash')

  <div class="card tl-card"><div class="card-body p-0">
    <form method="get" action="{{ route('domain-content.index', $resource) }}">
      <div class="tl-tabs-bar">
        <div class="tl-tabs" role="radiogroup" aria-label="Status">@foreach(['' => 'All', '1' => 'Active', '0' => 'Inactive'] as $value => $tabLabel)<input type="radio" class="btn-check" name="status" id="status-{{ $value === '' ? 'all' : $value }}" value="{{ $value }}" @checked($status === (string) $value) onchange="this.form.submit()"><label class="tl-tab" for="status-{{ $value === '' ? 'all' : $value }}">{{ $tabLabel }}</label>@endforeach</div>
      </div>
      <div class="tl-search-bar">
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ Str::plural($one) }} by {{ $isAlbum ? 'title' : 'name' }}" aria-label="Search">@if(request()->filled('q'))<a href="{{ route('domain-content.index', ['resource' => $resource] + ($status !== '' ? ['status' => $status] : [])) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <button type="submit" class="visually-hidden">Search</button>
      </div>
    </form>

    <div class="table-responsive"><table class="table table-hover align-middle tl-table mb-0">
      <thead><tr>
        <th>{{ $isVisa ? 'Country / service' : ($isAlbum ? 'Album' : 'Name') }}</th>
        @if($isVisa)<th>Service fee</th><th class="d-none d-md-table-cell">Processing</th>@endif
        @if($isAlbum)<th>Media</th>@endif
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      @forelse($items as $item)
        @php $name = data_get($item, $label); $mediaCount = $isAlbum ? $item->media()->count() : 0; @endphp
        <tr>
          <td>
            <div class="cat-name">
              @if($isAlbum)
                @if($item->cover_image)<img src="{{ $item->cover_image }}" alt="" class="cat-thumb" loading="lazy">@else<span class="cat-thumb-empty"><i class="ri-gallery-line"></i></span>@endif
              @endif
              <div class="cat-name-text">
                @if($isVisa && ($item->country || $item->destination))
                  @can('edit-'.$resource)<a href="{{ route('domain-content.edit', [$resource, $item]) }}" class="fw-semibold">{{ $item->country ?: $item->destination?->name }}</a>@else<span class="fw-semibold">{{ $item->country ?: $item->destination?->name }}</span>@endcan
                  @if($name)<div class="cat-sub">{{ $name }}</div>@endif
                @else
                  @can('edit-'.$resource)<a href="{{ route('domain-content.edit', [$resource, $item]) }}" class="fw-semibold">{{ $name }}</a>@else<span class="fw-semibold">{{ $name }}</span>@endcan
                  @if($isAlbum && ($item->tour_date || $item->category))<div class="cat-sub">{{ collect([$item->category?->name, $item->tour_date?->format('M j, Y')])->filter()->implode(' · ') }}</div>@endif
                @endif
              </div>
            </div>
          </td>
          @if($isVisa)
            <td><span class="fw-semibold">{{ $item->price !== null ? money($item->price, $item->currency) : '—' }}</span>@if($item->third_party_fee !== null)<div class="cat-sub">+ {{ money($item->third_party_fee, $item->currency) }} third-party</div>@endif</td>
            <td class="d-none d-md-table-cell">{{ $item->processing_time ?: '—' }}</td>
          @endif
          @if($isAlbum)
            <td class="text-nowrap">@can('list-gallery-media')<a href="{{ route('gallery-media.index', $item) }}">{{ $mediaCount }} {{ Str::plural('item', $mediaCount) }}</a>@else{{ $mediaCount }} {{ Str::plural('item', $mediaCount) }}@endcan</td>
          @endif
          <td>@if($item->is_active)<span class="badge bg-success bg-opacity-10 text-success">Active</span>@else<span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>@endif</td>
          <td class="cat-actions">
            @if($isVisa)<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#visa-service-{{ $item->id }}">Details</button>@endif
            @if($isAlbum)<a href="{{ route('gallery-media.index', $item) }}" class="btn btn-sm btn-outline-secondary" title="Photos and videos"><i class="ri-image-line"></i> Media</a>@endif
            @can('edit-'.$resource)<a href="{{ route('domain-content.edit', [$resource, $item]) }}" class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $name }}"><i class="ri-pencil-line"></i></a>@endcan
            @can('delete-'.$resource)<form action="{{ route('domain-content.destroy', [$resource, $item]) }}" method="post">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $name }}" onclick="return confirm('Delete “{{ addslashes($name) }}”?{{ $isAlbum && $mediaCount ? ' Its '.$mediaCount.' media '.Str::plural('item', $mediaCount).' will no longer be shown.' : '' }}')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="6">
          <div class="cat-empty">
            <div class="cat-empty-icon"><i class="{{ $filtered ? 'ri-search-line' : ($icons[$resource] ?? 'ri-folder-line') }}"></i></div>
            @if($filtered)
              <h5>No {{ Str::plural($one) }} match</h5><p>Try another search or status.</p>
              <a class="btn btn-light" href="{{ route('domain-content.index', $resource) }}">Clear filters</a>
            @else
              <h5>No {{ Str::plural($one) }} yet</h5><p>{{ $isAlbum ? 'Create an album, then upload its photos and videos.' : 'Add the first one to show it on the website.' }}</p>
              @can('create-'.$resource)<a href="{{ route('domain-content.create', $resource) }}" class="btn btn-primary"><i class="ri-add-line"></i> New {{ $one }}</a>@endcan
            @endif
          </div>
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $items->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>

@if($isVisa)
  @foreach($items as $item)
    <div class="modal fade" id="visa-service-{{ $item->id }}" tabindex="-1" aria-labelledby="visa-service-{{ $item->id }}-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="visa-service-{{ $item->id }}-title">{{ $item->country ?: ($item->destination?->name ?: data_get($item, $label)) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <dl class="cat-dl">
          <dt>Service</dt><dd>{{ data_get($item, $label) ?: '—' }}</dd>
          <dt>DreamTour fee</dt><dd class="fw-semibold">{{ $item->price !== null ? money($item->price, $item->currency) : '—' }}</dd>
          <dt>Third-party fee</dt><dd>{{ $item->third_party_fee !== null ? money($item->third_party_fee, $item->currency) : 'Not included' }}@if($item->third_party_fee_note)<div class="cat-sub">{{ $item->third_party_fee_note }}</div>@endif</dd>
          <dt>Processing time</dt><dd>{{ $item->processing_time ?: '—' }}</dd>
          <dt>Linked destination</dt><dd>{{ $item->destination?->name ?: '—' }}</dd>
          <dt>Description</dt><dd>{!! $item->description ? nl2br(e($item->description)) : '—' !!}</dd>
          <dt>Required documents</dt><dd>@php $docs = collect(preg_split('/\r?\n/', (string) $item->required_documents))->map(fn ($d) => trim($d))->filter(); @endphp @if($docs->isNotEmpty())<ul class="mb-0 ps-3">@foreach($docs as $doc)<li>{{ $doc }}</li>@endforeach</ul>@else—@endif</dd>
          <dt>Note</dt><dd>{!! $item->disclaimer ? nl2br(e($item->disclaimer)) : '—' !!}</dd>
        </dl>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>@can('edit-'.$resource)<a href="{{ route('domain-content.edit', [$resource, $item]) }}" class="btn btn-primary"><i class="ri-pencil-line"></i> Edit</a>@endcan</div>
    </div></div></div>
  @endforeach
@endif
@include('admin.includes.footer')
