@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('status');
  $newAction = ['url' => route('socials.create'), 'label' => 'New social link', 'can' => 'create-socials'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Social links', 'count' => $socials->total(), 'noun' => 'link', 'filtered' => $filtered, 'action' => $newAction,
  'intro' => 'The social icons in the website header and footer. Inactive links stay here but are hidden.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('socials.index'), 'search' => 'Search platforms', 'clearUrl' => route('socials.index'),
    'tabs' => ['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Platform</th><th class="d-none d-md-table-cell">Link</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($socials as $social)
      <tr>
        <td><div class="cms-cell-flex"><span class="cms-icon-preview">@if($social->icon)<i class="{{ $social->icon }}"></i>@else<i class="ri-links-line"></i>@endif</span><div><a class="cms-title" href="{{ route('socials.edit', $social) }}">{{ $social->title }}</a>@if($social->icon)<span class="cms-sub"><code>{{ $social->icon }}</code></span>@endif</div></div></td>
        <td class="d-none d-md-table-cell"><a href="{{ $social->link }}" target="_blank" rel="noopener" class="cms-clip" title="{{ $social->link }}">{{ $social->link }}</a></td>
        <td><span class="badge bg-{{ $social->is_active ? 'success' : 'secondary' }}">{{ $social->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">
          <a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $social->title }}" href="{{ route('socials.edit', $social) }}"><i class="ri-pencil-line"></i></a>
          <form method="post" action="{{ route('socials.destroy', $social) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $social->title }}" onclick="return confirm('Delete the {{ addslashes($social->title) }} link? It disappears from the website.')"><i class="ri-delete-bin-line"></i></button></form>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 4, 'icon' => 'ri-share-line', 'title' => 'No social links yet', 'text' => 'Add your Facebook, Instagram or YouTube page so visitors can follow you.', 'filtered' => $filtered, 'clearUrl' => route('socials.index'), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $socials->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
