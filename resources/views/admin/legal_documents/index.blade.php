@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Legal documents</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item active">Legal documents</li></ol></div>
  </div></div></div>
  @include('admin.includes.flash')
  <div class="card"><div class="card-body">
    <h4 class="mb-1">Legal documents</h4>
    <p class="text-muted">Written once and used everywhere: the footer, every tour page and every booking form, including tours created later. Every change to the wording is kept as a new version, and each booking records the exact versions its customer accepted.</p>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Document</th><th>Status</th><th>Shown on tour pages</th><th>Accepted at booking</th><th>Current version</th><th>Acceptances</th><th></th></tr></thead>
      <tbody>
      @foreach($documents as $doc)
        <tr>
          <td><strong>{{ $doc->title }}</strong><br><a href="{{ $doc->url }}" target="_blank" class="small">{{ url($doc->url) }}</a></td>
          <td>@if($doc->is_published)<span class="badge bg-success">Published</span>@else<span class="badge bg-secondary">Hidden</span>@endif</td>
          <td>{{ $doc->show_on_tours ? 'Yes' : 'No' }}</td>
          <td>{{ $doc->require_acceptance ? 'Required' : 'No' }}</td>
          <td>v{{ $doc->currentVersion?->version ?? 1 }} <span class="text-muted small">· {{ $doc->updated_at->format('M j, Y') }}</span></td>
          <td>{{ $acceptances[$doc->id] ?? 0 }}</td>
          <td class="text-end">@can('edit-legal-documents')<a class="btn btn-sm btn-primary" href="{{ route('legal-documents.edit', $doc) }}"><i class="ri-pencil-line"></i> Edit</a>@endcan</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </div></div>
</div></div></div>
@include('admin.includes.footer')
