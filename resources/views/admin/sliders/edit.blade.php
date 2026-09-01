@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Slayder Redaktə</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('sliders.index') }}">Slayderlər</a></li>
                                <li class="breadcrumb-item active">Redaktə</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('message'))
                <div class="alert alert-success">{{session('message')}}</div>
            @endif

            <form action="{{ route('sliders.update', $slider->id) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PUT')
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
                                                {{ strtoupper($lang) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    @foreach(['az', 'en', 'ru'] as $lang)
                                        <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $lang }}" role="tabpanel">
                                            <div class="mb-3">
                                                <label class="form-label">Alt Başlıq</label>
                                                <input class="form-control" type="text" value="{{ old($lang . '_subtitle', $slider->translate($lang)?->subtitle) }}" name="{{ $lang }}_subtitle">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Başlıq <span class="text-danger">*</span></label>
                                                <input class="form-control" type="text" value="{{ old($lang . '_title', $slider->translate($lang)?->title) }}" name="{{ $lang }}_title">
                                                @error("{$lang}_title")
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Təsvir</label>
                                                <textarea class="form-control" rows="4" name="{{ $lang }}_description">{{ old($lang . '_description', $slider->translate($lang)?->description) }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Button Mətni</label>
                                                <input class="form-control" type="text" value="{{ old($lang . '_button_text', $slider->translate($lang)?->button_text) }}" name="{{ $lang }}_button_text">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Şəkil</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Slayder Şəkli</label>
                                    @if($slider->image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $slider->image) }}" alt="" style="max-width: 100%; border-radius: 4px;">
                                        </div>
                                    @endif
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    @error("image")
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Parametrlər</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Button Link</label>
                                    <input class="form-control" type="text" value="{{ old('button_link', $slider->button_link) }}" name="button_link" placeholder="/products">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $slider->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label">Aktiv</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Yenilə</button>
                                    <a href="{{ route('sliders.index') }}" class="btn btn-outline-secondary">Geri</a>
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
