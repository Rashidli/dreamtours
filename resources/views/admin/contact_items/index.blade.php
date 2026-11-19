@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('status');
  $newAction = ['url' => route('contact_items.create'), 'label' => 'New contact item', 'can' => 'create-contact_items'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Contact information', 'count' => $contact_items->total(), 'noun' => 'item', 'filtered' => $filtered, 'action' => $newAction,
  'intro' => 'Phone, email, address and opening hours shown on the Contact page and in the footer.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('contact_items.index'), 'search' => 'Search title or value', 'clearUrl' => route('contact_items.index'),
    'tabs' => ['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Item</th><th class="d-none d-md-table-cell">Link</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($contact_items as $item)
      <tr>
        <td><div class="cms-cell-flex"><span class="cms-icon-preview"><i class="{{ $item->icon ?: 'ri-information-line' }}"></i></span><div><a class="cms-title" href="{{ route('contact_items.edit', $item) }}">{{ $item->title }}</a><span class="cms-sub">{{ $item->value }}</span></div></div></td>
        <td class="d-none d-md-table-cell">@if($item->link)<span class="cms-clip text-muted" title="{{ $item->link }}">{{ $item->link }}</span>@else<span class="text-muted">—</span>@endif</td>
        <td><span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">
          <a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $item->title }}" href="{{ route('contact_items.edit', $item) }}"><i class="ri-pencil-line"></i></a>
          <form method="post" action="{{ route('contact_items.destroy', $item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $item->title }}" onclick="return confirm('Delete this contact item? It disappears from the website.')"><i class="ri-delete-bin-line"></i></button></form>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 4, 'icon' => 'ri-contacts-book-line', 'title' => 'No contact details yet', 'text' => 'Add your phone number, email and address so customers can reach you.', 'filtered' => $filtered, 'clearUrl' => route('contact_items.index'), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $contact_items->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
