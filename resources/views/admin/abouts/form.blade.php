@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $aboutTitle = $about->translate('en')?->title;
  $media = app(\App\Services\ImageUploadService::class);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $about->exists ? ($aboutTitle ?: 'Edit About block') : 'New About block', 'parent' => ['About content', route('abouts.index')], 'current' => $about->exists ? 'Edit' : 'New'])
@include('admin.includes.flash')
<form method="post" class="tf-form" enctype="multipart/form-data" action="{{ $about->exists ? route('abouts.update', $about) : route('abouts.store') }}">@csrf @if($about->exists)@method('PUT')@endif
@include('admin.includes.validation_errors')
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-article-line', 'title' => 'Text', 'hint' => 'The heading and paragraphs shown on the About page.'])
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="fw-semibold tf-req" for="about-key">Block</label><input id="about-key" class="form-control" name="key" value="{{ old('key', $about->key ?: 'main') }}" required list="about-keys"><datalist id="about-keys"><option value="main">Our story</option><option value="vision">Vision</option><option value="mission">Mission</option><option value="founder">Founder</option></datalist><div class="form-text"><code>main</code> = Our story · <code>vision</code> · <code>mission</code> · <code>founder</code></div></div>
        <div class="col-md-8"><label class="fw-semibold tf-req" for="about-title">Title</label><input id="about-title" class="form-control" name="en[title]" value="{{ old('en.title', $about->translate('en')?->title) }}" required><div class="form-text">For the founder: their name and role.</div></div>
      </div>
      <div class="mb-0"><label class="fw-semibold">Description</label><textarea class="js-editor" rows="7" name="en[description]">{{ old('en.description', $about->translate('en')?->description) }}</textarea></div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Publish</h5>
      <div class="cms-meta"><span>Changes show on the About page as soon as you save.</span></div>
      @include('admin.cms_ui.save_actions', ['label' => $about->exists ? 'Save changes' : 'Create block', 'back' => ['All About blocks', route('abouts.index')]])
    </div></div>
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Image</h5>
      @if($about->image)<a href="{{ $media->url($about->image) }}" target="_blank" rel="noopener"><img src="{{ $media->url($about->image) }}" alt="" class="tf-image mb-3"></a>@else<div class="tf-image-empty mb-3"><span><i class="ri-image-line fs-3 d-block text-center"></i>No image yet</span></div>@endif
      <label class="fw-semibold" for="about-image">Upload a new image</label>
      <input id="about-image" class="form-control" type="file" name="image" accept="image/*,.heic,.heif">
      <input class="form-control mt-2" name="image_path" value="{{ old('image_path', $about->image) }}" placeholder="or paste an image URL / stored file name" aria-label="Image URL or stored file name">
      <div class="form-text">For the founder block this is their photo.</div>
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
