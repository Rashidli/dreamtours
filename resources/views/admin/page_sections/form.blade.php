@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $locales = config('translatable.locales');
  $multi = count($locales) > 1;
  $advancedOptions = \Illuminate\Support\Arr::except($section->options ?? [], ['images', 'alt']);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $section->title ?: $section->key, 'parent' => ['Page sections', route('page-sections.index')], 'current' => $section->title ?: $section->key,
  'badge' => $section->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" enctype="multipart/form-data" action="{{ route('page-sections.update', $section) }}">
  @csrf
  @method('PUT')
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-file-text-line', 'title' => 'Content', 'hint' => 'The texts of this section. Leave a field empty to hide it.'])
      @include('admin.includes.lang_tabs', ['id' => 'section'])
      <div class="tab-content">
        @foreach($locales as $lang)
          @php $t = $section->translate($lang); $sfx = $multi ? ' ('.strtoupper($lang).')' : ''; @endphp
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="section-{{ $lang }}" role="tabpanel">
            <div class="row g-3 mb-3">
              <div class="col-md-5"><label class="fw-semibold" for="sec-{{ $lang }}-eyebrow">Eyebrow{{ $sfx }}</label><input id="sec-{{ $lang }}-eyebrow" class="form-control" type="text" name="{{ $lang }}[eyebrow]" value="{{ old("$lang.eyebrow", $t?->eyebrow) }}"><div class="form-text">The small line above the heading.</div></div>
              <div class="col-md-7"><label class="fw-semibold" for="sec-{{ $lang }}-title">Title{{ $sfx }}</label><input id="sec-{{ $lang }}-title" class="form-control" type="text" name="{{ $lang }}[title]" value="{{ old("$lang.title", $t?->title) }}"></div>
            </div>
            <div class="mb-3"><label class="fw-semibold" for="sec-{{ $lang }}-subtitle">Subtitle{{ $sfx }}</label><textarea id="sec-{{ $lang }}-subtitle" class="form-control" rows="2" name="{{ $lang }}[subtitle]">{{ old("$lang.subtitle", $t?->subtitle) }}</textarea></div>
            <div class="mb-3"><label class="fw-semibold">Text{{ $sfx }}</label><textarea class="form-control js-editor" rows="7" name="{{ $lang }}[body]">{{ old("$lang.body", $t?->body) }}</textarea></div>
            <div class="mb-0"><label class="fw-semibold" for="sec-{{ $lang }}-button">Button text{{ $sfx }}</label><input id="sec-{{ $lang }}-button" class="form-control" type="text" name="{{ $lang }}[button_text]" value="{{ old("$lang.button_text", $t?->button_text) }}"><div class="form-text">The button link is set in the side panel.</div></div>
          </div>
        @endforeach
      </div>
    </div></div>

    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-image-2-line', 'title' => 'Images', 'hint' => 'Not every layout shows every image; leave a slot empty to keep the default photo.'])
      <div class="row">
        @include('admin.cms_ui.media_field', ['field' => 'image', 'label' => 'Section image', 'cols' => 6, 'current' => $section->getRawOriginal('image'), 'preview' => $section->image])
        @include('admin.cms_ui.media_field', ['field' => 'background_image', 'label' => 'Background image', 'cols' => 6, 'current' => $section->getRawOriginal('background_image'), 'preview' => $section->background_image])
        @include('admin.cms_ui.media_field', ['field' => 'image_2', 'label' => 'Image 2 (photo collages)', 'cols' => 6, 'current' => $section->options['images']['image_2'] ?? null, 'preview' => $section->extraImage('image_2')])
        @include('admin.cms_ui.media_field', ['field' => 'image_3', 'label' => 'Image 3 (photo collages)', 'cols' => 6, 'current' => $section->options['images']['image_3'] ?? null, 'preview' => $section->extraImage('image_3')])
      </div>
      <div class="tf-subtle">
        <div class="tf-label mb-1">Alternative text</div>
        <div class="form-text mt-0 mb-3">Describes each photo for screen readers and search engines.</div>
        <div class="row g-3">
          @foreach(['image' => 'Section image', 'background_image' => 'Background image', 'image_2' => 'Image 2', 'image_3' => 'Image 3'] as $slot => $slotLabel)
            <div class="col-md-6"><label class="fw-semibold" for="alt-{{ $slot }}">{{ $slotLabel }}</label><input id="alt-{{ $slot }}" class="form-control" name="alt[{{ $slot }}]" value="{{ old('alt.'.$slot, $section->options['alt'][$slot] ?? '') }}" maxlength="255"></div>
          @endforeach
        </div>
      </div>
    </div></div>
  </div>

  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Publish</h5>
      <div class="cms-meta mb-2"><span>{{ $section->page?->title }} · <code>{{ $section->key }}</code></span><span>Where this section sits on the page is part of the website code; only its content is editable here.</span></div>
      <div class="cms-switches">
        @include('admin.cms_ui.status_switch', ['id' => 'statusSwitch', 'checked' => old('is_active', $section->is_active), 'label' => 'Active', 'hint' => 'When off, the whole section is hidden on the website. Its content stays saved here.'])
      </div>
      @include('admin.cms_ui.save_actions', ['label' => 'Save changes', 'back' => ['All sections', route('page-sections.index')]])
    </div></div>

    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Button</h5>
      <label class="fw-semibold" for="sec-button-url">Button URL</label>
      <input id="sec-button-url" class="form-control" type="text" name="button_url" value="{{ old('button_url', $section->button_url) }}" placeholder="/one-day-tours">
      <div class="form-text">Internal links start with “/”.</div>
    </div></div>

    <div class="card"><div class="card-body">
      <details class="cms-details-toggle" @if($errors->has('options_json') || old('options_json') || $advancedOptions) open @endif>
        <summary>Advanced options</summary>
        <textarea class="form-control font-monospace" rows="4" name="options_json" aria-label="Advanced options (JSON)">{{ old('options_json', $advancedOptions ? json_encode($advancedOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
        <div class="form-text">JSON, for example an extra CSS class. May be left empty.</div>
      </details>
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
