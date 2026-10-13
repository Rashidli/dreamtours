@include('admin.includes.header')
@php($labels = ['categories' => 'Tour categories', 'destinations' => 'Destinations', 'transportations' => 'Transportation', 'pickup-points' => 'Pickup points'])
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
                                @can('create-'.$permission)
                                    <a href="{{ route('tour-catalogue.create', $resource) }}" class="btn btn-primary">
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
                                    <a class="btn btn-light" href="{{ route('tour-catalogue.index', $resource) }}">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            @if($resource !== 'pickup-points')<th>Image</th>@endif
                                            <th>Name</th>
                                            <th>{{ $resource === 'pickup-points' ? 'Address' : 'Type' }}</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($items as $key => $item)
                                        <tr>
                                            <td>{{ $items->firstItem() + $key }}</td>
                                            @if($resource !== 'pickup-points')
                                                <td>
                                                    @if($item->image)
                                                        <img src="{{ $item->image }}" alt="" width="60" height="40" style="object-fit: cover; border-radius: 4px;">
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $resource === 'pickup-points' ? $item->address : $item->type }}</td>
                                            <td>
                                                @if($item->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('edit-'.$permission)
                                                    <a href="{{ route('tour-catalogue.edit', [$resource, $item]) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endcan
                                                @can('delete-'.$permission)
                                                    <form action="{{ route('tour-catalogue.destroy', [$resource, $item]) }}" method="post" style="display: inline-block">
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
