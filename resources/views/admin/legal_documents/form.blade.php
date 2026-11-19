@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $document->title, 'parent' => ['Legal documents', route('legal-documents.index')], 'current' => $document->title,
  'badge' => $document->is_published ? ['Published', 'success'] : ['Hidden', 'secondary']])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ route('legal-documents.update', $document) }}">@csrf @method('PUT')
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-file-paper-2-line', 'title' => 'Wording', 'hint' => 'Changing the name, heading or text saves a new version.'])
      <div class="row g-3">
        <div class="col-md-5"><label class="fw-semibold tf-req" for="legal-title">Name</label><input id="legal-title" class="form-control" name="title" value="{{ old('title', $document->title) }}" required maxlength="150"><div class="form-text">Used in links, the footer and the booking form.</div></div>
        <div class="col-md-7"><label class="fw-semibold" for="legal-heading">Page heading</label><input id="legal-heading" class="form-control" name="heading" value="{{ old('heading', $document->heading) }}" maxlength="255"><div class="form-text">The large title on its page, e.g. “Terms and Conditions of Booking”.</div></div>
        <div class="col-12"><label class="fw-semibold tf-req" for="editor_en">Text</label><textarea id="editor_en" class="form-control" name="body" rows="14">{{ old('body', $document->body) }}</textarea></div>
      </div>
    </div></div>
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-eye-line', 'title' => 'Where it appears', 'hint' => 'Switching these does not create a new version.'])
      <div class="form-check form-switch tf-switch"><input type="hidden" name="is_published" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" @checked(old('is_published', $document->is_published))><label class="form-check-label" for="is_published"><span>Published</span><small>Shown on the website at <a href="{{ $document->url }}" target="_blank" rel="noopener">{{ url($document->url) }}</a>. When hidden, the page, its footer link, its tour-page block and its booking checkbox all disappear; earlier acceptances stay recorded.</small></label></div>
      <div class="form-check form-switch tf-switch"><input type="hidden" name="show_on_tours" value="0"><input class="form-check-input" type="checkbox" role="switch" id="show_on_tours" name="show_on_tours" value="1" @checked(old('show_on_tours', $document->show_on_tours))><label class="form-check-label" for="show_on_tours"><span>Show on every tour page</span><small>Appears in the trip’s policies, for all current and future tours.</small></label></div>
      <div class="form-check form-switch tf-switch"><input type="hidden" name="require_acceptance" value="0"><input class="form-check-input" type="checkbox" role="switch" id="require_acceptance" name="require_acceptance" value="1" @checked(old('require_acceptance', $document->require_acceptance))><label class="form-check-label" for="require_acceptance"><span>Customers must accept it when booking</span><small>A required checkbox on every booking form.</small></label></div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Save</h5>
      <div class="cms-meta"><span>Current version <b>v{{ $document->versions->first()?->version ?? 1 }}</b></span><span>A changed name, heading or text is saved as the next version; bookings keep the version their customer accepted.</span></div>
      @include('admin.cms_ui.save_actions', ['label' => 'Save changes', 'back' => ['All documents', route('legal-documents.index')]])
    </div></div>
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Version history</h5>
      <p class="text-muted mb-2">Every saved wording, newest first.</p>
      <ul class="list-group list-group-flush cms-list-group">
        @foreach($document->versions as $v)
          <li class="list-group-item align-items-start">
            <div><a href="{{ route('legal-documents.version', $v) }}" class="fw-semibold">Version {{ $v->version }}</a>@if($loop->first) <span class="badge bg-success">Current</span>@endif<span class="cms-sub text-muted">{{ $v->created_at?->format('M j, Y g:i A') }}{{ $v->author ? ' · '.$v->author->name : '' }}</span></div>
            <span class="badge bg-light flex-shrink-0" title="Bookings that accepted this version">{{ $v->bookings_count }} {{ Str::plural('booking', $v->bookings_count) }}</span>
          </li>
        @endforeach
      </ul>
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
