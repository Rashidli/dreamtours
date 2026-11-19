@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $locales = config('translatable.locales');
  $multi = count($locales) > 1;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $page->title ?: 'Edit page', 'parent' => ['Pages & SEO', route('cms-pages.index')], 'current' => $page->title,
  'badge' => $page->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ route('cms-pages.update', $page) }}">
  @csrf
  @method('PUT')
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-search-eye-line', 'title' => 'Title & search engines (SEO)', 'hint' => 'The page title, and how the page looks in Google results and link previews.'])
      @include('admin.includes.lang_tabs', ['id' => 'page'])
      <div class="tab-content">
        @foreach($locales as $lang)
          @php $t = $page->translate($lang); @endphp
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="page-{{ $lang }}" role="tabpanel">
            <div class="mb-3">
              <label class="fw-semibold tf-req" for="page-{{ $lang }}-title">Title{{ $multi ? ' ('.strtoupper($lang).')' : '' }}</label>
              <input id="page-{{ $lang }}-title" class="form-control @error("$lang.title") is-invalid @enderror" type="text" name="{{ $lang }}[title]" value="{{ old("$lang.title", $t?->title) }}">
              @error("$lang.title") <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="tf-subtle">
              <div class="mb-3">
                <label class="fw-semibold" for="page-{{ $lang }}-meta-title">Meta title{{ $multi ? ' ('.strtoupper($lang).')' : '' }}</label>
                <input id="page-{{ $lang }}-meta-title" class="form-control @error("$lang.meta_title") is-invalid @enderror" type="text" name="{{ $lang }}[meta_title]" value="{{ old("$lang.meta_title", $t?->meta_title) }}">
                @error("$lang.meta_title") <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">The blue headline in Google. Falls back to the page title when empty. About 50–60 characters.</div>
              </div>
              <div class="mb-3">
                <label class="fw-semibold" for="page-{{ $lang }}-meta-description">Meta description{{ $multi ? ' ('.strtoupper($lang).')' : '' }}</label>
                <textarea id="page-{{ $lang }}-meta-description" class="form-control @error("$lang.meta_description") is-invalid @enderror" rows="3" name="{{ $lang }}[meta_description]">{{ old("$lang.meta_description", $t?->meta_description) }}</textarea>
                @error("$lang.meta_description") <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">The grey text under the headline. About 150–160 characters.</div>
              </div>
              <div class="mb-0">
                <label class="fw-semibold" for="page-{{ $lang }}-meta-keywords">Meta keywords{{ $multi ? ' ('.strtoupper($lang).')' : '' }}</label>
                <input id="page-{{ $lang }}-meta-keywords" class="form-control @error("$lang.meta_keywords") is-invalid @enderror" type="text" name="{{ $lang }}[meta_keywords]" value="{{ old("$lang.meta_keywords", $t?->meta_keywords) }}" placeholder="comma-separated keywords">
                @error("$lang.meta_keywords") <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div></div>

    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-layout-masonry-line', 'title' => 'Sections on this page', 'hint' => 'Each block of the page. Open one to change its text, button or images.'])
      <ul class="list-group list-group-flush cms-list-group">
        @forelse($page->sections as $section)
          <li class="list-group-item">
            <span class="min-w-0"><span class="cms-title fw-semibold">{{ $section->title ?: $section->key }}</span> <code class="ms-1 text-muted">{{ $section->key }}</code>@unless($section->is_active) <span class="badge bg-secondary ms-1">Inactive</span>@endunless</span>
            <a class="btn btn-sm btn-light flex-shrink-0" href="{{ route('page-sections.edit', $section) }}"><i class="ri-pencil-line"></i> Edit</a>
          </li>
        @empty
          <li class="list-group-item text-muted">This page has no editable sections.</li>
        @endforelse
      </ul>
    </div></div>
  </div>

  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Publish</h5>
      <div class="cms-meta mb-2"><span>Page key <code>{{ $page->key }}</code></span><span>The address and layout are part of the website code, so only the content and SEO are editable here.</span></div>
      <div class="cms-switches">
        @include('admin.cms_ui.status_switch', ['id' => 'statusSwitch', 'checked' => old('is_active', $page->exists ? $page->is_active : true), 'label' => 'Active', 'hint' => 'Shown on the website.'])
      </div>
      @include('admin.cms_ui.save_actions', ['label' => 'Save changes', 'back' => ['All pages', route('cms-pages.index')]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
