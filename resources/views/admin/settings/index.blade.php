@include('admin.includes.header')
@php($labels = App\Support\SettingLabels::class)
@php($grouped = $labels::group($settings))
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Global settings</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Global settings</li>
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

            <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-8">
                        @foreach($grouped as $title => $items)
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">{{ $title }}</h4>

                                    @foreach($items as $setting)
                                        <div class="mb-3">
                                            <label class="form-label" for="setting-{{ $setting->key }}">{{ $labels::label($setting->key) }}</label>

                                            @if(in_array($setting->key, $labels::MEDIA, true))
                                                <input class="form-control" type="file" id="setting-file-{{ $setting->key }}"
                                                       name="setting_file[{{ $setting->key }}]" accept=".png,.jpg,.jpeg,.webp,.svg">
                                                <input class="form-control mt-2" type="text" id="setting-{{ $setting->key }}"
                                                       name="settings[{{ $setting->key }}]"
                                                       value="{{ old('settings.'.$setting->key, $setting->value) }}"
                                                       placeholder="or paste an image URL / stored file name">
                                                @if($preview = setting_image($setting->key))
                                                    <div class="mt-2 p-2 rounded" style="background:#0B111F;display:inline-block">
                                                        <img src="{{ $preview }}" alt="Logo" style="height:44px;width:auto">
                                                    </div>
                                                @endif
                                            @elseif(in_array($setting->key, $labels::TOGGLES, true))
                                                <select class="form-select" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]">
                                                    <option value="1" @selected(old('settings.'.$setting->key, $setting->value) !== '0')>Show</option>
                                                    <option value="0" @selected(old('settings.'.$setting->key, $setting->value) === '0')>Hide</option>
                                                </select>
                                            @elseif(in_array($setting->key, $labels::LONG, true))
                                                <textarea class="form-control" rows="3" id="setting-{{ $setting->key }}"
                                                          name="settings[{{ $setting->key }}]">{{ old('settings.'.$setting->key, $setting->value) }}</textarea>
                                            @else
                                                <input class="form-control" type="text" id="setting-{{ $setting->key }}"
                                                       name="settings[{{ $setting->key }}]" value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                            @endif

                                            @if($labels::hint($setting->key))
                                                <small class="text-muted">{{ $labels::hint($setting->key) }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-save-line align-middle me-1"></i> Save
                                </button>
                                <small class="d-block mt-2 text-muted">Changes take effect on the site immediately.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@include('admin.includes.footer')
