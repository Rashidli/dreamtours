@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Xidmət Redakte</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('services.index') }}">Xidmətlər</a></li>
                                <li class="breadcrumb-item active">{{ $service->translate('az')?->title }}</li>
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

            <form action="{{ route('services.update', $service->id) }}" method="post" enctype="multipart/form-data">
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
                                                        <input class="form-control form-control-lg" type="text" value="{{ $service->translate($lang)?->title }}" name="{{ $lang }}_title" placeholder="Xidmet bashligi...">
                                                        @error("{$lang}_title")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Qisa Metn</label>
                                                        <textarea class="form-control" rows="3" name="{{ $lang }}_short_description" placeholder="Qisa tanim...">{{ $service->translate($lang)?->short_description }}</textarea>
                                                        @error("{$lang}_short_description")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tam Metn <span class="text-danger">*</span></label>
                                                        <textarea id="editor_{{ $lang }}" class="form-control" name="{{ $lang }}_description" rows="6">{{ $service->translate($lang)?->description }}</textarea>
                                                        @error("{$lang}_description")
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shekil Title Tag</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_img_title" value="{{ $service->translate($lang)?->img_title }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shekil Alt Tag</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_img_alt" value="{{ $service->translate($lang)?->img_alt }}">
                                                    </div>
                                                </div>

                                                <div class="col-12"><hr></div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Title</label>
                                                        <input class="form-control" type="text" name="{{ $lang }}_meta_title" value="{{ $service->translate($lang)?->meta_title }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Description</label>
                                                        <textarea class="form-control" name="{{ $lang }}_meta_description" rows="2">{{ $service->translate($lang)?->meta_description }}</textarea>
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
                                        <img src="{{ asset('storage/' . $service->image) }}" class="img-fluid rounded" style="max-height: 150px;" alt="{{ $service->translate('az')?->img_alt }}">
                                    </div>
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    <small class="text-muted">Bosh buraxsaniz deyishmeyecek</small>
                                    @error('image')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Icon (FontAwesome)</label>
                                    @if($service->icon)
                                        <div class="text-center p-3 bg-light rounded mb-2">
                                            <i class="{{ $service->icon }}" style="font-size: 40px; color: var(--bs-primary);"></i>
                                        </div>
                                    @endif
                                    <select class="form-select icon-select" name="icon" id="iconSelect">
                                        <option value="">-- Icon seçin --</option>
                                        <option value="fa-solid fa-wrench" {{ $service->icon == 'fa-solid fa-wrench' ? 'selected' : '' }}>🔧 Açar (Wrench)</option>
                                        <option value="fa-solid fa-droplet" {{ $service->icon == 'fa-solid fa-droplet' ? 'selected' : '' }}>💧 Damcı (Droplet)</option>
                                        <option value="fa-solid fa-faucet-drip" {{ $service->icon == 'fa-solid fa-faucet-drip' ? 'selected' : '' }}>🚰 Kran (Faucet)</option>
                                        <option value="fa-solid fa-shower" {{ $service->icon == 'fa-solid fa-shower' ? 'selected' : '' }}>🚿 Duş (Shower)</option>
                                        <option value="fa-solid fa-bath" {{ $service->icon == 'fa-solid fa-bath' ? 'selected' : '' }}>🛁 Vanna (Bath)</option>
                                        <option value="fa-solid fa-toilet" {{ $service->icon == 'fa-solid fa-toilet' ? 'selected' : '' }}>🚽 Tualet (Toilet)</option>
                                        <option value="fa-solid fa-sink" {{ $service->icon == 'fa-solid fa-sink' ? 'selected' : '' }}>🪥 Leysan (Sink)</option>
                                        <option value="fa-solid fa-hot-tub-person" {{ $service->icon == 'fa-solid fa-hot-tub-person' ? 'selected' : '' }}>♨️ Jakuzi (Hot Tub)</option>
                                        <option value="fa-solid fa-temperature-high" {{ $service->icon == 'fa-solid fa-temperature-high' ? 'selected' : '' }}>🌡️ İstilik (Temperature)</option>
                                        <option value="fa-solid fa-fire" {{ $service->icon == 'fa-solid fa-fire' ? 'selected' : '' }}>🔥 Alov (Fire)</option>
                                        <option value="fa-solid fa-house-chimney" {{ $service->icon == 'fa-solid fa-house-chimney' ? 'selected' : '' }}>🏠 Ev (House)</option>
                                        <option value="fa-solid fa-water" {{ $service->icon == 'fa-solid fa-water' ? 'selected' : '' }}>🌊 Su (Water)</option>
                                        <option value="fa-solid fa-pump-soap" {{ $service->icon == 'fa-solid fa-pump-soap' ? 'selected' : '' }}>🧴 Sabun (Pump)</option>
                                        <option value="fa-solid fa-toolbox" {{ $service->icon == 'fa-solid fa-toolbox' ? 'selected' : '' }}>🧰 Alət qutusu (Toolbox)</option>
                                        <option value="fa-solid fa-screwdriver-wrench" {{ $service->icon == 'fa-solid fa-screwdriver-wrench' ? 'selected' : '' }}>🔧 Alətlər (Tools)</option>
                                        <option value="fa-solid fa-gear" {{ $service->icon == 'fa-solid fa-gear' ? 'selected' : '' }}>⚙️ Dişli (Gear)</option>
                                        <option value="fa-solid fa-plug" {{ $service->icon == 'fa-solid fa-plug' ? 'selected' : '' }}>🔌 Fiş (Plug)</option>
                                        <option value="fa-solid fa-bolt" {{ $service->icon == 'fa-solid fa-bolt' ? 'selected' : '' }}>⚡ İldırım (Bolt)</option>
                                    </select>
                                    <small class="text-muted">Xidmət kartında göstəriləcək icon</small>
                                    @error('icon')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="statusSwitch" {{ $service->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="statusSwitch">Aktiv</label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i> Yenile
                                    </button>
                                    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
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
