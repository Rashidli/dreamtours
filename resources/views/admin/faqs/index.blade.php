@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('group') || request()->filled('status');
  $newAction = ['url' => route('faqs.create', array_filter(['group' => request('group')])), 'label' => 'New FAQ', 'can' => 'create-faqs'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'FAQ', 'count' => $faqs->total(), 'noun' => 'question', 'filtered' => $filtered, 'action' => $newAction,
  'intro' => 'Trip FAQs show on the Contact page; Visa Support FAQs show on the Visa Support page. Lower order numbers come first.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('faqs.index'), 'search' => 'Search questions', 'clearUrl' => route('faqs.index'),
    'tabs' => ['name' => 'group', 'label' => 'FAQ group', 'options' => ['' => 'All'] + \App\Models\Faq::GROUPS],
    'selects' => [['name' => 'status', 'label' => 'Status', 'all' => 'All statuses', 'options' => ['1' => 'Active', '0' => 'Inactive']]]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Question</th><th class="d-none d-md-table-cell">Group</th><th class="d-none d-md-table-cell">Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($faqs as $faq)
      <tr>
        <td class="cms-wrap">@can('edit-faqs')<a class="cms-title" href="{{ route('faqs.edit', $faq) }}">{{ $faq->question }}</a>@else<span class="cms-title">{{ $faq->question }}</span>@endcan</td>
        <td class="d-none d-md-table-cell text-nowrap">{{ \App\Models\Faq::GROUPS[$faq->group] ?? $faq->group }}</td>
        <td class="d-none d-md-table-cell">{{ $faq->order }}</td>
        <td><span class="badge bg-{{ $faq->is_active ? 'success' : 'secondary' }}">{{ $faq->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">
          <button type="button" class="btn btn-sm btn-light btn-details" data-bs-toggle="modal" data-bs-target="#cms-details" data-details="#cms-d-{{ $faq->id }}">Details</button>
          @can('edit-faqs')<a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit this FAQ" href="{{ route('faqs.edit', $faq) }}"><i class="ri-pencil-line"></i></a>@endcan
          @can('delete-faqs')<form method="post" action="{{ route('faqs.destroy', $faq) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete this FAQ" onclick="return confirm('Delete this FAQ? It disappears from the website straight away.')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          <template id="cms-d-{{ $faq->id }}" data-title="{{ $faq->question }}" data-edit="{{ auth()->user()->can('edit-faqs') ? route('faqs.edit', $faq) : '' }}">
            <dl><dt>Group</dt><dd>{{ \App\Models\Faq::GROUPS[$faq->group] ?? $faq->group }}</dd><dt>Order</dt><dd>{{ $faq->order }}</dd><dt>Status</dt><dd>{{ $faq->is_active ? 'Active — shown on the website' : 'Inactive — hidden' }}</dd></dl>
            <div class="cms-details-text">{!! nl2br(e($faq->answer)) !!}</div>
          </template>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 5, 'icon' => 'ri-question-answer-line', 'title' => 'No questions yet', 'text' => 'Add the questions customers ask most; they appear on the Contact and Visa Support pages.', 'filtered' => $filtered, 'clearUrl' => route('faqs.index'), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $faqs->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.cms_ui.details_modal')
@include('admin.includes.footer')
