@include('admin.includes.header')
@php($title = $type ? $types[$type][0] : 'Team, partners & stats')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">{{ $title }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item active">{{ $title }}</li></ol></div>
  </div></div></div>

  <div class="card"><div class="card-body">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">{{ $title }}</h4>
      <a class="btn btn-primary" href="{{ route('company-content.create', array_filter(['type' => $type])) }}"><i class="ri-add-line align-middle me-1"></i> New {{ $type ? $types[$type][1] : 'item' }}</a>
    </div>
    @if($type === 'hero_slide')<p class="text-muted mt-2 mb-0">Slides rotate on the homepage in the order below. Inactive slides stay here but are not shown.</p>@endif
    @if($type === 'team')<p class="text-muted mt-2 mb-0">Members marked “Always first” (the founder / CEO) lead the list; everyone else follows the Order number.</p>@endif
    <form class="row g-2 my-3">
      <div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search"></div>
      <div class="col-md-3"><select class="form-select" name="type"><option value="">All types</option>@foreach($types as $key => $meta)<option value="{{ $key }}" @selected($type === $key)>{{ $meta[0] }}</option>@endforeach</select></div>
      <div class="col-md-2"><select class="form-select" name="status"><option value="">All</option><option value="1" @selected(request('status') === '1')>Active</option><option value="0" @selected(request('status') === '0')>Inactive</option></select></div>
      <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
    </form>
    <div class="table-responsive"><table class="table align-middle">
      <thead><tr><th style="width:90px">Image</th><th>Title</th>@unless($type)<th>Type</th>@endunless<th>Order</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($items as $item)
        <tr>
          <td>@if($item->image)<img src="{{ $item->image }}" alt="" style="width:80px;height:48px;object-fit:cover;border-radius:6px">@endif</td>
          <td>{{ $item->title }}@if($item->subtitle)<br><small class="text-muted">{{ $item->subtitle }}</small>@endif @if($item->pinned)<span class="badge bg-info ms-1">Always first</span>@endif</td>
          @unless($type)<td>{{ $types[$item->type][0] ?? $item->type }}</td>@endunless
          <td>{{ $item->sort_order }}</td>
          <td><span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td class="text-nowrap"><a class="btn btn-sm btn-primary" title="Edit" href="{{ route('company-content.edit', $item) }}"><i class="ri-pencil-line"></i></a>
            <form class="d-inline" method="post" action="{{ route('company-content.destroy', $item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form></td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-muted">Nothing here yet.</td></tr>
      @endforelse
      </tbody>
    </table></div>
    {{ $items->links('admin.vendor.pagination.bootstrap-5') }}
  </div></div>
</div></div></div>
@include('admin.includes.footer')
