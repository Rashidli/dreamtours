@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 1])
<div class="row">
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h5 class="card-title">Upload a customer list</h5>
      <p class="text-muted">A <b>.csv</b> or Excel <b>.xlsx</b> file, first sheet, with column names in the first row — up to {{ number_format(\App\Support\CustomerImport::MAX_ROWS) }} customers. Nothing is saved until you have checked every row on step 3.</p>
      <form method="post" action="{{ route('customer-imports.store') }}" enctype="multipart/form-data">@csrf
        <input class="form-control mb-3" type="file" name="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        <button class="btn btn-primary"><i class="ri-upload-2-line align-middle me-1"></i> Upload and continue</button>
      </form>
      <hr>
      <p class="small mb-1"><b>Columns that can be imported:</b></p>
      <p class="text-muted mb-0">{{ collect(\App\Support\CustomerImport::FIELDS)->map(fn ($f) => $f[0])->implode(' · ') }}</p>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h5 class="card-title">Recent imports</h5>
      <table class="table crm-table mb-0">
        <thead><tr><th>File</th><th>By</th><th>Result</th><th></th></tr></thead>
        <tbody>
        @forelse($imports as $import)
          <tr>
            <td class="text-break">{{ $import->filename }}<br><span class="text-muted">{{ $import->created_at->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }}</span></td>
            <td>{{ $import->user?->name }}</td>
            <td>@if($import->status === 'imported'){{ $import->summary['created'] ?? 0 }} new · {{ $import->summary['updated'] ?? 0 }} updated · {{ $import->summary['skipped'] ?? 0 }} skipped · {{ $import->summary['failed'] ?? 0 }} errors@else<span class="text-muted">Not imported</span>@endif</td>
            <td class="text-end">@if($import->status !== 'imported' && \Illuminate\Support\Facades\Storage::disk('local')->exists($import->path))<a class="btn btn-sm btn-outline-primary" href="{{ route('customer-imports.edit', $import) }}">Continue</a>@endif</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-muted">No imports yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div></div>
  </div>
</div>
</div></div></div>@include('admin.includes.footer')
