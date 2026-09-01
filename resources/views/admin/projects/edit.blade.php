@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Layihə Redakte</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Layihələr</a></li>
                                <li class="breadcrumb-item active">{{ $project->translate('az')?->title }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('projects.update', $project->id) }}" method="post" enctype="multipart/form-data">
                @method('PUT')
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
                                                        <label class="form-label fw-bold">Bashliq <span class="text-danger">*</span></label>
                                                        <input class="form-control form-control-lg" type="text" value="{{ $project->translate($lang)?->title }}" name="{{ $lang }}_title" placeholder="Layihe bashligi...">
                                                        @error("{$lang}_title")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Qisa Metn</label>
                                                        <textarea class="form-control" rows="3" name="{{ $lang }}_short_description" placeholder="Qisa tanim...">{{ $project->translate($lang)?->short_description }}</textarea>
                                                        @error("{$lang}_short_description")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tam Metn <span class="text-danger">*</span></label>
                                                        <textarea id="editor_{{ $lang }}" class="form-control" name="{{ $lang }}_description" rows="6">{{ $project->translate($lang)?->description }}</textarea>
                                                        @error("{$lang}_description")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Müştəri</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_client" value="{{ $project->translate($lang)?->client }}" placeholder="Müştəri adı...">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Məkan</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_location" value="{{ $project->translate($lang)?->location }}" placeholder="Ünvan...">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Təhvil tarixi</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_delivery_date" value="{{ $project->translate($lang)?->delivery_date }}" placeholder="Məs: Yan 2025-Fev 2025">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shekil Title Tag</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_img_title" value="{{ $project->translate($lang)?->img_title }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shekil Alt Tag</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_img_alt" value="{{ $project->translate($lang)?->img_alt }}">
                                                    </div>
                                                </div>

                                                <div class="col-12"><hr></div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Title</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_meta_title" value="{{ $project->translate($lang)?->meta_title }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Description</label>
                                                        <textarea class="form-control" name="{{ $lang }}_meta_description" rows="2">{{ $project->translate($lang)?->meta_description }}</textarea>
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
                                    <label class="form-label fw-bold">Movcud Shekil</label>
                                    <div class="text-center p-2 bg-light rounded mb-2">
                                        <img src="{{ asset('storage/' . $project->image) }}" class="img-fluid rounded" style="max-height: 150px;" alt="{{ $project->translate('az')?->img_alt }}">
                                    </div>
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    <small class="text-muted">Bosh buraxsaniz deyishmeyecek</small>
                                    @error('image')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Xidmət</label>
                                    <select class="form-select" name="service_id">
                                        <option value="">Seçin...</option>
                                        @foreach($services as $svc)
                                            <option value="{{ $svc->id }}" {{ $project->service_id == $svc->id ? 'selected' : '' }}>{{ $svc->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Qalereya Şəkilləri</label>
                                    @if($project->gallery)
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            @foreach($project->gallery as $gImg)
                                                <img src="{{ asset('storage/' . $gImg) }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            @endforeach
                                        </div>
                                    @endif
                                    <input class="form-control" type="file" name="gallery[]" accept="image/*" multiple>
                                    <small class="text-muted">Yeni şəkillər yükləsəniz köhnələr əvəz olunacaq</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Before Şəkil</label>
                                    @if($project->before_image)
                                        <div class="text-center p-1 bg-light rounded mb-1">
                                            <img src="{{ asset('storage/' . $project->before_image) }}" class="img-fluid rounded" style="max-height: 80px;">
                                        </div>
                                    @endif
                                    <input class="form-control" type="file" name="before_image" accept="image/*">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">After Şəkil</label>
                                    @if($project->after_image)
                                        <div class="text-center p-1 bg-light rounded mb-1">
                                            <img src="{{ asset('storage/' . $project->after_image) }}" class="img-fluid rounded" style="max-height: 80px;">
                                        </div>
                                    @endif
                                    <input class="form-control" type="file" name="after_image" accept="image/*">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="statusSwitch" {{ $project->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="statusSwitch">Aktiv</label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i> Yenile
                                    </button>
                                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
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
