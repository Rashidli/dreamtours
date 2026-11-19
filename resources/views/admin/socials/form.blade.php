@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $social->exists ? ($social->title ?: 'Edit social link') : 'New social link', 'parent' => ['Social links', route('socials.index')], 'current' => $social->exists ? 'Edit' : 'New',
  'badge' => $social->exists ? ($social->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']) : null])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ $social->exists ? route('socials.update', $social) : route('socials.store') }}">@csrf @if($social->exists)@method('PUT')@endif
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-share-line', 'title' => 'Social link', 'hint' => 'The platform and the address of your page on it.'])
      <div class="row g-3">
        <div class="col-md-6"><label class="fw-semibold tf-req" for="social-title">Platform name</label><input id="social-title" class="form-control" name="title" value="{{ old('title', $social->title) }}" required placeholder="e.g. Instagram"></div>
        <div class="col-md-6"><label class="fw-semibold" for="social-icon">Icon key</label>
          <div class="d-flex gap-2 align-items-center"><span class="cms-icon-preview" aria-hidden="true"><i id="social-icon-preview" class="{{ old('icon', $social->icon) ?: 'ri-links-line' }}"></i></span><input id="social-icon" class="form-control" name="icon" value="{{ old('icon', $social->icon) }}" placeholder="ri-instagram-line"></div>
          <div class="form-text">A Remix icon class, e.g. <code>ri-facebook-fill</code>, <code>ri-instagram-line</code>, <code>ri-youtube-line</code>.</div></div>
        <div class="col-12"><label class="fw-semibold tf-req" for="social-link">Link</label><input id="social-link" class="form-control" name="link" value="{{ old('link', $social->link) }}" required placeholder="https://www.instagram.com/yourpage"><div class="form-text">The full address, starting with https://.</div></div>
      </div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Visibility</h5>
      @include('admin.cms_ui.status_switch', ['id' => 'social-active', 'checked' => old('is_active', $social->exists ? $social->is_active : true), 'label' => 'Active', 'hint' => 'Shown in the website header and footer.'])
      @include('admin.cms_ui.save_actions', ['label' => $social->exists ? 'Save changes' : 'Create link', 'back' => ['All social links', route('socials.index')]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
<script>
document.getElementById('social-icon').addEventListener('input', function () { document.getElementById('social-icon-preview').className = this.value.trim() || 'ri-links-line'; });
</script>
@include('admin.includes.footer')
