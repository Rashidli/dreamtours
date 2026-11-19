@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $mt = $medium->translate('en');
  $preview = $medium->type === 'image' ? $medium->path : $medium->thumbnail;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('gallery-media.index', $album) }}" class="cat-back" title="Back to {{ $album->title }}" aria-label="Back to the album media"><i class="ri-arrow-left-line"></i></a>
      <h4>Edit {{ $medium->type === 'video' ? 'video' : 'photo' }}</h4><span class="cat-count">{{ $album->title }}</span>
    </div>
  </div>

  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')

  <form method="post" enctype="multipart/form-data" class="cat-form tf-form" action="{{ route('gallery-media.update', [$album, $medium]) }}">@csrf @method('PUT')
    <div class="row">
      <div class="col-lg-8">
        <div class="card"><div class="card-body">
          <div class="tf-head"><span class="tf-icon"><i class="{{ $medium->type === 'video' ? 'ri-video-line' : 'ri-image-line' }}"></i></span><div><h5>File</h5><p>Replace the file by uploading a new one, or point to a link.</p></div></div>
          <div class="row g-3">
            <div class="col-md-5 cat-image-field">
              @if($preview)<a href="{{ $medium->path }}" target="_blank" rel="noopener"><img src="{{ $preview }}" alt="" class="tf-image" data-cat-preview-img></a>@else<div class="tf-image-empty" data-cat-preview-empty><span><i class="{{ $medium->type === 'video' ? 'ri-play-circle-line' : 'ri-image-line' }}"></i>{{ $medium->type === 'video' ? 'Video' : 'No preview' }}</span></div><img src="" alt="" class="tf-image d-none" data-cat-preview-img>@endif
              @if($medium->path)<div class="form-text"><a href="{{ $medium->path }}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> Open the current file</a></div>@endif
            </div>
            <div class="col-md-7">
              <div class="mb-3">
                <label class="fw-semibold" for="gm-type">Type</label>
                <select id="gm-type" class="form-select" name="type"><option value="image" @selected($medium->type==='image')>Photo</option><option value="video" @selected($medium->type==='video')>Video</option></select>
              </div>
              <div class="mb-3">
                <label class="fw-semibold" for="gm-file">Upload a new photo or video</label>
                <input id="gm-file" type="file" class="form-control" name="file" accept="image/*,.heic,.heif,video/mp4,video/webm,video/quicktime" data-cat-preview>
              </div>
              <div>
                <label class="fw-semibold" for="gm-path">…or a link</label>
                <input id="gm-path" class="form-control" name="path" value="{{ old('path', $medium->getRawOriginal('path')) }}">
                <div class="form-text">A full URL or a stored file name.</div>
              </div>
            </div>
          </div>
        </div></div>

        <div class="card"><div class="card-body">
          <div class="tf-head"><span class="tf-icon"><i class="ri-text"></i></span><div><h5>Text</h5><p>The title shows under the photo; alt text describes it for screen readers and search engines.</p></div></div>
          <div class="mb-3">
            <label class="fw-semibold" for="gm-caption">Title</label>
            <input id="gm-caption" class="form-control" name="en[caption]" value="{{ old('en.caption', $mt?->caption) }}">
          </div>
          <div>
            <label class="fw-semibold" for="gm-alt">Alt text</label>
            <input id="gm-alt" class="form-control" name="en[alt_text]" value="{{ old('en.alt_text', $mt?->alt_text) }}" placeholder="e.g. Tour group at Niagara Falls in autumn">
          </div>
        </div></div>
      </div>

      <div class="col-lg-4">
        <div class="card cat-save-card"><div class="card-body">
          <h5 class="cat-side-title">Order</h5>
          <label class="fw-semibold" for="gm-order">Display order</label>
          <input id="gm-order" class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $medium->sort_order ?? 0) }}">
          <div class="form-text">Lower numbers are shown first in the album.</div>
          <div class="d-grid gap-2 mt-3">
            <button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save changes</button>
            <a class="btn btn-light" href="{{ route('gallery-media.index', $album) }}">Cancel</a>
          </div>
        </div></div>

        <div class="card"><div class="card-body cat-image-field">
          <h5 class="cat-side-title">Thumbnail</h5>
          @if($medium->thumbnail)<a href="{{ $medium->thumbnail }}" target="_blank" rel="noopener"><img src="{{ $medium->thumbnail }}" alt="" class="tf-image mb-3" data-cat-preview-img></a>@else<div class="tf-image-empty mb-3" data-cat-preview-empty><span><i class="ri-image-line"></i>No thumbnail</span></div><img src="" alt="" class="tf-image mb-3 d-none" data-cat-preview-img>@endif
          <label class="fw-semibold" for="gm-thumb">Upload a thumbnail</label>
          <input id="gm-thumb" type="file" class="form-control" name="thumbnail" accept="image/*,.heic,.heif" data-cat-preview>
          <input class="form-control mt-2" name="thumbnail_path" value="{{ old('thumbnail_path', $medium->getRawOriginal('thumbnail')) }}" placeholder="or paste an image URL / stored file name" aria-label="Thumbnail URL or stored file name">
          <div class="form-text">Optional. Mostly useful for videos.</div>
        </div></div>
      </div>
    </div>
  </form>

</div></div></div>
<script>
// Preview a newly chosen image before saving.
document.addEventListener('change', function (e) {
  if (!e.target.matches('[data-cat-preview]') || !e.target.files || !e.target.files[0] || !e.target.files[0].type.startsWith('image/')) return;
  const box = e.target.closest('.card-body'); const img = box.querySelector('[data-cat-preview-img]'); const empty = box.querySelector('[data-cat-preview-empty]');
  if (!img) return;
  img.src = URL.createObjectURL(e.target.files[0]); img.classList.remove('d-none'); if (empty) empty.classList.add('d-none');
});
</script>
@include('admin.includes.footer')
