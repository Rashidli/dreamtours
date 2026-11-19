@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $version->title.' — version '.$version->version, 'parent' => ['Legal documents', route('legal-documents.index')], 'current' => 'Version '.$version->version])
<div class="card"><div class="card-body">
  @include('admin.cms_ui.section_head', ['icon' => 'ri-history-line', 'title' => 'Saved wording', 'hint' => 'Saved '.e($version->created_at?->format('D, M j, Y g:i A')).e($version->author ? ' by '.$version->author->name : '').' · accepted in '.$version->bookings_count.' '.Str::plural('booking', $version->bookings_count).'. This is the exact wording; it cannot be changed.'])
  <div class="cms-version-body">
    <h3 class="h4 mb-3">{{ $version->heading ?: $version->title }}</h3>
    {!! $version->body !!}
  </div>
  @can('edit-legal-documents')<a class="btn btn-light mt-3" href="{{ route('legal-documents.edit', $version->document) }}"><i class="ri-arrow-left-line align-middle me-1"></i> Back to {{ $version->document->title }}</a>@endcan
</div></div>
</div></div></div>
@include('admin.includes.footer')
