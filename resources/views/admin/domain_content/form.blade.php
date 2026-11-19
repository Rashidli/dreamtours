@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $locales = config('translatable.locales');
  $labels = ['visa-services' => 'Visa service', 'gallery-albums' => 'Gallery album', 'gallery-categories' => 'Gallery category'];
  $listLabels = ['visa-services' => 'Visa support pricing', 'gallery-albums' => 'Gallery albums', 'gallery-categories' => 'Gallery categories'];
  $title = $labels[$resource] ?? str($resource)->replace('-', ' ')->title();
  $isAlbum = $resource === 'gallery-albums';
  $isVisa = $resource === 'visa-services';
  $nameField = $isAlbum ? 'title' : 'name';
  $hints = [
    'visa-services' => 'The service name and text shown in the Visa Support fee table.',
    'gallery-albums' => 'The album title and text shown on the Gallery page.',
    'gallery-categories' => 'The name visitors filter the Gallery page by.',
  ];
  $heading = $item->exists ? (data_get($item, $nameField) ?: ($isVisa ? $item->country : null) ?: 'Edit '.mb_strtolower($title)) : 'New '.mb_strtolower($title);
  $isActive = (bool) old('is_active', $item->exists ? $item->is_active : true);
  $backLabel = mb_strtolower($listLabels[$resource] ?? $title);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('domain-content.index', $resource) }}" class="cat-back" title="Back to {{ $backLabel }}" aria-label="Back to {{ $backLabel }}"><i class="ri-arrow-left-line"></i></a>
      <h4>{{ $heading }}</h4>
      @if($item->exists)<span class="badge {{ $item->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span>@endif
    </div>
    @if($isAlbum && $item->exists)<div class="cat-header-actions"><a class="btn btn-light" href="{{ route('gallery-media.index', $item) }}"><i class="ri-image-line"></i> All media</a></div>@endif
  </div>

  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')

  <form method="post" enctype="multipart/form-data" class="cat-form tf-form"
        action="{{ $item->exists ? route('domain-content.update', [$resource, $item]) : route('domain-content.store', $resource) }}">
    @csrf
    @if($item->exists) @method('PUT') @endif

    <div class="row">
      <div class="col-lg-8">
        <div class="card"><div class="card-body">
          <div class="tf-head"><span class="tf-icon"><i class="{{ $isVisa ? 'ri-passport-line' : ($isAlbum ? 'ri-gallery-line' : 'ri-price-tag-3-line') }}"></i></span><div><h5>Details</h5><p>{{ $hints[$resource] ?? 'Name and description.' }}</p></div></div>

          @include('admin.includes.lang_tabs', ['id' => 'dc'])

          <div class="tab-content {{ count($locales) > 1 ? 'pt-3' : '' }}">
            @foreach($locales as $lang)
              @php $t = $item->translate($lang); $suffix = count($locales) > 1 ? ' ('.strtoupper($lang).')' : ''; @endphp
              <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="dc-{{ $lang }}" role="tabpanel">
                <div class="mb-3">
                  <label class="fw-semibold tf-req" for="dc-name-{{ $lang }}">{{ $isAlbum ? 'Title' : 'Name' }}{{ $suffix }}</label>
                  <input id="dc-name-{{ $lang }}" class="form-control @error("$lang.$nameField") is-invalid @enderror" type="text"
                         name="{{ $lang }}[{{ $nameField }}]" value="{{ old("$lang.$nameField", $t?->{$nameField}) }}" @if($isVisa) placeholder="e.g. UK Standard Visitor Visa" @endif>
                  @error("$lang.$nameField") <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @unless($resource === 'gallery-categories')
                  <div class="{{ $isVisa ? 'mb-3' : 'mb-0' }}">
                    <label class="fw-semibold" for="dc-desc-{{ $lang }}">Description{{ $suffix }}</label>
                    <textarea id="dc-desc-{{ $lang }}" class="form-control" rows="4" name="{{ $lang }}[description]">{{ old("$lang.description", $t?->description) }}</textarea>
                  </div>
                @endunless

                @if($isVisa)
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="fw-semibold" for="dc-docs-{{ $lang }}">Required documents{{ $suffix }}</label>
                      <textarea id="dc-docs-{{ $lang }}" class="form-control" rows="5" name="{{ $lang }}[required_documents]" placeholder="Valid passport&#10;Recent photo">{{ old("$lang.required_documents", $t?->required_documents) }}</textarea>
                      <div class="form-text">One document per line — shown as a list.</div>
                    </div>
                    <div class="col-md-6">
                      <label class="fw-semibold" for="dc-note-{{ $lang }}">Note{{ $suffix }}</label>
                      <textarea id="dc-note-{{ $lang }}" class="form-control" rows="5" name="{{ $lang }}[disclaimer]">{{ old("$lang.disclaimer", $t?->disclaimer) }}</textarea>
                      <div class="form-text">Small print under the fees, e.g. a disclaimer.</div>
                    </div>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        </div></div>

        @if($isVisa)
          <div class="card"><div class="card-body">
            <div class="tf-head"><span class="tf-icon"><i class="ri-money-dollar-circle-line"></i></span><div><h5>Country & fees</h5><p>What the customer pays DreamTour, plus any government fee paid separately.</p></div></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="fw-semibold" for="dc-country">Country</label>
                <input id="dc-country" class="form-control" type="text" name="country" value="{{ old('country', $item->country) }}" placeholder="e.g. United Kingdom">
                <div class="form-text">Type the country, or link it to a destination.</div>
              </div>
              <div class="col-md-6">
                <label class="fw-semibold" for="dc-dest">Linked destination <span class="text-muted fw-normal">(optional)</span></label>
                <select id="dc-dest" class="form-select" name="destination_id">
                  <option value="">None</option>
                  @foreach($destinations as $d)
                    <option value="{{ $d->id }}" @selected(old('destination_id', $item->destination_id) == $d->id)>{{ $d->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <div class="cat-fee-row">
                  <div><label class="fw-semibold" for="dc-price">DreamTour service fee</label><input id="dc-price" class="form-control" type="text" inputmode="decimal" name="price" value="{{ old('price', $item->price) }}"></div>
                  <div><label class="fw-semibold tf-req" for="dc-currency">Currency</label><input id="dc-currency" class="form-control text-uppercase" type="text" name="currency" maxlength="3" value="{{ old('currency', $item->currency ?: 'CAD') }}"></div>
                </div>
              </div>
              <div class="col-md-6">
                <label class="fw-semibold" for="dc-processing">Processing time</label>
                <input id="dc-processing" class="form-control" type="text" name="processing_time" value="{{ old('processing_time', $item->processing_time) }}" placeholder="e.g. 5–7 working days">
              </div>
              <div class="col-12">
                <div class="tf-subtle">
                  <div class="row g-3">
                    <div class="col-md-5">
                      <label class="fw-semibold" for="dc-third">Government / third-party fee <span class="text-muted fw-normal">(optional)</span></label>
                      <input id="dc-third" class="form-control" type="text" inputmode="decimal" name="third_party_fee" value="{{ old('third_party_fee', $item->third_party_fee) }}" placeholder="leave empty if not included">
                      <div class="form-text">Same currency as the service fee.</div>
                    </div>
                    <div class="col-md-7">
                      <label class="fw-semibold" for="dc-third-note">Fee note</label>
                      <input id="dc-third-note" class="form-control" type="text" name="third_party_fee_note" value="{{ old('third_party_fee_note', $item->third_party_fee_note) }}" placeholder="e.g. paid directly to the consulate">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div></div>
        @endif
      </div>

      <div class="col-lg-4">
        <div class="card cat-save-card"><div class="card-body">
          <h5 class="cat-side-title">Status</h5>
          <div class="form-check form-switch tf-switch">
            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="statusSwitch" value="1" @checked($isActive)>
            <label class="form-check-label" for="statusSwitch">Active<small>Inactive items are hidden on the website.</small></label>
          </div>
          <div class="mt-3">
            <label class="fw-semibold" for="dc-order">Display order</label>
            <input id="dc-order" class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
            <div class="form-text">Lower numbers are shown first.</div>
          </div>
          <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ $item->exists ? 'Save changes' : 'Create '.mb_strtolower($title) }}</button>
            <a href="{{ route('domain-content.index', $resource) }}" class="btn btn-light">Cancel</a>
          </div>
        </div></div>

        @if($isAlbum)
          <div class="card"><div class="card-body">
            <h5 class="cat-side-title">Where it belongs</h5>
            <div class="mb-3">
              <label class="fw-semibold" for="dc-cat">Category</label>
              <select id="dc-cat" class="form-select" name="gallery_category_id">
                <option value="">None</option>
                @foreach($categories as $c)
                  <option value="{{ $c->id }}" @selected(old('gallery_category_id', $item->gallery_category_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="fw-semibold" for="dc-tour">Tour</label>
              <select id="dc-tour" class="form-select" name="tour_id">
                <option value="">None</option>
                @foreach($tours as $t)
                  <option value="{{ $t->id }}" @selected(old('tour_id', $item->tour_id) == $t->id)>{{ $t->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="fw-semibold" for="dc-album-dest">Destination</label>
              <select id="dc-album-dest" class="form-select" name="destination_id">
                <option value="">None</option>
                @foreach($destinations as $d)
                  <option value="{{ $d->id }}" @selected(old('destination_id', $item->destination_id) == $d->id)>{{ $d->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="fw-semibold" for="dc-date">Tour date</label>
              <input id="dc-date" class="form-control" type="date" name="tour_date" value="{{ old('tour_date', $item->tour_date?->format('Y-m-d')) }}">
            </div>
          </div></div>

          <div class="card"><div class="card-body cat-image-field">
            <h5 class="cat-side-title">Cover image</h5>
            @if($item->cover_image)<a href="{{ $item->cover_image }}" target="_blank" rel="noopener"><img src="{{ $item->cover_image }}" alt="" class="tf-image mb-3" data-cat-preview-img></a>@else<div class="tf-image-empty mb-3" data-cat-preview-empty><span><i class="ri-image-line"></i>No cover yet</span></div><img src="" alt="" class="tf-image mb-3 d-none" data-cat-preview-img>@endif
            <label class="fw-semibold" for="dc-cover">Upload a new cover</label>
            <input id="dc-cover" type="file" class="form-control @error('cover_image') is-invalid @enderror" name="cover_image" accept="image/*,.heic,.heif" data-cat-preview>@error('cover_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <input class="form-control mt-2" name="cover_image_path" value="{{ old('cover_image_path', $item->getRawOriginal('cover_image')) }}" placeholder="or paste an image URL / stored file name" aria-label="Cover image URL or stored file name">
            <div class="form-text">Shown on the album card on the Gallery page.</div>
          </div></div>
        @endif
      </div>
    </div>
  </form>

  @if($isAlbum && $item->exists)
    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-image-line"></i></span><div class="flex-grow-1"><h5>Photos & videos</h5><p>Upload in bulk, or add by link. Hover a photo to edit or delete it.</p></div><span class="badge bg-secondary bg-opacity-10 text-secondary flex-shrink-0">{{ $item->media->count() }} {{ Str::plural('item', $item->media->count()) }}</span></div>

      @include('admin.gallery_media.bulk_upload', ['album' => $item])

      <form class="tf-subtle mb-4" method="post" action="{{ route('gallery-media.store', $item) }}">
        @csrf
        <div class="fw-semibold mb-2">…or add a photo or video by link</div>
        <div class="cat-link-add">
          <div><label class="form-label mb-1" for="dc-link-type">Type</label><select id="dc-link-type" class="form-select" name="type"><option value="image">Photo</option><option value="video">Video</option></select></div>
          <div><label class="form-label mb-1" for="dc-link-path">Link</label><input id="dc-link-path" class="form-control" name="path" placeholder="https://…" required></div>
          <div><label class="form-label mb-1" for="dc-link-caption">Title</label><input id="dc-link-caption" class="form-control" name="en[caption]" placeholder="Optional"></div>
          <div><button class="btn btn-outline-secondary w-100"><i class="ri-add-line"></i> Add</button></div>
        </div>
      </form>

      @if($item->media->isNotEmpty())
        <div class="cat-media-grid">
          @foreach($item->media as $medium)
            @include('admin.gallery_media._tile', ['album' => $item, 'medium' => $medium])
          @endforeach
        </div>
      @else
        <div class="cat-empty py-4"><div class="cat-empty-icon"><i class="ri-image-line"></i></div><h5>No photos yet</h5><p>Drop photos into the box above to start the album.</p></div>
      @endif
    </div></div>
  @endif

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
