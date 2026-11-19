@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $groups = ['header' => 'Header', 'footer_company' => 'Footer: Company', 'footer_support' => 'Footer: Support', 'footer_legal' => 'Footer: Legal'];
  $filtered = request()->filled('q') || request()->filled('location') || request()->filled('status');
  $newAction = ['url' => route('navigation-items.create'), 'label' => 'New link', 'can' => 'create-navigation'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Navigation & footer links', 'count' => $items->total(), 'noun' => 'link', 'filtered' => $filtered, 'action' => $newAction,
  'intro' => 'The menu at the top of the website and the link columns in the footer.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('navigation-items.index'), 'search' => 'Search titles', 'clearUrl' => route('navigation-items.index'),
    'tabs' => ['name' => 'location', 'label' => 'Link group', 'options' => ['' => 'All'] + $groups],
    'selects' => [['name' => 'status', 'label' => 'Status', 'all' => 'All statuses', 'options' => ['1' => 'Active', '0' => 'Inactive']]]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Link</th><th class="d-none d-md-table-cell">Group</th><th class="d-none d-lg-table-cell">Parent link</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($items as $item)
      <tr>
        <td>@can('edit-navigation')<a class="cms-title" href="{{ route('navigation-items.edit', $item) }}">{{ $item->title }}</a>@else<span class="cms-title">{{ $item->title }}</span>@endcan<span class="cms-sub"><code>{{ $item->url }}</code>@if($item->target === '_blank') <i class="ri-external-link-line" title="Opens in a new tab"></i>@endif</span></td>
        <td class="d-none d-md-table-cell text-nowrap">{{ $groups[$item->location] ?? $item->location }}</td>
        <td class="d-none d-lg-table-cell">{{ $item->parent?->title ?: '—' }}</td>
        <td><span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">
          @can('edit-navigation')<a href="{{ route('navigation-items.edit', $item) }}" class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $item->title }}"><i class="ri-pencil-line"></i></a>@endcan
          @can('delete-navigation')<form action="{{ route('navigation-items.destroy', $item) }}" method="post">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $item->title }}" onclick="return confirm('Delete this link? It disappears from the website menu.')"><i class="ri-delete-bin-line"></i></button></form>@endcan
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 5, 'icon' => 'ri-menu-line', 'title' => 'No links yet', 'text' => 'Add the pages visitors should reach from the header menu and the footer.', 'filtered' => $filtered, 'clearUrl' => route('navigation-items.index'), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $items->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
