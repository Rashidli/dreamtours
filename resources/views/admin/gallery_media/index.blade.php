@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $type = (string) request('type', '');
  $filtered = request()->filled('q') || $type !== '';
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('domain-content.index', 'gallery-albums') }}" class="cat-back" title="Back to gallery albums" aria-label="Back to gallery albums"><i class="ri-arrow-left-line"></i></a>
      <h4>{{ $album->title }}</h4><span class="cat-count">{{ $media->total() }} {{ Str::plural('item', $media->total()) }}{{ $filtered ? ' match' : '' }}</span>
    </div>
    <div class="cat-header-actions"><a class="btn btn-light" href="{{ route('domain-content.edit', ['gallery-albums', $album]) }}"><i class="ri-settings-3-line"></i> Album settings</a></div>
  </div>

  @include('admin.includes.flash')

  @can('create-gallery-media')
    <div class="card"><div class="card-body pb-1">
      @include('admin.gallery_media.bulk_upload')
    </div></div>
  @endcan

  <div class="card tl-card"><div class="card-body p-0">
    <form method="get" action="{{ route('gallery-media.index', $album) }}">
      <div class="tl-tabs-bar">
        <div class="tl-tabs" role="radiogroup" aria-label="Type">@foreach(['' => 'All', 'image' => 'Photos', 'video' => 'Videos'] as $value => $tabLabel)<input type="radio" class="btn-check" name="type" id="type-{{ $value ?: 'all' }}" value="{{ $value }}" @checked($type === $value) onchange="this.form.submit()"><label class="tl-tab" for="type-{{ $value ?: 'all' }}">{{ $tabLabel }}</label>@endforeach</div>
      </div>
      <div class="tl-search-bar">
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search by file name or title" aria-label="Search by file name or title">@if(request()->filled('q'))<a href="{{ route('gallery-media.index', ['album' => $album] + ($type !== '' ? ['type' => $type] : [])) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <button type="submit" class="visually-hidden">Search</button>
        <span class="tl-hint">Hover a photo to edit or delete it</span>
      </div>
    </form>

    @if($media->isNotEmpty())
      <div class="cat-section-pad">
        <div class="cat-media-grid">
          @foreach($media as $medium)
            @include('admin.gallery_media._tile', ['album' => $album, 'medium' => $medium])
          @endforeach
        </div>
      </div>
    @else
      <div class="cat-empty">
        <div class="cat-empty-icon"><i class="{{ $filtered ? 'ri-search-line' : 'ri-image-line' }}"></i></div>
        @if($filtered)
          <h5>Nothing matches</h5><p>Try another search or type.</p>
          <a class="btn btn-light" href="{{ route('gallery-media.index', $album) }}">Clear filters</a>
        @else
          <h5>No photos yet</h5><p>Drop photos into the upload box above to fill this album.</p>
        @endif
      </div>
    @endif
    <div class="tl-pager">{{ $media->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>
@include('admin.includes.footer')
