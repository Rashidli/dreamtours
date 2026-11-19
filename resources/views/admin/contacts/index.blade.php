@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('read');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Contact requests', 'count' => $contacts->total(), 'noun' => 'request', 'filtered' => $filtered,
  'intro' => 'Messages sent from the website’s contact form. Opening a request marks it as read.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('contacts.index'), 'search' => 'Search name, phone or message', 'clearUrl' => route('contacts.index'),
    'tabs' => ['name' => 'read', 'label' => 'Read status', 'options' => ['' => 'All', '0' => 'Unread', '1' => 'Read']]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>From</th><th class="d-none d-md-table-cell">Message</th><th class="d-none d-sm-table-cell">Received</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($contacts as $contact)
      <tr>
        <td>@unless($contact->is_read)<span class="cms-unread-dot" title="Unread"></span>@endunless<a class="cms-title" href="{{ route('contacts.show', $contact) }}">{{ $contact->name }}</a><span class="cms-sub">{{ $contact->phone }}</span></td>
        <td class="d-none d-md-table-cell"><span class="cms-clip text-muted">{{ str($contact->message)->limit(90) }}</span></td>
        <td class="d-none d-sm-table-cell text-nowrap text-muted">{{ $contact->created_at->format('M j, Y g:i A') }}</td>
        <td class="cms-actions">
          <button type="button" class="btn btn-sm btn-light btn-details" data-bs-toggle="modal" data-bs-target="#cms-details" data-details="#cms-d-{{ $contact->id }}">Details</button>
          <form method="post" action="{{ route('contacts.destroy', $contact) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete the request from {{ $contact->name }}" onclick="return confirm('Delete this contact request? This cannot be undone.')"><i class="ri-delete-bin-line"></i></button></form>
          <template id="cms-d-{{ $contact->id }}" data-title="{{ $contact->name }}" data-edit="{{ route('contacts.show', $contact) }}" data-edit-label="Open and mark as read">
            <dl><dt>Phone</dt><dd><a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></dd><dt>Received</dt><dd>{{ $contact->created_at->format('D, M j, Y g:i A') }}</dd><dt>Status</dt><dd>{{ $contact->is_read ? 'Read' : 'Unread' }}</dd></dl>
            <div class="cms-details-text">{!! nl2br(e($contact->message)) !!}</div>
          </template>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 4, 'icon' => 'ri-inbox-line', 'title' => 'No contact requests yet', 'text' => 'When someone sends the contact form on the website, their message lands here.', 'filtered' => $filtered, 'clearUrl' => route('contacts.index')])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $contacts->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.cms_ui.details_modal')
@include('admin.includes.footer')
