@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <form action="{{ route('service_categories.store') }}" method="post">
                @csrf
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Xidmət kateqoriyası əlavə et</h4>

                        <ul class="nav nav-tabs" id="langTabs" role="tablist">
                            @foreach(['az', 'en', 'ru'] as $lang)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $lang }}-tab" data-bs-toggle="tab" href="#{{ $lang }}" role="tab" aria-controls="{{ $lang }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ strtoupper($lang) }}</a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content" id="langTabsContent">
                            @foreach(['az', 'en', 'ru'] as $lang)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $lang }}" role="tabpanel" aria-labelledby="{{ $lang }}-tab">
                                    <div class="mb-3">
                                        <label class="col-form-label">Başlıq {{ strtoupper($lang) }}</label>
                                        <input class="form-control" type="text" value="{{ old($lang . '_title') }}" name="{{ $lang }}_title">
                                        @if($errors->first("{$lang}_title")) <small class="form-text text-danger">{{ $errors->first("{$lang}_title") }}</small> @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-3">
                            <button class="btn btn-primary">Yadda saxla</button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.includes.footer')
