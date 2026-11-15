@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">{{ $version->title }} — version {{ $version->version }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('legal-documents.index') }}">Legal documents</a></li><li class="breadcrumb-item active">Version {{ $version->version }}</li></ol></div>
  </div></div></div>
  <div class="card"><div class="card-body">
    <p class="text-muted">Saved {{ $version->created_at?->format('D, M j, Y g:i A') }}{{ $version->author ? ' by '.$version->author->name : '' }} · accepted in {{ $version->bookings_count }} {{ Str::plural('booking', $version->bookings_count) }}. This is the exact wording; it cannot be changed.</p>
    <h3>{{ $version->heading ?: $version->title }}</h3>
    <div class="border rounded p-3 bg-light">{!! $version->body !!}</div>
    @can('edit-legal-documents')<a class="btn btn-light mt-3" href="{{ route('legal-documents.edit', $version->document) }}">Back to {{ $version->document->title }}</a>@endcan
  </div></div>
</div></div></div>
@include('admin.includes.footer')
