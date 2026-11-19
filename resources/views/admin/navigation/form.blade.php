@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $locales = config('translatable.locales');
  $multi = count($locales) > 1;
  $groups = ['header' => 'Header', 'footer_company' => 'Footer: Company', 'footer_support' => 'Footer: Support', 'footer_legal' => 'Footer: Legal'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $item->exists ? ($item->title ?: 'Edit link') : 'New link', 'parent' => ['Navigation & footer', route('navigation-items.index')], 'current' => $item->exists ? $item->title : 'New',
  'badge' => $item->exists ? ($item->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']) : null])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ $item->exists ? route('navigation-items.update', $item) : route('navigation-items.store') }}">
  @csrf
  @if($item->exists) @method('PUT') @endif
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-links-line', 'title' => 'Link', 'hint' => 'The words visitors see and the page they go to.'])
      @include('admin.includes.lang_tabs', ['id' => 'nav'])
      <div class="tab-content">
        @foreach($locales as $lang)
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="nav-{{ $lang }}" role="tabpanel">
            <div class="mb-3">
              <label class="fw-semibold tf-req" for="nav-{{ $lang }}-title">Title{{ $multi ? ' ('.strtoupper($lang).')' : '' }}</label>
              <input id="nav-{{ $lang }}-title" class="form-control @error("$lang.title") is-invalid @enderror" type="text" name="{{ $lang }}[title]" value="{{ old("$lang.title", $item->translate($lang)?->title) }}" placeholder="e.g. One-day tours">
              @error("$lang.title") <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
        @endforeach
      </div>
      <div class="mb-0">
        <label class="fw-semibold tf-req" for="nav-url">URL</label>
        <input id="nav-url" class="form-control @error('url') is-invalid @enderror" type="text" name="url" value="{{ old('url', $item->url) }}" placeholder="/one-day-tours">
        @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Internal links start with “/”; external links need the full address (https://…).</div>
      </div>
    </div></div>
  </div>

  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Placement</h5>
      <div class="mb-3">
        <label class="fw-semibold tf-req" for="nav-location">Group</label>
        <select id="nav-location" class="form-select" name="location">
          @foreach($groups as $value => $label)
            <option value="{{ $value }}" @selected(old('location', $item->location ?: 'header') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-3">
        <label class="fw-semibold" for="nav-parent">Parent link</label>
        <select id="nav-parent" class="form-select" name="parent_id">
          <option value="">None (top level)</option>
          @foreach($parents as $parent)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $item->parent_id) == $parent->id)>{{ $parent->title }} ({{ $groups[$parent->location] ?? $parent->location }})</option>
          @endforeach
        </select>
        <div class="form-text">Pick one to show this link inside its drop-down menu.</div>
      </div>
      <div class="mb-2">
        <label class="fw-semibold" for="nav-target">Opens in</label>
        <select id="nav-target" class="form-select" name="target">
          <option value="_self" @selected(old('target', $item->target) === '_self')>Same tab</option>
          <option value="_blank" @selected(old('target', $item->target) === '_blank')>New tab</option>
        </select>
      </div>
      <div class="cms-switches">
        @include('admin.cms_ui.status_switch', ['id' => 'statusSwitch', 'checked' => old('is_active', $item->exists ? $item->is_active : true), 'label' => 'Active', 'hint' => 'Shown on the website.'])
      </div>
      @include('admin.cms_ui.save_actions', ['label' => $item->exists ? 'Save changes' : 'Create link', 'back' => ['All links', route('navigation-items.index')]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
