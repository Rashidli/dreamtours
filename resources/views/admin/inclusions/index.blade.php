@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Inclusions</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item active">Inclusions</li></ol></div>
  </div></div></div>
  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')
  <div class="card"><div class="card-body">
    <h4 class="mb-1">Included / not included library</h4>
    <p class="text-muted">Write each line once, then tick it on the tours it applies to (tour page → “What’s included”). Editing a line here updates every tour that uses it. Inactive lines stay ticked on their tours but are not shown on the website.</p>
    @can('create-inclusions')
      <form class="row g-2 align-items-end border rounded p-3 mb-3" method="post" action="{{ route('inclusions.store') }}">@csrf
        <div class="col-md-2"><label class="form-label small">List</label><select class="form-select" name="type">@foreach(\App\Models\Inclusion::TYPES as $key => $label)<option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">Icon</label><select class="form-select" name="icon"><option value="">No icon</option>@foreach(\App\Models\TourContentItem::ICONS as $icon => $label)<option value="{{ $icon }}" @selected(old('icon') === $icon)>{{ $icon }} {{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small">Title <span class="text-danger">*</span></label><input class="form-control" name="en[title]" value="{{ old('en.title') }}" required maxlength="255" placeholder="e.g. Round-trip coach transportation"></div>
        <div class="col-md-4"><label class="form-label small">Description</label><input class="form-control" name="en[description]" value="{{ old('en.description') }}" maxlength="2000" placeholder="Optional detail shown under the title"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
      </form>
    @endcan
    <form class="row g-2 mb-3">
      <div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search by title"></div>
      <div class="col-md-3"><select class="form-select" name="type"><option value="">Both lists</option>@foreach(\App\Models\Inclusion::TYPES as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
    </form>

    @foreach(\App\Models\Inclusion::TYPES as $type => $label)
      @continue(!$groups->has($type))
      <h5 class="mt-4">{{ $label }} <span class="text-muted small">({{ $groups[$type]->count() }})</span></h5>
      <div class="table-responsive"><table class="table align-middle table-sm">
        <thead><tr><th style="width:80px">Order</th><th style="width:150px">Icon</th><th>Title</th><th>Description</th><th style="width:80px">Active</th><th style="width:110px">Used on</th><th style="width:150px"></th></tr></thead>
        <tbody>
        @foreach($groups[$type] as $item)
          <tr>
            <td><form method="post" action="{{ route('inclusions.update', $item) }}" id="inclusion-{{ $item->id }}">@csrf @method('PUT')<input type="hidden" name="type" value="{{ $item->type }}"><input type="hidden" name="is_active_present" value="1"></form><input form="inclusion-{{ $item->id }}" type="number" min="0" class="form-control form-control-sm" name="sort_order" value="{{ $item->sort_order }}"></td>
            <td><select form="inclusion-{{ $item->id }}" class="form-select form-select-sm" name="icon"><option value="">—</option>@foreach(\App\Models\TourContentItem::ICONS as $icon => $iconLabel)<option value="{{ $icon }}" @selected($item->icon === $icon)>{{ $icon }} {{ $iconLabel }}</option>@endforeach</select></td>
            <td><input form="inclusion-{{ $item->id }}" class="form-control form-control-sm" name="en[title]" value="{{ $item->title }}" required maxlength="255"></td>
            <td><textarea form="inclusion-{{ $item->id }}" class="form-control form-control-sm" name="en[description]" rows="1" maxlength="2000">{{ $item->description }}</textarea></td>
            <td class="text-center"><input form="inclusion-{{ $item->id }}" class="form-check-input" type="checkbox" name="is_active" value="1" @checked($item->is_active)></td>
            <td>@if($item->tours_count)<span title="{{ $item->tours->pluck('name')->implode(', ') }}">{{ $item->tours_count }} {{ Str::plural('tour', $item->tours_count) }}</span>@else<span class="text-muted">not used</span>@endif</td>
            <td class="text-end text-nowrap">
              @can('edit-inclusions')<button form="inclusion-{{ $item->id }}" class="btn btn-sm btn-primary">Save</button>@endcan
              @can('delete-inclusions')<form method="post" action="{{ route('inclusions.destroy', $item) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete “{{ addslashes($item->title) }}”?{{ $item->tours_count ? ' It will be removed from '.$item->tours_count.' tour(s).' : '' }}')"><i class="ri-delete-bin-line"></i></button></form>@endcan
            </td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
    @endforeach
    @if($groups->isEmpty())<p class="text-muted">No lines found.</p>@endif
  </div></div>
</div></div></div>
@include('admin.includes.footer')
