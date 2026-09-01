@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Yeni Mehsul</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Mehsullar</a></li>
                                <li class="breadcrumb-item active">Yeni</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('products.store') }}" method="post" enctype="multipart/form-data">
                @csrf
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
                                                        <input class="form-control form-control-lg" type="text" value="{{ old($lang . '_title') }}" name="{{ $lang }}_title" placeholder="Mehsul basliqi...">
                                                        @error("{$lang}_title")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Qisa Metn</label>
                                                        <textarea class="form-control" rows="3" name="{{ $lang }}_short_description" placeholder="Qisa tanim...">{{ old($lang . '_short_description') }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tam Metn</label>
                                                        <textarea id="editor_{{ $lang }}" class="form-control" name="{{ $lang }}_description" rows="6">{{ old($lang . '_description') }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">One cixan xususiyyetler</label>
                                                        <textarea class="form-control" rows="4" name="{{ $lang }}_features" placeholder="Her setirde bir xususiyyet yazin...">{{ old($lang . '_features') }}</textarea>
                                                        <small class="text-muted">Her setirde bir xususiyyet yazin</small>
                                                    </div>
                                                </div>

                                                <div class="col-12"><hr></div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Title</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_meta_title" value="{{ old("{$lang}_meta_title") }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Keywords</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_meta_keywords" value="{{ old("{$lang}_meta_keywords") }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Description</label>
                                                        <textarea class="form-control" name="{{ $lang }}_meta_description" rows="2">{{ old("{$lang}_meta_description") }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
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
                                    <label class="form-label fw-bold">Esas Sekil <span class="text-danger">*</span></label>
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    @error('image')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Qalereya Sekilleri</label>
                                    <input class="form-control" type="file" name="gallery[]" accept="image/*" multiple>
                                    <small class="text-muted">Bir nece sekil sece bilersiz</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Kateqoriya</label>
                                    <select class="form-select" name="product_category_id">
                                        <option value="">Secin...</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('product_category_id') == $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Qiymet (AZN) <span class="text-danger">*</span></label>
                                            <input class="form-control" type="number" step="0.01" min="0" name="price" value="{{ old('price', 0) }}">
                                            @error('price')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Kohne Qiymet</label>
                                            <input class="form-control" type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Xususiyyetler (Filter)</label>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                        @foreach($features as $feature)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="feature_ids[]" value="{{ $feature->id }}" id="feature_{{ $feature->id }}"
                                                    {{ in_array($feature->id, old('feature_ids', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="feature_{{ $feature->id }}">{{ $feature->title }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                        <label class="form-check-label" for="is_active">Aktiv</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
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
@include('admin.includes.footer')
