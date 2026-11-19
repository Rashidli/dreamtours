@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $contact_item->exists ? ($contact_item->translate('en')?->title ?: 'Edit contact item') : 'New contact item', 'parent' => ['Contact information', route('contact_items.index')], 'current' => $contact_item->exists ? 'Edit' : 'New',
  'badge' => $contact_item->exists ? ($contact_item->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']) : null])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ $contact_item->exists ? route('contact_items.update', $contact_item) : route('contact_items.store') }}">@csrf @if($contact_item->exists)@method('PUT')@endif
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-contacts-book-line', 'title' => 'Contact detail', 'hint' => 'What visitors read, and where a click takes them.'])
      <div class="row g-3">
        <div class="col-md-6"><label class="fw-semibold tf-req" for="ci-title">Title</label><input id="ci-title" class="form-control" name="en[title]" value="{{ old('en.title', $contact_item->translate('en')?->title) }}" required placeholder="e.g. Call us"></div>
        <div class="col-md-6"><label class="fw-semibold tf-req" for="ci-value">Value</label><input id="ci-value" class="form-control" name="en[value]" value="{{ old('en.value', $contact_item->translate('en')?->value) }}" required placeholder="e.g. +1 416 726 6393"></div>
        <div class="col-md-6"><label class="fw-semibold" for="ci-link">Link</label><input id="ci-link" class="form-control" name="link" value="{{ old('link', $contact_item->link) }}" placeholder="tel:+14167266393"><div class="form-text">Optional. <code>tel:</code> for phones, <code>mailto:</code> for email, or a full https:// address.</div></div>
        <div class="col-md-6"><label class="fw-semibold" for="ci-icon">Icon key</label>
          <div class="d-flex gap-2 align-items-center"><span class="cms-icon-preview" aria-hidden="true"><i id="ci-icon-preview" class="{{ old('icon', $contact_item->icon) ?: 'ri-information-line' }}"></i></span><input id="ci-icon" class="form-control" name="icon" value="{{ old('icon', $contact_item->icon) }}" placeholder="ri-phone-line"></div>
          <div class="form-text">A Remix icon class, e.g. <code>ri-phone-line</code>, <code>ri-mail-line</code>, <code>ri-map-pin-line</code>.</div></div>
      </div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Visibility</h5>
      @include('admin.cms_ui.status_switch', ['id' => 'ci-active', 'checked' => old('is_active', $contact_item->exists ? $contact_item->is_active : true), 'label' => 'Active', 'hint' => 'Shown on the Contact page and in the footer.'])
      @include('admin.cms_ui.save_actions', ['label' => $contact_item->exists ? 'Save changes' : 'Create item', 'back' => ['All contact items', route('contact_items.index')]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
<script>
document.getElementById('ci-icon').addEventListener('input', function () { document.getElementById('ci-icon-preview').className = this.value.trim() || 'ri-information-line'; });
</script>
@include('admin.includes.footer')
