@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Legal documents', 'count' => $documents->count(), 'noun' => 'document',
  'intro' => 'Written once and used everywhere: the footer, every tour page and every booking form, including tours created later. Each change to the wording is kept as a new version, and every booking records the exact versions its customer accepted.'])
@include('admin.includes.flash')
<div class="card tl-card"><div class="card-body p-0">
  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Document</th><th>Status</th><th class="d-none d-md-table-cell">Tour pages</th><th class="d-none d-md-table-cell">At booking</th><th class="d-none d-lg-table-cell">Version</th><th class="d-none d-lg-table-cell">Acceptances</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($documents as $doc)
      <tr>
        <td>@can('edit-legal-documents')<a class="cms-title" href="{{ route('legal-documents.edit', $doc) }}">{{ $doc->title }}</a>@else<span class="cms-title">{{ $doc->title }}</span>@endcan<span class="cms-sub"><a href="{{ $doc->url }}" target="_blank" rel="noopener" class="text-muted">{{ $doc->url }} <i class="ri-external-link-line"></i></a></span></td>
        <td>@if($doc->is_published)<span class="badge bg-success">Published</span>@else<span class="badge bg-secondary">Hidden</span>@endif</td>
        <td class="d-none d-md-table-cell">{{ $doc->show_on_tours ? 'Shown' : '—' }}</td>
        <td class="d-none d-md-table-cell">{{ $doc->require_acceptance ? 'Must accept' : '—' }}</td>
        <td class="d-none d-lg-table-cell text-nowrap">v{{ $doc->currentVersion?->version ?? 1 }}<span class="cms-sub">{{ $doc->updated_at->format('M j, Y') }}</span></td>
        <td class="d-none d-lg-table-cell">{{ $acceptances[$doc->id] ?? 0 }}</td>
        <td class="cms-actions">@can('edit-legal-documents')<a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $doc->title }}" href="{{ route('legal-documents.edit', $doc) }}"><i class="ri-pencil-line"></i></a>@endcan</td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 7, 'icon' => 'ri-file-paper-2-line', 'title' => 'No legal documents yet', 'text' => 'Terms, privacy and refund policies are set up with the website.'])
    @endforelse
    </tbody>
  </table></div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
