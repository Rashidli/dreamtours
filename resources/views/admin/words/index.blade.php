@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Site copy', 'count' => $words->total(), 'noun' => 'text', 'filtered' => $filtered,
  'intro' => 'Button, heading and label text used across the website. Each text is swapped in place; its position on the page does not change.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('words.index'), 'search' => 'Search text', 'clearUrl' => route('words.index')])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Text</th><th class="text-end" style="width:90px">Actions</th></tr></thead>
    <tbody>
    @forelse($words as $word)
      <tr>
        <td class="cms-wrap">@can('edit-words')<a class="cms-title" href="{{ route('words.edit', $word) }}">{{ $word->title }}</a>@else{{ $word->title }}@endcan</td>
        <td class="cms-actions">@can('edit-words')<a href="{{ route('words.edit', $word) }}" class="btn btn-sm btn-primary" title="Edit" aria-label="Edit this text"><i class="ri-pencil-line"></i></a>@endcan</td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 2, 'icon' => 'ri-translate-2', 'title' => 'No site copy yet', 'text' => 'Texts appear here once the website uses them.', 'filtered' => $filtered, 'clearUrl' => route('words.index')])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $words->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.includes.footer')
