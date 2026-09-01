@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Mehsul Redakte</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Mehsullar</a></li>
                                <li class="breadcrumb-item active">Redakte</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('message'))
                <div class="alert alert-success">{{session('message')}}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{session('error')}}</div>
            @endif

            <form action="{{ route('products.update', $product->id) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0 text-white">Dil Versiyalari</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills nav-justified mb-3" role="tablist">
                                    @foreach(['az', 'en', 'ru'] as $lang)
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link @if($loop->first) active @endif" data-bs-toggle="tab" href="#{{ $lang }}" role="tab">
                                                <i class="fas fa-globe me-1"></i> {{ strtoupper($lang) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    @foreach(['az', 'en', 'ru'] as $lang)
                                        <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $lang }}" role="tabpanel">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Basliq <span class="text-danger">*</span></label>
                                                        <input class="form-control form-control-lg" type="text" value="{{ old($lang . '_title', $product->translate($lang)?->title) }}" name="{{ $lang }}_title" placeholder="Mehsul basliqi...">
                                                        @error("{$lang}_title")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Qisa Metn</label>
                                                        <textarea class="form-control" rows="3" name="{{ $lang }}_short_description" placeholder="Qisa tanim...">{{ old($lang . '_short_description', $product->translate($lang)?->short_description) }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tam Metn</label>
                                                        <textarea id="editor_{{ $lang }}" class="form-control" name="{{ $lang }}_description" rows="6">{{ old($lang . '_description', $product->translate($lang)?->description) }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">One cixan xususiyyetler</label>
                                                        <textarea class="form-control" rows="4" name="{{ $lang }}_features" placeholder="Her setirde bir xususiyyet yazin...">{{ old($lang . '_features', $product->translate($lang)?->features) }}</textarea>
                                                        <small class="text-muted">Her setirde bir xususiyyet yazin</small>
                                                    </div>
                                                </div>

                                                <div class="col-12"><hr></div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Title</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_meta_title" value="{{ old("{$lang}_meta_title", $product->translate($lang)?->meta_title) }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Keywords</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_meta_keywords" value="{{ old("{$lang}_meta_keywords", $product->translate($lang)?->meta_keywords) }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Description</label>
                                                        <textarea class="form-control" name="{{ $lang }}_meta_description" rows="2">{{ old("{$lang}_meta_description", $product->translate($lang)?->meta_description) }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Gallery Images -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Qalereya Sekilleri</h5>
                            </div>
                            <div class="card-body">
                                <div class="row" id="gallery-images">
                                    @foreach($product->images as $image)
                                        <div class="col-md-3 mb-3" id="image-{{ $image->id }}">
                                            <div class="position-relative">
                                                <img src="{{ asset('storage/'.$image->image) }}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" onclick="deleteImage({{ $image->id }})">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Yeni sekiller elave et</label>
                                    <input class="form-control" type="file" name="gallery[]" accept="image/*" multiple>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="card-title mb-0 text-white">Parametrler</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Esas Sekil</label>
                                    @if($product->image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid rounded" style="max-height: 150px;">
                                        </div>
                                    @endif
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    @error('image')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Kateqoriya</label>
                                    <select class="form-select" name="product_category_id">
                                        <option value="">Secin...</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('product_category_id', $product->product_category_id) == $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Qiymet (AZN) <span class="text-danger">*</span></label>
                                            <input class="form-control" type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}">
                                            @error('price')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Kohne Qiymet</label>
                                            <input class="form-control" type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Xususiyyetler (Filter)</label>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                        @php $selectedFeatures = $product->productFeatures->pluck('id')->toArray(); @endphp
                                        @foreach($features as $feature)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="feature_ids[]" value="{{ $feature->id }}" id="feature_{{ $feature->id }}"
                                                    {{ in_array($feature->id, old('feature_ids', $selectedFeatures)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="feature_{{ $feature->id }}">{{ $feature->title }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $product->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Aktiv</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" {{ $product->is_featured ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured">One cixan</label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i> Yadda Saxla
                                    </button>
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Geri
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

<script>
function deleteImage(id) {
    if(confirm('Bu sekili silmek isteyirsiniz?')) {
        fetch('/admin/products/delete-image/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.getElementById('image-' + id).remove();
            }
        });
    }
}
</script>
@include('admin.includes.footer')
