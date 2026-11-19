@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 4])
<div class="card cs-done"><div class="card-body text-center py-4">
  <div class="cs-done-icon"><i class="ri-check-double-line"></i></div>
  <h5 class="mb-1">“{{ $import->filename }}” was imported</h5>
  <p class="text-muted mb-0">{{ $import->imported_at?->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }} by {{ $import->user?->name }}. The uploaded file has been deleted from the server.</p>
  <ul class="cs-done-list">
    <li><b>{{ $import->summary['created'] ?? 0 }}</b> new customers created</li>
    <li><b>{{ $import->summary['updated'] ?? 0 }}</b> existing customers updated (empty fields only)</li>
    <li><b>{{ $import->summary['skipped'] ?? 0 }}</b> rows skipped (already customers, or repeated in the file)</li>
    <li><b class="{{ ($import->summary['failed'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $import->summary['failed'] ?? 0 }}</b> rows with problems not imported</li>
  </ul>
  <div class="d-flex flex-wrap justify-content-center gap-2">
    <a class="btn btn-light" href="{{ route('customer-imports.create') }}">Import another file</a>
    <a class="btn btn-primary" href="{{ route('customers.index', ['source' => 'import', 'sort' => 'newest']) }}">See imported customers <i class="ri-arrow-right-line"></i></a>
  </div>
</div></div>
</div></div></div>@include('admin.includes.footer')
