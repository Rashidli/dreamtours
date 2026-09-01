@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Xidmət Sifariş Bölməsi</h4>
                    </div>
                </div>
            </div>

            <form action="{{ route('order-service-section.store') }}" method="post" enctype="multipart/form-data">
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
                                                {{ strtoupper($lang) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    @foreach(['az', 'en', 'ru'] as $lang)
                                        <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $lang }}" role="tabpanel">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Başlıq @if($lang == 'az')<span class="text-danger">*</span>@endif</label>
                                                <input class="form-control" type="text" value="{{ old($lang . '_title') }}" name="{{ $lang }}_title" placeholder="Məs: Gəlin xəyalınızdakı bağı birlikdə böyüdək!">
                                                @error("{$lang}_title")
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Təsvir</label>
                                                <textarea class="form-control" rows="3" name="{{ $lang }}_description" placeholder="Məs: Peşəkar komandamız sizə bağınızı daha gözəl etməkdə kömək edəcək.">{{ old($lang . '_description') }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Düymə mətni</label>
                                                <input class="form-control" type="text" value="{{ old($lang . '_button_text') }}" name="{{ $lang }}_button_text" placeholder="Məs: Xidmət sifariş et">
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
                                <h5 class="card-title mb-0">Arxa fon şəkli</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input class="form-control" type="file" name="image" accept="image/*">
                                    <small class="text-muted">Tövsiyə olunan ölçü: 1920x400px</small>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Yadda Saxla</button>
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
