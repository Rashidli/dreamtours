@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Vehicles & seating</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Vehicles & seating</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">Seating templates for each kind of vehicle. Add one to a departure date to seat its passengers; the date keeps its own copy, so changing a template later never moves anyone.</p>
  @can('create-vehicle-layouts')<a class="btn btn-primary text-nowrap" href="{{ route('vehicle-layouts.create') }}"><i class="ri-add-line me-1"></i>New template</a>@endcan
</div>

<div class="row g-3">
@forelse($layouts as $layout)
  <div class="col-sm-6 col-xl-4">
    <div class="card h-100 mb-0 {{ $layout->is_active ? '' : 'opacity-75' }}"><div class="card-body d-flex gap-3">
      <div class="flex-shrink-0">@include('admin.vehicles._plan', ['plan' => $layout->layout, 'small' => true])</div>
      <div class="d-flex flex-column flex-grow-1">
        <h5 class="mb-1">{{ $layout->name }}</h5>
        <div class="text-muted mb-2">{{ $layout->seat_count }} passenger seats{{ $layout->transportation ? ' · '.$layout->transportation->name : '' }}@unless($layout->is_active) · <span class="badge bg-light text-muted">hidden</span>@endunless</div>
        <div class="mt-auto d-flex flex-wrap gap-1">
          @can('edit-vehicle-layouts')<a class="btn btn-sm btn-outline-primary" href="{{ route('vehicle-layouts.edit', $layout) }}"><i class="ri-pencil-line"></i> Edit</a>@endcan
          @can('create-vehicle-layouts')<a class="btn btn-sm btn-light" href="{{ route('vehicle-layouts.create', ['copy' => $layout->id]) }}"><i class="ri-file-copy-line"></i> Copy</a>@endcan
          @can('delete-vehicle-layouts')<form method="post" action="{{ route('vehicle-layouts.destroy', $layout) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Remove “'.$layout->name.'”? Dates that already use it keep their seats.') }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Remove"><i class="ri-delete-bin-line"></i></button></form>@endcan
        </div>
      </div>
    </div></div>
  </div>
@empty
  <div class="col-12"><div class="card"><div class="card-body crm-empty"><i class="ri-bus-2-line"></i>No templates yet.</div></div></div>
@endforelse
</div>
</div></div></div>@include('admin.includes.footer')
