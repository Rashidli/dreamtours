@include('admin.includes.header')
@php($locales = config('translatable.locales'))
@php($labels = ['categories' => 'Tour category', 'destinations' => 'Destination', 'transportations' => 'Transportation', 'pickup-points' => 'Pickup point'])
@php($title = $labels[$resource] ?? str($resource)->singular()->title())
@php($isPickup = $resource === 'pickup-points')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $item->exists ? $title.' Edit' : 'New '.mb_strtolower($title) }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('tour-catalogue.index', $resource) }}">{{ $title }}</a></li>
                                <li class="breadcrumb-item active">{{ $item->exists ? $item->name : 'New' }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @include('admin.includes.validation_errors')

            <form method="post" enctype="multipart/form-data"
                  action="{{ $item->exists ? route('tour-catalogue.update', [$resource, $item]) : route('tour-catalogue.store', $resource) }}">
                @csrf
                @if($item->exists) @method('PUT') @endif

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Details</h4>

                                @include('admin.includes.lang_tabs', ['id' => 'cat'])

                                <div class="tab-content pt-3">
                                    @foreach($locales as $lang)
                                        @php($t = $item->translate($lang))
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="cat-{{ $lang }}" role="tabpanel">
                                            <div class="mb-3">
                                                <label class="form-label">Name ({{ strtoupper($lang) }}) <span class="text-danger">*</span></label>
                                                <input class="form-control @error("$lang.name") is-invalid @enderror" type="text"
                                                       name="{{ $lang }}[name]" value="{{ old("$lang.name", $t?->name) }}">
                                                @error("$lang.name") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            @if($isPickup)
                                                <div class="mb-3">
                                                    <label class="form-label">Instructions ({{ strtoupper($lang) }})</label>
                                                    <textarea class="form-control" rows="4" name="{{ $lang }}[instructions]">{{ old("$lang.instructions", $t?->instructions) }}</textarea>
                                                </div>
                                            @else
                                                <div class="mb-3">
                                                    <label class="form-label">Description ({{ strtoupper($lang) }})</label>
                                                    <textarea class="form-control" rows="4" name="{{ $lang }}[description]">{{ old("$lang.description", $t?->description) }}</textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if($isPickup)
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Address <span class="text-danger">*</span></label>
                                            <input class="form-control @error('address') is-invalid @enderror" type="text" name="address" value="{{ old('address', $item->address) }}">
                                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Map URL</label>
                                            <input class="form-control" type="text" name="map_url" value="{{ old('map_url', $item->map_url) }}">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @unless($isPickup)
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Image</h4>
                                    <div class="row">
                                        @include('admin.includes.media_field', ['field' => 'image', 'label' => 'Image', 'cols' => 6, 'current' => $item->getRawOriginal('image'), 'preview' => $item->image])
                                    </div>
                                </div>
                            </div>
                        @endunless
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Settings</h4>

                                @unless($isPickup)
                                    <div class="mb-3">
                                        <label class="form-label">Type <span class="text-danger">*</span></label>
                                        <input class="form-control @error('type') is-invalid @enderror" type="text" name="type" value="{{ old('type', $item->type) }}">
                                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                @endunless

                                @if($resource === 'destinations')
                                    <div class="mb-3">
                                        <label class="form-label">Parent destination</label>
                                        <select class="form-select" name="parent_id">
                                            <option value="">None</option>
                                            @foreach($parents as $parent)
                                                <option value="{{ $parent->id }}" @selected(old('parent_id', $item->parent_id) == $parent->id)>{{ $parent->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if($resource === 'transportations')
                                    <div class="mb-3">
                                        <label class="form-label">Seats</label>
                                        <input class="form-control" type="number" name="seat_count" value="{{ old('seat_count', $item->seat_count) }}">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Status</h4>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="statusSwitch" value="1"
                                           @checked(old('is_active', $item->exists ? $item->is_active : true))>
                                    <label class="form-check-label" for="statusSwitch">Active</label>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Display order</label>
                                    <input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
                                    <small class="text-muted">Lower numbers are shown first.</small>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ri-save-line align-middle me-1"></i> Save
                                    </button>
                                    <a href="{{ route('tour-catalogue.index', $resource) }}" class="btn btn-light">
                                        <i class="ri-arrow-left-line align-middle me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@include('admin.includes.footer')
