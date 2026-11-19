@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $title = $type ? $types[$type][0] : 'Team, partners & stats';
  $noun = $type ? $types[$type][1] : 'item';
  $filtered = request()->filled('q') || request()->filled('status') || (request()->filled('type') && !$type);
  $newAction = ['url' => route('company-content.create', array_filter(['type' => $type])), 'label' => 'New '.$noun];
  $intros = [
    'hero_slide' => 'Slides rotate at the top of the homepage in the order below. Inactive slides stay here but are not shown.',
    'team' => 'Members marked “Always first” (the founder / CEO) lead the list; everyone else follows the Order number.',
    'private_place' => 'Places shown on the Private tours page. Lower order numbers come first.',
  ];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => $title, 'count' => $items->total(), 'noun' => $noun, 'filtered' => $filtered, 'action' => $newAction,
  'intro' => e($intros[$type] ?? 'Short blocks used across the website: team, partners, statistics, advantages, values and step lists.')])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('company-content.index'), 'search' => 'Search titles', 'clearUrl' => route('company-content.index', array_filter(['type' => $type])), 'ignore' => $type ? ['type'] : [],
    'tabs' => ['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']],
    'selects' => [['name' => 'type', 'label' => 'Type', 'all' => 'All types', 'options' => collect($types)->map(fn ($meta) => $meta[0])->all()]]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th class="d-none d-sm-table-cell" style="width:88px">Image</th><th>{{ $type ? ucfirst($types[$type][2]['title'] ?? 'Title') : 'Title' }}</th>@unless($type)<th class="d-none d-md-table-cell">Type</th>@endunless<th class="d-none d-md-table-cell">Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($items as $item)
      <tr>
        <td class="d-none d-sm-table-cell">@if($item->image)<img src="{{ $item->image }}" alt="" class="cms-thumb" loading="lazy">@else<span class="cms-thumb-empty"><i class="ri-image-line"></i></span>@endif</td>
        <td class="cms-wrap"><a class="cms-title" href="{{ route('company-content.edit', $item) }}">{{ $item->title }}</a>@if($item->pinned) <span class="badge bg-info ms-1">Always first</span>@endif @if($item->subtitle)<span class="cms-sub">{{ $item->subtitle }}</span>@endif</td>
        @unless($type)<td class="d-none d-md-table-cell text-nowrap">{{ $types[$item->type][0] ?? $item->type }}</td>@endunless
        <td class="d-none d-md-table-cell">{{ $item->sort_order }}</td>
        <td><span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="cms-actions">
          <button type="button" class="btn btn-sm btn-light btn-details" data-bs-toggle="modal" data-bs-target="#cms-details" data-details="#cms-d-{{ $item->id }}">Details</button>
          <a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $item->title }}" href="{{ route('company-content.edit', $item) }}"><i class="ri-pencil-line"></i></a>
          <form method="post" action="{{ route('company-content.destroy', $item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $item->title }}" onclick="return confirm('Delete this {{ $types[$item->type][1] ?? 'item' }}? It disappears from the website.')"><i class="ri-delete-bin-line"></i></button></form>
          @php $fields = $types[$item->type][2] ?? []; @endphp
          <template id="cms-d-{{ $item->id }}" data-title="{{ $item->title }}" data-edit="{{ route('company-content.edit', $item) }}">
            @if($item->image)<img src="{{ $item->image }}" alt="" class="cms-details-img">@endif
            <dl>
              <dt>Type</dt><dd>{{ $types[$item->type][0] ?? $item->type }}</dd>
              @if($item->subtitle)<dt>{{ $fields['subtitle'] ?? 'Subtitle' }}</dt><dd>{{ $item->subtitle }}</dd>@endif
              @if($item->value)<dt>{{ $fields['value'] ?? 'Value' }}</dt><dd>{{ $item->value }}</dd>@endif
              @if($item->url)<dt>{{ $fields['url'] ?? 'Link' }}</dt><dd><code>{{ $item->url }}</code></dd>@endif
              <dt>Order</dt><dd>{{ $item->sort_order }}{{ $item->pinned ? ' · Always first' : '' }}</dd>
              <dt>Status</dt><dd>{{ $item->is_active ? 'Active — shown on the website' : 'Inactive — hidden' }}</dd>
            </dl>
            @if($item->description)<div class="cms-details-text">{!! nl2br(e($item->description)) !!}</div>@endif
          </template>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => $type ? 5 : 6, 'icon' => 'ri-team-line', 'title' => 'Nothing here yet', 'text' => 'Add the first '.$noun.' to show it on the website.', 'filtered' => $filtered, 'clearUrl' => route('company-content.index', array_filter(['type' => $type])), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $items->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.cms_ui.details_modal')
@include('admin.includes.footer')
