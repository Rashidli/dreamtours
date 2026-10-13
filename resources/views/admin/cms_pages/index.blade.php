@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Pages & SEO</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Pages & SEO</li>
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
                                <h4 class="card-title mb-0">Pages</h4>
                                <small class="text-muted">Pages are wired into the code; only their content and SEO are editable here.</small>
                            </div>

                            <form class="row g-2 mb-3" method="get">
                                <div class="col-md-6">
                                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search title or key">
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
                                    <a class="btn btn-light" href="{{ route('cms-pages.index') }}">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Key</th>
                                            <th>Section</th>
                                            <th>SEO</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($pages as $key => $page)
                                        <tr>
                                            <td>{{ $pages->firstItem() + $key }}</td>
                                            <td>{{ $page->title }}</td>
                                            <td><code>{{ $page->key }}</code></td>
                                            <td>{{ $page->sections_count }}</td>
                                            <td>
                                                @if($page->meta_title && $page->meta_description)
                                                    <span class="badge bg-success">Tam</span>
                                                @elseif($page->meta_title || $page->meta_description)
                                                    <span class="badge bg-warning">Partial</span>
                                                @else
                                                    <span class="badge bg-danger">Empty</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($page->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('edit-pages')
                                                    <a href="{{ route('cms-pages.edit', $page) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">No pages found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">{{ $pages->links('admin.vendor.pagination.bootstrap-5') }}</div>
                            <small class="text-muted">Total {{ $pages->total() }} pages.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('admin.includes.footer')
