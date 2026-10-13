@include('admin.includes.header')
@php($groups = ['header' => 'Header', 'footer_company' => 'Footer: Company', 'footer_support' => 'Footer: Support', 'footer_legal' => 'Footer: Legal'])
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Navigation & footer links</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Navigation & footer</li>
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
                                <h4 class="card-title mb-0">Links</h4>
                                @can('create-navigation')
                                    <a href="{{ route('navigation-items.create') }}" class="btn btn-primary">
                                        <i class="ri-add-line align-middle me-1"></i> New
                                    </a>
                                @endcan
                            </div>

                            <form class="row g-2 mb-3" method="get">
                                <div class="col-md-4">
                                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search title">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="location">
                                        <option value="">All groups</option>
                                        @foreach($groups as $value => $label)
                                            <option value="{{ $value }}" @selected(request('location')===$value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="status">
                                        <option value="">All statuses</option>
                                        <option value="1" @selected(request('status')==='1')>Active</option>
                                        <option value="0" @selected(request('status')==='0')>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary">Filter</button>
                                    <a class="btn btn-light" href="{{ route('navigation-items.index') }}">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Group</th>
                                            <th>Parent link</th>
                                            <th>URL</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($items as $key => $item)
                                        <tr>
                                            <td>{{ $items->firstItem() + $key }}</td>
                                            <td>{{ $item->title }}</td>
                                            <td>{{ $groups[$item->location] ?? $item->location }}</td>
                                            <td>{{ $item->parent?->title ?: '—' }}</td>
                                            <td><code>{{ $item->url }}</code></td>
                                            <td>
                                                @if($item->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('edit-navigation')
                                                    <a href="{{ route('navigation-items.edit', $item) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endcan
                                                @can('delete-navigation')
                                                    <form action="{{ route('navigation-items.destroy', $item) }}" method="post" style="display: inline-block">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                                                onclick="return confirm('Delete this link?')">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">No links found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">{{ $items->links('admin.vendor.pagination.bootstrap-5') }}</div>
                            <small class="text-muted">Total {{ $items->total() }} links.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('admin.includes.footer')
