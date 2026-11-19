@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4>Tour filters</h4><span class="cat-count">{{ $filters->count() }} {{ Str::plural('group', $filters->count()) }}</span></div>
    @can('create-tour-filters')<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filter-new"><i class="ri-add-line"></i> New filter group</button>@endcan
  </div>
  <p class="cat-intro">Extra filters for the <b>One Day Tours</b> and <b>Multi Day Tours</b> pages, next to the built-in ones (destination, date, price, transport…). Create a filter group, add its options, then tick the matching options on each tour’s page. Only active options that at least one tour uses appear on the website.</p>

  @if(session('message'))<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="ri-check-line me-1"></i>{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.includes.validation_errors')

  @forelse($filters as $filter)
    <div class="card tl-card" id="filter-{{ $filter->id }}" style="scroll-margin-top:90px"><div class="card-body p-0">
      <div class="cat-group-head">
        <span class="tf-icon"><i class="ri-filter-3-line"></i></span>
        <div class="flex-grow-1" style="min-width:180px">
          <h5>{{ $filter->name }} @if($filter->is_active)<span class="badge bg-success bg-opacity-10 text-success ms-1 align-middle">Active</span>@else<span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 align-middle">Inactive</span>@endif</h5>
          <div class="cat-group-meta">Shown on {{ \App\Models\TourFilter::LISTINGS[$filter->listing] ?? $filter->listing }} · {{ $filter->options->count() }} {{ Str::plural('option', $filter->options->count()) }} · order {{ $filter->sort_order }}</div>
        </div>
        <div class="d-flex gap-2">
          @can('edit-tour-filters')<button type="button" class="btn btn-light" data-bs-toggle="collapse" data-bs-target="#filter-settings-{{ $filter->id }}" aria-expanded="false" aria-controls="filter-settings-{{ $filter->id }}"><i class="ri-settings-3-line"></i> Group settings</button>@endcan
          @can('delete-tour-filters')
            <form method="post" action="{{ route('tour-filters.destroy', $filter) }}" class="m-0">@csrf @method('DELETE')<button class="btn btn-outline-danger" title="Remove this filter group" aria-label="Remove the {{ $filter->name }} filter group" onclick="return confirm('Remove the whole “{{ $filter->name }}” filter and its {{ $filter->options->count() }} options? Tours stay as they are.')"><i class="ri-delete-bin-line"></i></button></form>
          @endcan
        </div>
      </div>

      <div class="collapse" id="filter-settings-{{ $filter->id }}">
        <form class="cat-group-settings cat-form" method="post" action="{{ route('tour-filters.update', $filter) }}">@csrf @method('PUT')
          <div class="row g-3 align-items-end">
            <div class="col-md-4"><label class="fw-semibold" for="f-{{ $filter->id }}-name">Filter name</label><input id="f-{{ $filter->id }}-name" class="form-control" name="name" value="{{ $filter->name }}" required></div>
            <div class="col-md-3"><label class="fw-semibold" for="f-{{ $filter->id }}-listing">Shown on</label><select id="f-{{ $filter->id }}-listing" class="form-select" name="listing">@foreach(\App\Models\TourFilter::LISTINGS as $key => $listingLabel)<option value="{{ $key }}" @selected($filter->listing === $key)>{{ $listingLabel }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="fw-semibold" for="f-{{ $filter->id }}-order">Order</label><input id="f-{{ $filter->id }}-order" class="form-control" type="number" min="0" name="sort_order" value="{{ $filter->sort_order }}"></div>
            <div class="col-6 col-md-1"><div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="f-{{ $filter->id }}-active" name="is_active" value="1" @checked($filter->is_active)><label class="form-check-label" for="f-{{ $filter->id }}-active">Active</label></div></div>
            <div class="col-md-2 text-md-end">@can('edit-tour-filters')<button class="btn btn-primary"><i class="ri-save-line"></i> Save group</button>@endcan</div>
          </div>
        </form>
      </div>

      <div class="table-responsive"><table class="table align-middle tl-table mb-0">
        <thead><tr><th>Option</th><th style="width:110px">Order</th><th style="width:100px">Active</th><th style="width:120px">Used by</th><th class="text-end" style="width:150px">Actions</th></tr></thead>
        <tbody>
        @forelse($filter->options as $option)
          <tr>
            <form method="post" action="{{ route('tour-filter-options.update', $option) }}" id="opt-{{ $option->id }}">@csrf @method('PUT')</form>
            <td><input form="opt-{{ $option->id }}" class="form-control cat-option-input" name="name" value="{{ $option->name }}" required aria-label="Option name"></td>
            <td><input form="opt-{{ $option->id }}" class="form-control cat-order-input" type="number" min="0" name="sort_order" value="{{ $option->sort_order }}" aria-label="Order"></td>
            <td><div class="form-check form-switch m-0"><input form="opt-{{ $option->id }}" type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" @checked($option->is_active) aria-label="Active"></div></td>
            <td class="text-nowrap">@if($option->tours_count){{ $option->tours_count }} {{ str('tour')->plural($option->tours_count) }}@else<span class="cat-muted">No tours</span>@endif</td>
            <td class="cat-actions">
              @can('edit-tour-filters')<button form="opt-{{ $option->id }}" class="btn btn-sm btn-outline-secondary">Save</button>@endcan
              @can('delete-tour-filters')<form method="post" action="{{ route('tour-filter-options.destroy', $option) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Remove option" aria-label="Remove {{ $option->name }}" onclick="return confirm('{{ $option->tours_count ? 'This option is used by '.$option->tours_count.' tour(s). It will be removed from them — the tours themselves are kept. Continue?' : 'Remove this option?' }}')"><i class="ri-delete-bin-line"></i></button></form>@endcan
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted py-4">No options yet — add the first one below.</td></tr>
        @endforelse
        </tbody>
      </table></div>
      @can('create-tour-filters')
        <form class="cat-add-row" method="post" action="{{ route('tour-filter-options.store', $filter) }}">@csrf
          <input class="form-control" name="name" placeholder="New option, e.g. Nature" required aria-label="New option name">
          <button class="btn btn-outline-secondary"><i class="ri-add-line"></i> Add option</button>
        </form>
      @endcan
    </div></div>
  @empty
    <div class="card"><div class="card-body">
      <div class="cat-empty">
        <div class="cat-empty-icon"><i class="ri-filter-3-line"></i></div>
        <h5>No filter groups yet</h5><p>Create a group such as “Theme” or “Season”, then add its options.</p>
        @can('create-tour-filters')<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filter-new"><i class="ri-add-line"></i> New filter group</button>@endcan
      </div>
    </div></div>
  @endforelse
</div></div></div>

@can('create-tour-filters')
  <div class="modal fade" id="filter-new" tabindex="-1" aria-labelledby="filter-new-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="post" action="{{ route('tour-filters.store') }}" class="cat-form">@csrf
      <div class="modal-header"><h5 class="modal-title" id="filter-new-title">New filter group</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="fw-semibold tf-req" for="filter-new-name">Name</label><input id="filter-new-name" class="form-control" name="name" placeholder="e.g. Theme, Season, Activity" required></div>
        <div><label class="fw-semibold" for="filter-new-listing">Shown on</label><select id="filter-new-listing" class="form-select" name="listing">@foreach(\App\Models\TourFilter::LISTINGS as $key => $listingLabel)<option value="{{ $key }}">{{ $listingLabel }}</option>@endforeach</select><div class="form-text">You add the options right after creating the group.</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-add-line"></i> Add filter</button></div>
    </form>
  </div></div></div>
@endcan
@include('admin.includes.footer')
