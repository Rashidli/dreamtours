@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $statusTones = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'secondary'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $review->exists ? 'Review by '.($review->translate('en')?->name ?: '…') : 'New review', 'parent' => ['Reviews', route('reviews.index')], 'current' => $review->exists ? 'Edit' : 'New',
  'badge' => $review->exists ? [\App\Models\Review::STATUSES[$review->status] ?? $review->status, $statusTones[$review->status] ?? 'secondary'] : null])
@include('admin.includes.flash')
<form method="post" class="tf-form" enctype="multipart/form-data" action="{{ $review->exists ? route('reviews.update', $review) : route('reviews.store') }}">@csrf @if($review->exists)@method('PUT')@endif
@include('admin.includes.validation_errors')
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-chat-quote-line', 'title' => 'Review', 'hint' => 'Who wrote it and what they said.'])
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="fw-semibold tf-req" for="rv-name">Name</label><input id="rv-name" class="form-control" name="en[name]" value="{{ old('en.name', $review->translate('en')?->name) }}" required></div>
        <div class="col-md-6"><label class="fw-semibold" for="rv-position">Position / city</label><input id="rv-position" class="form-control" name="en[position]" value="{{ old('en.position', $review->translate('en')?->position) }}" placeholder="e.g. Toronto, ON"></div>
      </div>
      <div class="mb-3"><label class="fw-semibold tf-req" for="rv-content">Review</label><textarea id="rv-content" class="form-control" rows="6" name="en[content]" required>{{ old('en.content', $review->translate('en')?->content) }}</textarea></div>
      <div class="row g-3">
        <div class="col-sm-4 col-md-3"><label class="fw-semibold tf-req" for="rv-rating">Rating</label><input id="rv-rating" type="number" min="1" max="5" class="form-control" name="rating" value="{{ old('rating', $review->rating ?? 5) }}" required><div class="form-text">1 to 5 stars.</div></div>
      </div>
    </div></div>
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-route-line', 'title' => 'Trip', 'hint' => 'Link the review to a tour so it shows on that tour’s page.'])
      <div class="row g-3">
        <div class="col-md-6"><label class="fw-semibold" for="rv-tour">Tour</label><select id="rv-tour" class="form-select" name="tour_id"><option value="">General (not linked to a tour)</option>@foreach($tours as $tour)<option value="{{ $tour->id }}" @selected(old('tour_id', $review->tour_id) == $tour->id)>{{ $tour->name }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-md-3"><label class="fw-semibold" for="rv-date">Travel date</label><input id="rv-date" class="form-control" type="date" name="travel_date" value="{{ old('travel_date', $review->travel_date?->format('Y-m-d')) }}"></div>
        <div class="col-sm-6 col-md-3"><label class="fw-semibold" for="rv-email">Email</label><input id="rv-email" class="form-control" type="email" name="email" value="{{ old('email', $review->email) }}"><div class="form-text">Never shown on the website.</div></div>
      </div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Moderation</h5>
      <div class="mb-2"><label class="fw-semibold" for="rv-status">Status</label><select id="rv-status" class="form-select" name="status">@foreach(\App\Models\Review::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', $review->status ?: 'approved') === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="cms-switches">
        @include('admin.cms_ui.status_switch', ['id' => 'rv-active', 'checked' => old('is_active', $review->exists ? $review->is_active : true), 'label' => 'Published on the website', 'hint' => 'Only approved reviews are shown.'])
      </div>
      @include('admin.cms_ui.save_actions', ['label' => $review->exists ? 'Save changes' : 'Create review', 'back' => ['All reviews', route('reviews.index')]])
    </div></div>
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Photo</h5>
      @if($review->image)<a href="{{ $review->image }}" target="_blank" rel="noopener"><img src="{{ $review->image }}" alt="" class="tf-image mb-3"></a>@else<div class="tf-image-empty cms-photo-empty mb-3"><span><i class="ri-user-line fs-3 d-block text-center"></i>No photo</span></div>@endif
      <label class="fw-semibold" for="rv-image">Upload a photo</label>
      <input id="rv-image" type="file" class="form-control" name="image" accept="image/*,.heic,.heif">
      <input class="form-control mt-2" name="image_path" value="{{ old('image_path', $review->image) }}" placeholder="or paste an image URL / stored file name" aria-label="Image URL or stored file name">
      <div class="form-text">Optional. The reviewer’s photo or a picture from the trip.</div>
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
