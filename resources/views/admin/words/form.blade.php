@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $locales = config('translatable.locales');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => 'Edit text', 'parent' => ['Site copy', route('words.index')], 'current' => 'Edit'])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ route('words.update', $word) }}">
  @csrf
  @method('PUT')
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-translate-2', 'title' => 'Text', 'hint' => 'This text is swapped in place on the website; its position does not change.'])
      @include('admin.includes.lang_tabs', ['id' => 'word'])
      <div class="tab-content">
        @foreach($locales as $lang)
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="word-{{ $lang }}" role="tabpanel">
            <label class="fw-semibold tf-req" for="word-{{ $lang }}-title">Text{{ count($locales) > 1 ? ' ('.strtoupper($lang).')' : '' }}</label>
            <textarea id="word-{{ $lang }}-title" class="form-control @error("$lang.title") is-invalid @enderror" rows="3" name="{{ $lang }}[title]">{{ old("$lang.title", $word->translate($lang)?->title) }}</textarea>
            @error("$lang.title") <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Keep it about the same length so buttons and headings still fit.</div>
          </div>
        @endforeach
      </div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Save</h5>
      <div class="cms-meta"><span>Changes show on the website as soon as you save.</span></div>
      @include('admin.cms_ui.save_actions', ['label' => 'Save changes', 'back' => ['All site copy', route('words.index')]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
