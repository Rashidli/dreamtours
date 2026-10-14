@include('admin.includes.header')
@php($labels = ['visa-services' => 'Visa Support Pricing', 'gallery-albums' => 'Gallery albums', 'gallery-categories' => 'Gallery categories'])
@php($title = $labels[$resource] ?? str($resource)->replace('-', ' ')->title())
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $title }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">{{ $title }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @if(session('message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">{{ $title }} list</h4>
                                @can('create-'.$resource)
                                    <a href="{{ route('domain-content.create', $resource) }}" class="btn btn-primary">
                                        <i class="ri-add-line align-middle me-1"></i> New
                                    </a>
                                @endcan
                            </div>

                            <form class="row g-2 mb-3" method="get">
                                <div class="col-md-6">
                                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search name">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">All statuses</option>
                                        <option value="1" @selected(request('status')==='1')>Active</option>
                                        <option value="0" @selected(request('status')==='0')>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary">Filter</button>
                                    <a class="btn btn-light" href="{{ route('domain-content.index', $resource) }}">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            @if($resource === 'visa-services')<th>Service fee</th><th>Processing</th>@endif
                                            @if($resource === 'gallery-albums')<th>Media</th>@endif
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($items as $key => $item)
                                        <tr>
                                            <td>{{ $items->firstItem() + $key }}</td>
                                            <td>@if($resource === 'visa-services' && ($item->country || $item->destination))<strong>{{ $item->country ?: $item->destination?->name }}</strong><br><small class="text-muted">{{ data_get($item, $label) }}</small>@else{{ data_get($item, $label) }}@endif</td>
                                            @if($resource === 'visa-services')
                                                <td>{{ $item->price !== null ? money($item->price, $item->currency) : '—' }}@if($item->third_party_fee !== null)<br><small class="text-muted">+ {{ money($item->third_party_fee, $item->currency) }} third-party</small>@endif</td>
                                                <td>{{ $item->processing_time ?: '—' }}</td>
                                            @endif
                                            @if($resource === 'gallery-albums')
                                                <td>{{ $item->media()->count() }}</td>
                                            @endif
                                            <td>
                                                @if($item->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($resource === 'gallery-albums')
                                                    <a href="{{ route('gallery-media.index', $item) }}" class="btn btn-sm btn-info" title="Media">
                                                        <i class="ri-image-line"></i>
                                                    </a>
                                                @endif
                                                @can('edit-'.$resource)
                                                    <a href="{{ route('domain-content.edit', [$resource, $item]) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endcan
                                                @can('delete-'.$resource)
                                                    <form action="{{ route('domain-content.destroy', [$resource, $item]) }}" method="post" style="display: inline-block">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                                                onclick="return confirm('Delete this item?')">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">No items found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">{{ $items->links('admin.vendor.pagination.bootstrap-5') }}</div>
                            <small class="text-muted">Total {{ $items->total() }} records.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('admin.includes.footer')
