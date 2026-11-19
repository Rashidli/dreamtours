@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $type = old('type', $item->type);
  [$listTitle, $noun, $fields] = $types[$type] ?? ['Content', 'item', []];
  $all = collect($types)->map(fn ($meta) => $meta[2]);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $item->exists ? ($item->translate('en')?->title ?: 'Edit '.$noun) : 'New '.$noun, 'parent' => [$listTitle, route('company-content.index', ['type' => $type])], 'current' => $item->exists ? 'Edit' : 'New',
  'badge' => $item->exists ? ($item->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']) : null])
@include('admin.includes.flash')
<form method="post" class="tf-form" enctype="multipart/form-data" action="{{ $item->exists ? route('company-content.update', $item) : route('company-content.store') }}">
  @csrf @if($item->exists)@method('PUT')@endif
  @include('admin.includes.validation_errors')
  {{-- Field labels follow the chosen type; switching type relabels the form without a reload. --}}
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-file-text-line', 'title' => 'Content', 'hint' => 'The fields change with the type; only the ones this type uses are shown.'])
      <div class="row g-3">
        <div class="col-md-4"><label class="fw-semibold" for="cc-type">Type</label>
          <select class="form-select" name="type" id="cc-type">@foreach($types as $key => $meta)<option value="{{ $key }}" @selected($type === $key)>{{ $meta[0] }}</option>@endforeach</select></div>
        <div class="col-md-8" data-field="title"><label class="fw-semibold tf-req" for="cc-title">{{ $fields['title'] ?? 'Title' }}</label>
          <input id="cc-title" class="form-control" name="en[title]" value="{{ old('en.title', $item->translate('en')?->title) }}" required></div>
        <div class="col-12" data-field="subtitle"><label class="fw-semibold" for="cc-subtitle">{{ $fields['subtitle'] ?? 'Subtitle' }}</label>
          <input id="cc-subtitle" class="form-control" name="en[subtitle]" value="{{ old('en.subtitle', $item->translate('en')?->subtitle) }}"></div>
        <div class="col-12" data-field="description"><label class="fw-semibold" for="cc-description">{{ $fields['description'] ?? 'Description' }}</label>
          <textarea id="cc-description" class="form-control" rows="4" name="en[description]">{{ old('en.description', $item->translate('en')?->description) }}</textarea></div>
        <div class="col-md-8" data-field="url"><label class="fw-semibold" for="cc-url">{{ $fields['url'] ?? 'Link' }}</label><input id="cc-url" class="form-control" name="url" value="{{ old('url', $item->url) }}"></div>
        <div class="col-md-4" data-field="value"><label class="fw-semibold" for="cc-value">{{ $fields['value'] ?? 'Value' }}</label><input id="cc-value" class="form-control" name="value" value="{{ old('value', $item->value) }}"></div>
      </div>
    </div></div>
    <div class="card" data-field="image"><div class="card-body">
      <div class="row">@include('admin.cms_ui.media_field', ['field' => 'image', 'label' => $fields['image'] ?? 'Image', 'cols' => 12, 'current' => $item->getRawOriginal('image'), 'preview' => $item->image])</div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Publish</h5>
      <div class="mb-2"><label class="fw-semibold" for="cc-order">Order</label><input id="cc-order" class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->exists ? $item->sort_order : '') }}" placeholder="last" title="Lower numbers are shown first"><div class="form-text">Lower numbers are shown first. Leave empty to add it at the end.</div></div>
      <div class="cms-switches">
        @include('admin.cms_ui.status_switch', ['id' => 'cc-active', 'checked' => old('is_active', $item->exists ? $item->is_active : true), 'label' => 'Active', 'hint' => 'Shown on the website.'])
        @include('admin.cms_ui.status_switch', ['name' => 'pinned', 'id' => 'cc-pinned', 'checked' => old('pinned', $item->pinned), 'label' => $fields['pinned'] ?? 'Always first', 'attrs' => 'data-field="pinned"'])
      </div>
      @include('admin.cms_ui.save_actions', ['label' => $item->exists ? 'Save changes' : 'Create '.$noun, 'back' => ['All '.Str::lower($listTitle), route('company-content.index', ['type' => $type])]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
<script>
  (function () {
    const labels = @json($all);
    const select = document.getElementById('cc-type');
    function apply() {
      const fields = labels[select.value] || {};
      document.querySelectorAll('[data-field]').forEach(function (el) {
        const key = el.dataset.field;
        const show = key === 'title' || key in fields;
        el.style.display = show ? '' : 'none';
        const label = el.querySelector('label span') || el.querySelector('label');
        if (show && fields[key] && label) label.textContent = fields[key];
      });
    }
    select.addEventListener('change', apply);
    apply();
  })();
</script>
@include('admin.includes.footer')
