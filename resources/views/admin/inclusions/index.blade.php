@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $type = (string) request('type', '');
  $filtered = request()->filled('q') || $type !== '';
  $total = $groups->flatten(1)->count();
  // Re-open the "New line" dialog when its own submission came back with errors (row edits send is_active_present).
  $reopenAdd = $errors->any() && old('en.title') !== null && old('is_active_present') === null;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4>Inclusions</h4><span class="cat-count">{{ $total }} {{ Str::plural('line', $total) }}{{ $filtered ? ' match' : '' }}</span></div>
    @can('create-inclusions')<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inclusion-new"><i class="ri-add-line"></i> New line</button>@endcan
  </div>
  <p class="cat-intro">The shared “Included / Not included” library. Write each line once, then tick it on the tours it applies to (tour page → “What’s included”). Editing a line here updates every tour that uses it. Inactive lines stay ticked on their tours but are not shown on the website.</p>

  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')

  <div class="card tl-card"><div class="card-body p-0">
    <form method="get" action="{{ route('inclusions.index') }}">
      <div class="tl-tabs-bar">
        <div class="tl-tabs" role="radiogroup" aria-label="List">@foreach(['' => 'Both lists'] + \App\Models\Inclusion::TYPES as $value => $tabLabel)<input type="radio" class="btn-check" name="type" id="type-{{ $value ?: 'all' }}" value="{{ $value }}" @checked($type === (string) $value) onchange="this.form.submit()"><label class="tl-tab" for="type-{{ $value ?: 'all' }}">{{ $tabLabel }}</label>@endforeach</div>
      </div>
      <div class="tl-search-bar">
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search lines by title" aria-label="Search lines by title">@if(request()->filled('q'))<a href="{{ route('inclusions.index', $type !== '' ? ['type' => $type] : []) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <button type="submit" class="visually-hidden">Search</button>
      </div>
    </form>

    @if($groups->isNotEmpty())
      <div class="table-responsive"><table class="table table-hover align-middle tl-table mb-0">
        <thead><tr><th class="cat-icon-cell">Icon</th><th>Line</th><th class="d-none d-md-table-cell">Used on</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach(\App\Models\Inclusion::TYPES as $groupType => $groupLabel)
          @continue(!$groups->has($groupType))
          <tr class="cat-group-row"><td colspan="5"><i class="{{ $groupType === 'included' ? 'ri-checkbox-circle-line text-success' : 'ri-close-circle-line text-danger' }}"></i> {{ $groupLabel }} <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $groups[$groupType]->count() }}</span></td></tr>
          @foreach($groups[$groupType] as $item)
            <tr>
              <td class="cat-icon-cell">{{ $item->icon ?: '—' }}</td>
              <td>
                <a href="#" class="fw-semibold text-body" data-bs-toggle="modal" data-bs-target="#inclusion-edit-{{ $item->id }}">{{ $item->title }}</a>
                @if($item->description)<div class="cat-desc">{{ $item->description }}</div>@endif
              </td>
              <td class="d-none d-md-table-cell text-nowrap">@if($item->tours_count)<span title="{{ $item->tours->pluck('name')->implode(', ') }}">{{ $item->tours_count }} {{ Str::plural('tour', $item->tours_count) }}</span>@else<span class="cat-muted">Not used</span>@endif</td>
              <td>@if($item->is_active)<span class="badge bg-success bg-opacity-10 text-success">Active</span>@else<span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>@endif</td>
              <td class="cat-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#inclusion-edit-{{ $item->id }}">{{ auth()->user()->can('edit-inclusions') ? 'Edit' : 'Details' }}</button>
                @can('delete-inclusions')<form method="post" action="{{ route('inclusions.destroy', $item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $item->title }}" onclick="return confirm('Delete “{{ addslashes($item->title) }}”?{{ $item->tours_count ? ' It will be removed from '.$item->tours_count.' tour(s).' : '' }}')"><i class="ri-delete-bin-line"></i></button></form>@endcan
              </td>
            </tr>
          @endforeach
        @endforeach
        </tbody>
      </table></div>
    @else
      <div class="cat-empty">
        <div class="cat-empty-icon"><i class="{{ $filtered ? 'ri-search-line' : 'ri-list-check-2' }}"></i></div>
        @if($filtered)
          <h5>No lines match</h5><p>Try another title or list.</p><a class="btn btn-light" href="{{ route('inclusions.index') }}">Clear filters</a>
        @else
          <h5>No lines yet</h5><p>Add lines such as “Round-trip coach transportation”, then tick them on each tour.</p>
          @can('create-inclusions')<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inclusion-new"><i class="ri-add-line"></i> New line</button>@endcan
        @endif
      </div>
    @endif
  </div></div>

</div></div></div>

@can('create-inclusions')
  <div class="modal fade" id="inclusion-new" tabindex="-1" aria-labelledby="inclusion-new-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="post" action="{{ route('inclusions.store') }}" class="cat-form">@csrf
      <div class="modal-header"><h5 class="modal-title" id="inclusion-new-title">New line</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-sm-6"><label class="fw-semibold" for="inc-new-type">List</label><select id="inc-new-type" class="form-select" name="type">@foreach(\App\Models\Inclusion::TYPES as $key => $typeLabel)<option value="{{ $key }}" @selected(old('type', $type ?: 'included') === $key)>{{ $typeLabel }}</option>@endforeach</select></div>
          <div class="col-sm-6"><label class="fw-semibold" for="inc-new-icon">Icon</label><select id="inc-new-icon" class="form-select" name="icon"><option value="">No icon</option>@foreach(\App\Models\TourContentItem::ICONS as $icon => $iconLabel)<option value="{{ $icon }}" @selected(old('icon') === $icon)>{{ $icon }} {{ $iconLabel }}</option>@endforeach</select></div>
          <div class="col-12"><label class="fw-semibold tf-req" for="inc-new-title">Title</label><input id="inc-new-title" class="form-control" name="en[title]" value="{{ old('en.title') }}" required maxlength="255" placeholder="e.g. Round-trip coach transportation"></div>
          <div class="col-12"><label class="fw-semibold" for="inc-new-desc">Description</label><textarea id="inc-new-desc" class="form-control" name="en[description]" rows="3" maxlength="2000" placeholder="Optional detail shown under the title">{{ old('en.description') }}</textarea></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-add-line"></i> Add line</button></div>
    </form>
  </div></div></div>
@endcan

@foreach($groups->flatten(1) as $item)
  <div class="modal fade" id="inclusion-edit-{{ $item->id }}" tabindex="-1" aria-labelledby="inclusion-edit-{{ $item->id }}-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <form method="post" action="{{ route('inclusions.update', $item) }}" id="inclusion-{{ $item->id }}" class="cat-form">@csrf @method('PUT')<input type="hidden" name="type" value="{{ $item->type }}"><input type="hidden" name="is_active_present" value="1">
      <div class="modal-header"><h5 class="modal-title" id="inclusion-edit-{{ $item->id }}-title">{{ \App\Models\Inclusion::TYPES[$item->type] ?? 'Line' }}: {{ $item->title }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="fw-semibold tf-req" for="inc-{{ $item->id }}-title">Title</label><input id="inc-{{ $item->id }}-title" class="form-control" name="en[title]" value="{{ $item->title }}" required maxlength="255"></div>
          <div class="col-12"><label class="fw-semibold" for="inc-{{ $item->id }}-desc">Description</label><textarea id="inc-{{ $item->id }}-desc" class="form-control" name="en[description]" rows="3" maxlength="2000">{{ $item->description }}</textarea></div>
          <div class="col-8"><label class="fw-semibold" for="inc-{{ $item->id }}-icon">Icon</label><select id="inc-{{ $item->id }}-icon" class="form-select" name="icon"><option value="">No icon</option>@foreach(\App\Models\TourContentItem::ICONS as $icon => $iconLabel)<option value="{{ $icon }}" @selected($item->icon === $icon)>{{ $icon }} {{ $iconLabel }}</option>@endforeach</select></div>
          <div class="col-4"><label class="fw-semibold" for="inc-{{ $item->id }}-order">Order</label><input id="inc-{{ $item->id }}-order" type="number" min="0" class="form-control" name="sort_order" value="{{ $item->sort_order }}"></div>
          <div class="col-12"><div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="inc-{{ $item->id }}-active" name="is_active" value="1" @checked($item->is_active)><label class="form-check-label" for="inc-{{ $item->id }}-active">Active<small>Inactive lines stay ticked on tours but are hidden on the website.</small></label></div></div>
          <div class="col-12"><div class="tf-subtle"><div class="fw-semibold mb-1">Used on {{ $item->tours_count }} {{ Str::plural('tour', $item->tours_count) }}</div>@if($item->tours_count)<div class="cat-chips">@foreach($item->tours as $tour)<span class="badge bg-light text-body">{{ $tour->name }}</span>@endforeach</div>@else<div class="text-muted">Not ticked on any tour yet.</div>@endif</div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ auth()->user()->can('edit-inclusions') ? 'Cancel' : 'Close' }}</button>@can('edit-inclusions')<button class="btn btn-primary"><i class="ri-save-line"></i> Save</button>@endcan</div>
    </form>
  </div></div></div>
@endforeach
@if($reopenAdd)
<script>document.addEventListener('DOMContentLoaded', function () { var el = document.getElementById('inclusion-new'); if (el && window.bootstrap) new bootstrap.Modal(el).show(); });</script>
@endif
@include('admin.includes.footer')
