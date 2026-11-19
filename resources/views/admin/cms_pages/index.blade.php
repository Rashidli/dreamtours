@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('status');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Pages & SEO', 'count' => $pages->total(), 'noun' => 'page', 'filtered' => $filtered,
  'intro' => 'Pages are built into the website, so they can’t be added or removed here. Edit each page’s title, Google texts (SEO) and its sections.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('cms-pages.index'), 'search' => 'Search title or key', 'clearUrl' => route('cms-pages.index'),
    'tabs' => ['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Page</th><th class="d-none d-md-table-cell">Sections</th><th>SEO</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($pages as $page)
      <tr>
        <td>@can('edit-pages')<a class="cms-title" href="{{ route('cms-pages.edit', $page) }}">{{ $page->title }}</a>@else<span class="cms-title">{{ $page->title }}</span>@endcan<span class="cms-sub"><code>{{ $page->key }}</code></span></td>
        <td class="d-none d-md-table-cell">{{ $page->sections_count }} {{ Str::plural('section', $page->sections_count) }}</td>
        <td>
          @if($page->meta_title && $page->meta_description)<span class="badge bg-success" title="Meta title and description are filled in">Complete</span>
          @elseif($page->meta_title || $page->meta_description)<span class="badge bg-warning" title="{{ $page->meta_title ? 'Meta description is missing' : 'Meta title is missing' }}">Partial</span>
          @else<span class="badge bg-danger" title="No meta title or description yet">Missing</span>@endif
        </td>
        <td><span class="badge bg-{{ $page->is_active ? 'success' : 'secondary' }}">{{ $page->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">@can('edit-pages')<a href="{{ route('cms-pages.edit', $page) }}" class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $page->title }}"><i class="ri-pencil-line"></i></a>@endcan</td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 5, 'icon' => 'ri-pages-line', 'title' => 'No pages yet', 'text' => 'Pages appear here once they are set up in the website code.', 'filtered' => $filtered, 'clearUrl' => route('cms-pages.index')])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $pages->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
