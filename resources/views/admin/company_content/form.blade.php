@include('admin.includes.header')
@php
  $type = old('type', $item->type);
  [$listTitle, $noun, $fields] = $types[$type] ?? ['Content', 'item', []];
  $all = collect($types)->map(fn ($meta) => $meta[2]);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">{{ $item->exists ? 'Edit '.$noun : 'New '.$noun }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('company-content.index', ['type' => $type]) }}">{{ $listTitle }}</a></li><li class="breadcrumb-item active">{{ $item->exists ? 'Edit' : 'New' }}</li></ol></div>
  </div></div></div>
  @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
  <form method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('company-content.update', $item) : route('company-content.store') }}">
    @csrf @if($item->exists)@method('PUT')@endif
    <div class="card"><div class="card-body">
      @include('admin.includes.validation_errors')
      {{-- Field labels follow the chosen type; switching type relabels the form without a reload. --}}
      <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Type</label>
          <select class="form-select" name="type" id="cc-type">@foreach($types as $key => $meta)<option value="{{ $key }}" @selected($type === $key)>{{ $meta[0] }}</option>@endforeach</select></div>
        <div class="col-md-8 mb-3" data-field="title"><label class="form-label">{{ $fields['title'] ?? 'Title' }}</label>
          <input class="form-control" name="en[title]" value="{{ old('en.title', $item->translate('en')?->title) }}" required></div>
      </div>
      <div class="mb-3" data-field="subtitle"><label class="form-label">{{ $fields['subtitle'] ?? 'Subtitle' }}</label>
        <input class="form-control" name="en[subtitle]" value="{{ old('en.subtitle', $item->translate('en')?->subtitle) }}"></div>
      <div class="mb-3" data-field="description"><label class="form-label">{{ $fields['description'] ?? 'Description' }}</label>
        <textarea class="form-control" rows="3" name="en[description]">{{ old('en.description', $item->translate('en')?->description) }}</textarea></div>
      <div class="row">
        <div class="col-md-5 p-0" data-field="image"><div class="row m-0">@include('admin.includes.media_field', ['field' => 'image', 'label' => $fields['image'] ?? 'Image', 'cols' => 12, 'current' => $item->getRawOriginal('image'), 'preview' => $item->image])</div></div>
        <div class="col-md-3 mb-3" data-field="url"><label class="form-label">{{ $fields['url'] ?? 'Link' }}</label><input class="form-control" name="url" value="{{ old('url', $item->url) }}"></div>
        <div class="col-md-2 mb-3" data-field="value"><label class="form-label">{{ $fields['value'] ?? 'Value' }}</label><input class="form-control" name="value" value="{{ old('value', $item->value) }}"></div>
        <div class="col-md-2 mb-3"><label class="form-label">Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->exists ? $item->sort_order : '') }}" placeholder="last" title="Lower numbers are shown first"></div>
      </div>
      <div class="d-flex flex-wrap gap-4 mb-3">
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->exists ? $item->is_active : true))> Active (shown on the website)</label>
        <label data-field="pinned"><input type="checkbox" name="pinned" value="1" @checked(old('pinned', $item->pinned))> <span>{{ $fields['pinned'] ?? 'Always first' }}</span></label>
      </div>
      <button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save</button>
    </div></div>
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
