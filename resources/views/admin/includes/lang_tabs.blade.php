{{--
    Language tab strip, driven by config('translatable.locales').
    Today that is just English, so a single tab renders; adding locales to the config
    grows the strip with no change here or in the forms that include it.

    Usage: @include('admin.includes.lang_tabs', ['id' => 'pageLang'])
--}}
@php($locales = config('translatable.locales'))

@if(count($locales) > 1)
    <ul class="nav nav-tabs" id="{{ $id }}Tabs" role="tablist">
        @foreach($locales as $lang)
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                   id="{{ $id }}-{{ $lang }}-tab" data-bs-toggle="tab" href="#{{ $id }}-{{ $lang }}"
                   role="tab" aria-controls="{{ $id }}-{{ $lang }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ strtoupper($lang) }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
