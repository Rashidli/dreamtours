@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Əlaqə Məlumatı Redaktə</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('contact_items.index') }}">Əlaqə Məlumatları</a></li>
                                <li class="breadcrumb-item active">{{ $contact_item->translate('az')?->title }}</li>
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

            <form action="{{ route('contact_items.update', $contact_item->id) }}" method="post">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0 text-white">Dil Versiyaları</h5>
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
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Başlıq <span class="text-danger">*</span></label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_title" value="{{ old("{$lang}_title", $contact_item->translate($lang)?->title) }}" placeholder="Məsələn: Telefon, Ünvan, E-poçt...">
                                                        @error("{$lang}_title")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Dəyər <span class="text-danger">*</span></label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_value" value="{{ old("{$lang}_value", $contact_item->translate($lang)?->value) }}" placeholder="Məsələn: +994 50 123 45 67">
                                                        @error("{$lang}_value")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
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
                                    <label class="form-label fw-bold">Icon (class)</label>
                                    <input class="form-control" type="text" name="icon" value="{{ old('icon', $contact_item->icon) }}" placeholder="fas fa-phone">
                                    @if($contact_item->icon)
                                        <div class="mt-2 p-2 bg-light rounded text-center">
                                            <i class="{{ $contact_item->icon }}" style="font-size: 24px;"></i>
                                            <small class="d-block text-muted">Mövcud icon</small>
                                        </div>
                                    @endif
                                    <small class="text-muted">Məsələn: fas fa-phone, fas fa-envelope, fas fa-map-marker-alt</small>
                                    @error('icon')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Link</label>
                                    <input class="form-control" type="text" name="link" value="{{ old('link', $contact_item->link) }}" placeholder="tel:+994501234567">
                                    <small class="text-muted">Məsələn: tel:+994501234567, mailto:info@site.az</small>
                                    @error('link')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="statusSwitch" {{ $contact_item->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="statusSwitch">Aktiv</label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i> Yenilə
                                    </button>
                                    <a href="{{ route('contact_items.index') }}" class="btn btn-outline-secondary">
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
