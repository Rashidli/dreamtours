@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $locales = config('translatable.locales');
  $labels = ['categories' => 'Tour category', 'destinations' => 'Destination', 'transportations' => 'Transportation', 'pickup-points' => 'Pickup point'];
  $listLabels = ['categories' => 'Tour categories', 'destinations' => 'Destinations', 'transportations' => 'Transportation', 'pickup-points' => 'Pickup points'];
  $hints = [
    'categories' => 'The name and text shown on the category listing page.',
    'destinations' => 'The name and text shown on the destination page.',
    'transportations' => 'How the vehicle is named on tour pages and booking forms.',
    'pickup-points' => 'Where travellers meet the coach, and what they should know.',
  ];
  $title = $labels[$resource] ?? str($resource)->singular()->title();
  $isPickup = $resource === 'pickup-points';
  $isActive = (bool) old('is_active', $item->exists ? $item->is_active : true);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('tour-catalogue.index', $resource) }}" class="cat-back" title="Back to {{ mb_strtolower($listLabels[$resource] ?? $title) }}" aria-label="Back to {{ mb_strtolower($listLabels[$resource] ?? $title) }}"><i class="ri-arrow-left-line"></i></a>
      <h4>{{ $item->exists ? $item->name : 'New '.mb_strtolower($title) }}</h4>
      @if($item->exists)<span class="badge {{ $item->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span>@endif
    </div>
  </div>

  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')

  <form method="post" enctype="multipart/form-data" class="cat-form tf-form"
        action="{{ $item->exists ? route('tour-catalogue.update', [$resource, $item]) : route('tour-catalogue.store', $resource) }}">
    @csrf
    @if($item->exists) @method('PUT') @endif

    <div class="row">
      <div class="col-lg-8">
        <div class="card"><div class="card-body">
          <div class="tf-head"><span class="tf-icon"><i class="{{ $isPickup ? 'ri-map-pin-user-line' : 'ri-file-text-line' }}"></i></span><div><h5>Details</h5><p>{{ $hints[$resource] ?? 'Name and description.' }}</p></div></div>

          @include('admin.includes.lang_tabs', ['id' => 'cat'])

          <div class="tab-content {{ count($locales) > 1 ? 'pt-3' : '' }}">
            @foreach($locales as $lang)
              @php $t = $item->translate($lang); $suffix = count($locales) > 1 ? ' ('.strtoupper($lang).')' : ''; @endphp
              <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="cat-{{ $lang }}" role="tabpanel">
                <div class="mb-3">
                  <label class="fw-semibold tf-req" for="cat-name-{{ $lang }}">Name{{ $suffix }}</label>
                  <input id="cat-name-{{ $lang }}" class="form-control @error("$lang.name") is-invalid @enderror" type="text"
                         name="{{ $lang }}[name]" value="{{ old("$lang.name", $t?->name) }}">
                  @error("$lang.name") <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if($isPickup)
                  <div class="mb-0">
                    <label class="fw-semibold" for="cat-instr-{{ $lang }}">Instructions{{ $suffix }}</label>
                    <textarea id="cat-instr-{{ $lang }}" class="form-control" rows="4" name="{{ $lang }}[instructions]">{{ old("$lang.instructions", $t?->instructions) }}</textarea>
                    <div class="form-text">E.g. where exactly to wait, parking, how early to arrive.</div>
                  </div>
                @else
                  <div class="mb-0">
                    <label class="fw-semibold" for="cat-desc-{{ $lang }}">Description{{ $suffix }}</label>
                    <textarea id="cat-desc-{{ $lang }}" class="form-control" rows="5" name="{{ $lang }}[description]">{{ old("$lang.description", $t?->description) }}</textarea>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        </div></div>

        @if($isPickup)
          <div class="card"><div class="card-body">
            <div class="tf-head"><span class="tf-icon"><i class="ri-map-2-line"></i></span><div><h5>Location</h5><p>The address printed on tickets and a map link for travellers.</p></div></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="fw-semibold tf-req" for="cat-address">Address</label>
                <input id="cat-address" class="form-control @error('address') is-invalid @enderror" type="text" name="address" value="{{ old('address', $item->address) }}">
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="fw-semibold" for="cat-map">Map URL</label>
                <input id="cat-map" class="form-control" type="text" name="map_url" value="{{ old('map_url', $item->map_url) }}" placeholder="https://maps.google.com/…">
                @if($item->map_url)<div class="form-text"><a href="{{ $item->map_url }}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> Open the current map link</a></div>@endif
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
            <label class="fw-semibold" for="cat-order">Display order</label>
            <input id="cat-order" class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
            <div class="form-text">Lower numbers are shown first.</div>
          </div>
          <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ $item->exists ? 'Save changes' : 'Create '.mb_strtolower($title) }}</button>
            <a href="{{ route('tour-catalogue.index', $resource) }}" class="btn btn-light">Cancel</a>
          </div>
        </div></div>

        @unless($isPickup)
          <div class="card"><div class="card-body">
            <h5 class="cat-side-title">Settings</h5>
            @unless($isPickup)
              <div class="mb-3">
                <label class="fw-semibold tf-req" for="cat-type">Type</label>
                @if($typeOptions)
                  @php $currentType = old('type', $item->type); @endphp
                  <select id="cat-type" class="form-select @error('type') is-invalid @enderror" name="type" required>
                    @foreach($typeOptions as $value => $label)<option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>@endforeach
                    @if($currentType && !array_key_exists($currentType, $typeOptions))<option value="{{ $currentType }}" selected>{{ $currentType }}</option>@endif
                  </select>
                @else
                  <input id="cat-type" class="form-control @error('type') is-invalid @enderror" type="text" name="type" value="{{ old('type', $item->type) }}">
                @endif
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            @endunless

            @if($resource === 'destinations')
              <div class="mb-3">
                <label class="fw-semibold" for="cat-parent">Parent destination</label>
                <select id="cat-parent" class="form-select" name="parent_id">
                  <option value="">None — top level</option>
                  @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $item->parent_id) == $parent->id)>{{ $parent->parent ? $parent->parent->name.' › ' : '' }}{{ $parent->name }}</option>
                  @endforeach
                </select>
                <div class="form-text">E.g. Paris sits under France.</div>
              </div>
            @endif

            @if($resource === 'transportations')
              <div class="mb-3">
                <label class="fw-semibold" for="cat-seats">Seats</label>
                <input id="cat-seats" class="form-control" type="number" name="seat_count" value="{{ old('seat_count', $item->seat_count) }}">
              </div>
            @endif
          </div></div>
        @endunless

        @unless($isPickup)
          <div class="card"><div class="card-body cat-image-field">
            <h5 class="cat-side-title">Image</h5>
            @if($item->image)<a href="{{ $item->image }}" target="_blank" rel="noopener"><img src="{{ $item->image }}" alt="" class="tf-image mb-3" data-cat-preview-img></a>@else<div class="tf-image-empty mb-3" data-cat-preview-empty><span><i class="ri-image-line"></i>No image yet</span></div><img src="" alt="" class="tf-image mb-3 d-none" data-cat-preview-img>@endif
            <label class="fw-semibold" for="cat-image">Upload a new image</label>
            <input id="cat-image" type="file" class="form-control @error('image') is-invalid @enderror" name="image" accept="image/*,.heic,.heif" data-cat-preview>@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <input class="form-control mt-2" name="image_path" value="{{ old('image_path', $item->getRawOriginal('image')) }}" placeholder="or paste an image URL / stored file name" aria-label="Image URL or stored file name">
            <div class="form-text">A wide photo works best (16:10). Shown on listing cards.</div>
          </div></div>
        @endunless
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
