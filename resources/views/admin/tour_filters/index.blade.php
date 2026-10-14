@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Tour filters</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item active">Tour filters</li></ol></div>
  </div></div></div>
  @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
  @include('admin.includes.validation_errors')

  <div class="card"><div class="card-body">
    <p class="text-muted mb-3">Extra filters for the <b>One Day Tours</b> and <b>Multi Day Tours</b> pages, next to the built-in ones (destination, date, price, transport…). Create a filter group, add its options, then tick the matching options on each tour’s page. Only active options that at least one tour uses appear on the website.</p>
    @can('create-tour-filters')
      <form class="row g-2 align-items-end" method="post" action="{{ route('tour-filters.store') }}">@csrf
        <div class="col-md-4"><label class="form-label">New filter group</label><input class="form-control" name="name" placeholder="e.g. Theme, Season, Activity" required></div>
        <div class="col-md-4"><label class="form-label">Shown on</label><select class="form-select" name="listing">@foreach(\App\Models\TourFilter::LISTINGS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-add-line"></i> Add filter</button></div>
      </form>
    @endcan
  </div></div>

  @forelse($filters as $filter)
    <div class="card" id="filter-{{ $filter->id }}"><div class="card-body">
      <form class="row g-2 align-items-end" method="post" action="{{ route('tour-filters.update', $filter) }}">@csrf @method('PUT')
        <div class="col-md-3"><label class="form-label small">Filter name</label><input class="form-control" name="name" value="{{ $filter->name }}" required></div>
        <div class="col-md-3"><label class="form-label small">Shown on</label><select class="form-select" name="listing">@foreach(\App\Models\TourFilter::LISTINGS as $key => $label)<option value="{{ $key }}" @selected($filter->listing === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-1"><label class="form-label small">Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ $filter->sort_order }}"></div>
        <div class="col-md-2"><label class="small"><input type="checkbox" name="is_active" value="1" @checked($filter->is_active)> Active</label></div>
        <div class="col-md-3 d-flex gap-1">@can('edit-tour-filters')<button class="btn btn-primary btn-sm"><i class="ri-save-line"></i> Save</button>@endcan</div>
      </form>
      @can('delete-tour-filters')
        <form class="mt-1" method="post" action="{{ route('tour-filters.destroy', $filter) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0" onclick="return confirm('Remove the whole “{{ $filter->name }}” filter and its {{ $filter->options->count() }} options? Tours stay as they are.')">Remove this filter group</button></form>
      @endcan

      <div class="table-responsive mt-3"><table class="table table-sm align-middle mb-2">
        <thead><tr><th>Option</th><th style="width:90px">Order</th><th style="width:90px">Active</th><th style="width:110px">Used by</th><th style="width:150px"></th></tr></thead>
        <tbody>
        @forelse($filter->options as $option)
          <tr>
            <form method="post" action="{{ route('tour-filter-options.update', $option) }}" id="opt-{{ $option->id }}">@csrf @method('PUT')</form>
            <td><input form="opt-{{ $option->id }}" class="form-control form-control-sm" name="name" value="{{ $option->name }}" required></td>
            <td><input form="opt-{{ $option->id }}" class="form-control form-control-sm" type="number" min="0" name="sort_order" value="{{ $option->sort_order }}"></td>
            <td><input form="opt-{{ $option->id }}" type="checkbox" class="form-check-input" name="is_active" value="1" @checked($option->is_active)></td>
            <td>{{ $option->tours_count }} {{ str('tour')->plural($option->tours_count) }}</td>
            <td class="text-nowrap">
              @can('edit-tour-filters')<button form="opt-{{ $option->id }}" class="btn btn-sm btn-outline-primary">Save</button>@endcan
              @can('delete-tour-filters')<form class="d-inline" method="post" action="{{ route('tour-filter-options.destroy', $option) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ $option->tours_count ? 'This option is used by '.$option->tours_count.' tour(s). It will be removed from them — the tours themselves are kept. Continue?' : 'Remove this option?' }}')"><i class="ri-delete-bin-line"></i></button></form>@endcan
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-muted">No options yet.</td></tr>
        @endforelse
        </tbody>
      </table></div>
      @can('create-tour-filters')
        <form class="d-flex gap-2" method="post" action="{{ route('tour-filter-options.store', $filter) }}">@csrf
          <input class="form-control form-control-sm" style="max-width:280px" name="name" placeholder="New option, e.g. Nature" required>
          <button class="btn btn-sm btn-outline-primary"><i class="ri-add-line"></i> Add option</button>
        </form>
      @endcan
    </div></div>
  @empty
    <div class="card"><div class="card-body text-muted">No filter groups yet.</div></div>
  @endforelse
</div></div></div>
@include('admin.includes.footer')
