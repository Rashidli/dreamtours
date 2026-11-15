@include('admin.includes.header')
<style>.note-editable{min-height:320px}</style>
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">{{ $document->title }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('legal-documents.index') }}">Legal documents</a></li><li class="breadcrumb-item active">{{ $document->title }}</li></ol></div>
  </div></div></div>
  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')
  <div class="row">
    <div class="col-lg-8"><div class="card"><div class="card-body">
      <form method="post" action="{{ route('legal-documents.update', $document) }}">@csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-5"><label class="form-label">Name <span class="text-danger">*</span></label><input class="form-control" name="title" value="{{ old('title', $document->title) }}" required maxlength="150"><small class="text-muted">Used in links, the footer and the booking form.</small></div>
          <div class="col-md-7"><label class="form-label">Page heading</label><input class="form-control" name="heading" value="{{ old('heading', $document->heading) }}" maxlength="255"><small class="text-muted">The large title on its page, e.g. “Terms and Conditions of Booking”.</small></div>
          <div class="col-12"><label class="form-label">Text <span class="text-danger">*</span></label><textarea id="editor_en" class="form-control" name="body" rows="14">{{ old('body', $document->body) }}</textarea></div>
          <div class="col-12">
            <div class="form-check form-switch mb-2"><input type="hidden" name="is_published" value="0"><input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $document->is_published))><label class="form-check-label" for="is_published"><strong>Published</strong> — shown on the website at <a href="{{ $document->url }}" target="_blank">{{ url($document->url) }}</a>. When hidden, the page, its footer link, its tour-page block and its booking checkbox all disappear; earlier acceptances stay recorded.</label></div>
            <div class="form-check form-switch mb-2"><input type="hidden" name="show_on_tours" value="0"><input class="form-check-input" type="checkbox" id="show_on_tours" name="show_on_tours" value="1" @checked(old('show_on_tours', $document->show_on_tours))><label class="form-check-label" for="show_on_tours"><strong>Show on every tour page</strong> — appears in the trip’s policies, for all current and future tours.</label></div>
            <div class="form-check form-switch"><input type="hidden" name="require_acceptance" value="0"><input class="form-check-input" type="checkbox" id="require_acceptance" name="require_acceptance" value="1" @checked(old('require_acceptance', $document->require_acceptance))><label class="form-check-label" for="require_acceptance"><strong>Customers must accept it when booking</strong> — a required checkbox on every booking form.</label></div>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2"><button class="btn btn-primary"><i class="ri-save-line"></i> Save</button><a class="btn btn-light" href="{{ route('legal-documents.index') }}">Back</a></div>
        <p class="text-muted small mt-2 mb-0">Changing the name, heading or text creates a new version. Switching the options above does not.</p>
      </form>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body">
      <h5>Version history</h5>
      <p class="text-muted small">Every saved wording, newest first. Bookings show the version their customer accepted.</p>
      <ul class="list-group list-group-flush">
        @foreach($document->versions as $v)
          <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
            <div><a href="{{ route('legal-documents.version', $v) }}"><strong>Version {{ $v->version }}</strong></a>@if($loop->first) <span class="badge bg-success">current</span>@endif<br><small class="text-muted">{{ $v->created_at?->format('M j, Y g:i A') }}{{ $v->author ? ' · '.$v->author->name : '' }}</small></div>
            <span class="badge bg-light text-dark" title="Bookings that accepted this version">{{ $v->bookings_count }} {{ Str::plural('booking', $v->bookings_count) }}</span>
          </li>
        @endforeach
      </ul>
    </div></div></div>
  </div>
</div></div></div>
@include('admin.includes.footer')
