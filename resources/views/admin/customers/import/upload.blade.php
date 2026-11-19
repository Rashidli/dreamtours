@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 1])
<div class="row gx-lg-4">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-upload-cloud-2-line"></i></span><div><h5>Upload a customer list</h5><p>Nothing is saved until you have checked every row on step 3.</p></div></div>
      <form method="post" action="{{ route('customer-imports.store') }}" enctype="multipart/form-data">@csrf
        <label class="cs-drop" for="ci-file">
          <i class="ri-file-excel-2-line"></i>
          <span class="cs-drop-title">Choose a .csv or Excel .xlsx file</span>
          <span class="text-muted">First sheet, column names in the first row, up to {{ number_format(\App\Support\CustomerImport::MAX_ROWS) }} customers.</span>
          <input class="form-control" id="ci-file" type="file" name="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        </label>
        <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary"><i class="ri-upload-2-line"></i> Upload and continue</button></div>
      </form>
      <hr class="cs-hr">
      <p class="fw-semibold mb-2">Columns that can be imported</p>
      <div class="cs-fields">@foreach(\App\Support\CustomerImport::FIELDS as $f)<span>{{ $f[0] }}</span>@endforeach</div>
    </div></div>
  </div>
  <div class="col-lg-5">
    <div class="card tl-card"><div class="card-body p-0">
      <div class="cs-panel-head"><h5 class="cs-panel-title"><i class="ri-history-line"></i> Recent imports</h5></div>
      <ul class="cs-import-list">
        @forelse($imports as $import)
          <li class="cs-import-item">
            <i class="ri-file-excel-2-line" aria-hidden="true"></i>
            <div class="cs-import-main">
              <div class="cs-import-name">{{ $import->filename }}</div>
              <div class="cs-sub">{{ $import->created_at->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }} · {{ $import->user?->name }}</div>
              <div class="cs-sub">@if($import->status === 'imported'){{ $import->summary['created'] ?? 0 }} new · {{ $import->summary['updated'] ?? 0 }} updated · {{ $import->summary['skipped'] ?? 0 }} skipped · {{ $import->summary['failed'] ?? 0 }} errors @else<span class="badge bg-warning bg-opacity-10 text-warning">Not imported</span>@endif</div>
            </div>
            @if($import->status !== 'imported' && \Illuminate\Support\Facades\Storage::disk('local')->exists($import->path))<a class="btn btn-light flex-shrink-0" href="{{ route('customer-imports.edit', $import) }}">Continue</a>@endif
          </li>
        @empty
          <li class="cs-empty cs-empty-sm"><i class="ri-inbox-archive-line"></i>No imports yet.</li>
        @endforelse
      </ul>
    </div></div>
  </div>
</div>
</div></div></div>@include('admin.includes.footer')
