@include('admin.includes.header')
@php($locales = config('translatable.locales'))
@php($labels = ['visa-services' => 'Visa service fee', 'gallery-albums' => 'Gallery album', 'gallery-categories' => 'Gallery category'])
@php($title = $labels[$resource] ?? str($resource)->replace('-', ' ')->title())
@php($isAlbum = $resource === 'gallery-albums')
@php($isVisa = $resource === 'visa-services')
@php($nameField = $isAlbum ? 'title' : 'name')
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
                                <li class="breadcrumb-item"><a href="{{ route('domain-content.index', $resource) }}">{{ $title }}</a></li>
                                <li class="breadcrumb-item active">{{ $item->exists ? data_get($item, $nameField) : 'New' }}</li>
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
                  action="{{ $item->exists ? route('domain-content.update', [$resource, $item]) : route('domain-content.store', $resource) }}">
                @csrf
                @if($item->exists) @method('PUT') @endif

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Details</h4>

                                @include('admin.includes.lang_tabs', ['id' => 'dc'])

                                <div class="tab-content pt-3">
                                    @foreach($locales as $lang)
                                        @php($t = $item->translate($lang))
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="dc-{{ $lang }}" role="tabpanel">
                                            <div class="mb-3">
                                                <label class="form-label">{{ $isAlbum ? 'Title' : 'Name' }} ({{ strtoupper($lang) }}) <span class="text-danger">*</span></label>
                                                <input class="form-control @error("$lang.$nameField") is-invalid @enderror" type="text"
                                                       name="{{ $lang }}[{{ $nameField }}]" value="{{ old("$lang.$nameField", $t?->{$nameField}) }}">
                                                @error("$lang.$nameField") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            @unless($resource === 'gallery-categories')
                                                <div class="mb-3">
                                                    <label class="form-label">Description ({{ strtoupper($lang) }})</label>
                                                    <textarea class="form-control" rows="4" name="{{ $lang }}[description]">{{ old("$lang.description", $t?->description) }}</textarea>
                                                </div>
                                            @endunless

                                            @if($isVisa)
                                                <div class="mb-3">
                                                    <label class="form-label">Required documents ({{ strtoupper($lang) }})</label>
                                                    <textarea class="form-control" rows="5" name="{{ $lang }}[required_documents]">{{ old("$lang.required_documents", $t?->required_documents) }}</textarea>
                                                    <small class="text-muted">One document per line.</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Note ({{ strtoupper($lang) }})</label>
                                                    <textarea class="form-control" rows="3" name="{{ $lang }}[disclaimer]">{{ old("$lang.disclaimer", $t?->disclaimer) }}</textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if($isAlbum)
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Cover</h4>
                                    <div class="row">
                                        @include('admin.includes.media_field', ['field' => 'cover_image', 'label' => 'Cover image', 'cols' => 6, 'current' => $item->getRawOriginal('cover_image'), 'preview' => $item->cover_image])
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-4">
                        @if($isVisa)
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Country & fees</h4>
                                    <div class="mb-3">
                                        <label class="form-label">Country</label>
                                        <input class="form-control" type="text" name="country" value="{{ old('country', $item->country) }}" placeholder="e.g. United Kingdom">
                                        <small class="text-muted">Type the country, or link it to a destination below.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Linked destination <small class="text-muted">(optional)</small></label>
                                        <select class="form-select" name="destination_id">
                                            <option value="">None</option>
                                            @foreach($destinations as $d)
                                                <option value="{{ $d->id }}" @selected(old('destination_id', $item->destination_id) == $d->id)>{{ $d->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-7 mb-3">
                                            <label class="form-label">DreamTour service fee</label>
                                            <input class="form-control" type="text" name="price" value="{{ old('price', $item->price) }}">
                                        </div>
                                        <div class="col-5 mb-3">
                                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                                            <input class="form-control" type="text" name="currency" maxlength="3" value="{{ old('currency', $item->currency ?: 'CAD') }}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Government / third-party fee <small class="text-muted">(optional, same currency)</small></label>
                                        <input class="form-control" type="text" name="third_party_fee" value="{{ old('third_party_fee', $item->third_party_fee) }}" placeholder="leave empty if not included">
                                        <input class="form-control mt-2" type="text" name="third_party_fee_note" value="{{ old('third_party_fee_note', $item->third_party_fee_note) }}" placeholder="note, e.g. paid directly to the consulate">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Processing time</label>
                                        <input class="form-control" type="text" name="processing_time" value="{{ old('processing_time', $item->processing_time) }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($isAlbum)
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Links</h4>
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="gallery_category_id">
                                            <option value="">None</option>
                                            @foreach($categories as $c)
                                                <option value="{{ $c->id }}" @selected(old('gallery_category_id', $item->gallery_category_id) == $c->id)>{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tour</label>
                                        <select class="form-select" name="tour_id">
                                            <option value="">None</option>
                                            @foreach($tours as $t)
                                                <option value="{{ $t->id }}" @selected(old('tour_id', $item->tour_id) == $t->id)>{{ $t->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Destination</label>
                                        <select class="form-select" name="destination_id">
                                            <option value="">None</option>
                                            @foreach($destinations as $d)
                                                <option value="{{ $d->id }}" @selected(old('destination_id', $item->destination_id) == $d->id)>{{ $d->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tour date</label>
                                        <input class="form-control" type="date" name="tour_date" value="{{ old('tour_date', $item->tour_date?->format('Y-m-d')) }}">
                                    </div>
                                </div>
                            </div>
                        @endif

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
                                    <a href="{{ route('domain-content.index', $resource) }}" class="btn btn-light">
                                        <i class="ri-arrow-left-line align-middle me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if($isAlbum && $item->exists)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0">Album media</h4>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('gallery-media.index', $item) }}">
                                        <i class="ri-image-line align-middle me-1"></i> Media list
                                    </a>
                                </div>

                                <form class="row g-2 mb-3" method="post" enctype="multipart/form-data" action="{{ route('gallery-media.store', $item) }}">
                                    @csrf
                                    <div class="col-md-2">
                                        <select class="form-select" name="type"><option>image</option><option>video</option></select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="file" class="form-control" name="file" accept="image/*,video/mp4,video/webm,video/quicktime">
                                    </div>
                                    <div class="col-md-3">
                                        <input class="form-control" name="path" placeholder="…or a link">
                                    </div>
                                    <div class="col-md-2">
                                        <input class="form-control" name="en[caption]" placeholder="Title">
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-primary w-100"><i class="ri-add-line"></i></button>
                                    </div>
                                </form>

                                <ul class="list-group">
                                    @forelse($item->media as $medium)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span class="d-flex align-items-center gap-2">
                                                @if($medium->type === 'image')
                                                    <img src="{{ $medium->path }}" alt="" style="height:40px;width:60px;object-fit:cover;border-radius:4px">
                                                @endif
                                                <span>{{ $medium->caption ?: $medium->getRawOriginal('path') }}</span>
                                            </span>
                                            <span>
                                                <a class="btn btn-sm btn-primary" href="{{ route('gallery-media.edit', [$item, $medium]) }}" title="Edit">
                                                    <i class="ri-pencil-line"></i>
                                                </a>
                                                <form class="d-inline" method="post" action="{{ route('gallery-media.destroy', [$item, $medium]) }}">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this media item?')">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">No media yet.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@include('admin.includes.footer')
