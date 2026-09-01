@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Yeni Portfolio</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('portfolios.index') }}">Portfolio</a></li>
                                <li class="breadcrumb-item active">Yeni</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('portfolios.store') }}" method="post" enctype="multipart/form-data">
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
                                                        <label class="form-label fw-bold">Başlıq <span class="text-danger">*</span></label>
                                                        <input class="form-control form-control-lg" type="text" value="{{ old($lang . '_title') }}" name="{{ $lang }}_title" placeholder="Portfolio başlığı...">
                                                        @error("{$lang}_title")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Qısa Mətn</label>
                                                        <textarea class="form-control" rows="3" name="{{ $lang }}_short_description" placeholder="Qısa təsvir...">{{ old($lang . '_short_description') }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tam Mətn</label>
                                                        <textarea id="editor_{{ $lang }}" class="form-control" name="{{ $lang }}_description" rows="6">{{ old($lang . '_description') }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Şəkil Title Tag</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_img_title" value="{{ old($lang . '_img_title') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Şəkil Alt Tag</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_img_alt" value="{{ old($lang . '_img_alt') }}">
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
                                <h5 class="card-title mb-0 text-white">Parametrlər</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Əsas Şəkil <span class="text-danger">*</span></label>
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    @error('image')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Xidmət</label>
                                    <select class="form-select" name="service_id">
                                        <option value="">Seçin...</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>{{ $service->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Müştəri</label>
                                    <input class="form-control" type="text" name="client" value="{{ old('client') }}" placeholder="Müştəri adı...">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ünvan</label>
                                    <input class="form-control" type="text" name="location" value="{{ old('location') }}" placeholder="Bakı, Azərbaycan">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Əlavə Şəkillər</label>
                                    <input class="form-control" type="file" name="images[]" accept="image/*" multiple>
                                    <small class="text-muted">Birdən çox şəkil seçə bilərsiniz</small>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Aktiv</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured">Ana səhifədə göstər</label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i> Yadda Saxla
                                    </button>
                                    <a href="{{ route('portfolios.index') }}" class="btn btn-outline-secondary">
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
