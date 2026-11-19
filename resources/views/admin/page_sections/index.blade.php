@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('page_id') || request()->filled('status');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Page sections', 'count' => $sections->total(), 'noun' => 'section', 'filtered' => $filtered,
  'intro' => 'The blocks that make up each page. Where a section sits is built into the website; its text, button and images are editable here.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('page-sections.index'), 'search' => 'Search title or key', 'clearUrl' => route('page-sections.index'),
    'tabs' => ['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']],
    'selects' => [['name' => 'page_id', 'label' => 'Page', 'all' => 'All pages', 'options' => $pages->pluck('title', 'id')->all()]]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Section</th><th class="d-none d-md-table-cell">Page</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($sections as $section)
      <tr>
        <td class="cms-wrap">@can('edit-page-sections')<a class="cms-title" href="{{ route('page-sections.edit', $section) }}">{{ $section->title ?: $section->key }}</a>@else<span class="cms-title">{{ $section->title ?: $section->key }}</span>@endcan<span class="cms-sub"><code>{{ $section->key }}</code><span class="d-md-none"> · {{ $section->page?->title }}</span></span></td>
        <td class="d-none d-md-table-cell">{{ $section->page?->title ?: '—' }}</td>
        <td><span class="badge bg-{{ $section->is_active ? 'success' : 'secondary' }}">{{ $section->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">@can('edit-page-sections')<a href="{{ route('page-sections.edit', $section) }}" class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $section->title ?: $section->key }}"><i class="ri-pencil-line"></i></a>@endcan</td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 4, 'icon' => 'ri-layout-masonry-line', 'title' => 'No sections yet', 'text' => 'Sections appear here once they are set up in the website code.', 'filtered' => $filtered, 'clearUrl' => route('page-sections.index')])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $sections->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
