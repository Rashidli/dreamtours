@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php($labels = App\Support\SettingLabels::class)
@php($grouped = $labels::group($settings))
{{-- Section intros for the Shopify-style layout: title + one line on the left, fields on the right. --}}
@php($intros = [
    'Brand' => ['ri-palette-line', 'The logo and browser icon used across the website.'],
    'Company' => ['ri-building-line', 'Your site name and the texts in the footer.'],
    'Contact' => ['ri-phone-line', 'How visitors reach you, and where the website sends its emails.'],
    'Staff alerts' => ['ri-notification-3-line', 'When the admin warns staff about nearly full trips, unpaid balances and upcoming departures.'],
    'Booking & payments' => ['ri-bank-card-line', 'Defaults for deposits, balance due dates and how customers pay.'],
    'Analytics & cookies' => ['ri-bar-chart-line', 'Tracking codes. They load only after a visitor accepts cookies.'],
    'Navigation buttons' => ['ri-links-line', 'Where the buttons in the website header link to.'],
])
{{-- The theme's overflow:hidden on .main-content stops position:sticky; clip looks the same but lets the save bar follow the scroll. --}}
<style>.main-content{overflow:clip}</style>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="tm-settings">
                <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="tm-title"><h4>Global settings</h4><span class="text-muted">Website-wide details. Changes take effect on the site immediately.</span></div>
                </div>

                @include('admin.includes.flash')

                @include('admin.includes.validation_errors')

                <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" id="settings-form" class="tm-form">
                    @csrf
                    @method('PUT')

                    @foreach($grouped as $title => $items)
                        @php([$icon, $intro] = $intros[$title] ?? ['ri-settings-3-line', 'Other settings used by the website.'])
                        <section class="tm-set-section" id="settings-{{ \Illuminate\Support\Str::slug($title) }}">
                            <div class="tm-set-aside">
                                <h2><i class="{{ $icon }}" aria-hidden="true"></i>{{ $title }}</h2>
                                <p>{{ $intro }}</p>
                            </div>
                            <div class="card"><div class="card-body">
                                @foreach($items as $setting)
                                    @php($value = old('settings.'.$setting->key, $setting->value))
                                    <div class="tm-set-field">
                                        @if(in_array($setting->key, $labels::TOGGLES, true))
                                            {{-- Hidden 0 + switch 1: posts the same values as the old Show/Hide select. --}}
                                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                            <div class="form-check form-switch tf-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="1" @checked($value !== '0')>
                                                <label class="form-check-label" for="setting-{{ $setting->key }}">{{ $labels::label($setting->key) }}@if($labels::hint($setting->key))<small>{{ $labels::hint($setting->key) }}</small>@endif</label>
                                            </div>
                                        @else
                                            <label class="fw-semibold" for="setting-{{ $setting->key }}">{{ $labels::label($setting->key) }}</label>

                                            @if(in_array($setting->key, $labels::MEDIA, true))
                                                @if($preview = setting_image($setting->key))
                                                    <div class="mb-2"><span class="tm-logo-preview {{ $setting->key === 'favicon' ? 'tm-light' : '' }}"><img src="{{ $preview }}" alt="Current {{ strtolower($labels::label($setting->key)) }}"></span></div>
                                                @endif
                                                <input class="form-control" type="file" id="setting-file-{{ $setting->key }}"
                                                       name="setting_file[{{ $setting->key }}]" accept=".png,.jpg,.jpeg,.webp,.svg" aria-label="Upload a new {{ strtolower($labels::label($setting->key)) }}">
                                                <input class="form-control mt-2" type="text" id="setting-{{ $setting->key }}"
                                                       name="settings[{{ $setting->key }}]"
                                                       value="{{ $value }}"
                                                       placeholder="or paste an image URL / stored file name">
                                            @elseif(in_array($setting->key, $labels::LONG, true))
                                                <textarea class="form-control" rows="3" id="setting-{{ $setting->key }}"
                                                          name="settings[{{ $setting->key }}]">{{ $value }}</textarea>
                                            @else
                                                <input class="form-control" type="text" id="setting-{{ $setting->key }}"
                                                       name="settings[{{ $setting->key }}]" value="{{ $value }}">
                                            @endif

                                            @if($labels::hint($setting->key))
                                                <div class="form-text">{{ $labels::hint($setting->key) }}</div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div></div>
                        </section>
                    @endforeach

                    {{-- Stays in view while scrolling the long list of settings --}}
                    <div class="tm-savebar">
                        <span class="tm-savebar-note"><span class="tm-clean-note"><i class="ri-information-line"></i> Changes take effect on the site immediately.</span><span class="tm-dirty-note"><i class="ri-edit-2-line"></i> Unsaved changes</span></span>
                        <div class="tm-savebar-actions">
                            <a href="{{ route('settings.index') }}" class="btn btn-light tm-discard">Discard</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<script>
// Marks the form once something changes, so the save bar says there are unsaved changes.
(function () {
    const form = document.getElementById('settings-form');
    if (!form) return;
    const dirty = function () { form.classList.add('tm-is-dirty'); };
    form.addEventListener('input', dirty);
    form.addEventListener('change', dirty);
})();
</script>
@include('admin.includes.footer')
