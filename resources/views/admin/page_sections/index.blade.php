@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Page sections</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Page sections</li>
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
                                <h4 class="card-title mb-0">Sections</h4>
                                <small class="text-muted">Sections are wired into the code; only their content is editable here.</small>
                            </div>

                            <form class="row g-2 mb-3" method="get">
                                <div class="col-md-4">
                                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search title or key">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="page_id">
                                        <option value="">All pages</option>
                                        @foreach($pages as $page)
                                            <option value="{{ $page->id }}" @selected(request('page_id') == $page->id)>{{ $page->title }}</option>
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
                                    <a class="btn btn-light" href="{{ route('page-sections.index') }}">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Page</th>
                                            <th>Section</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($sections as $key => $section)
                                        <tr>
                                            <td>{{ $sections->firstItem() + $key }}</td>
                                            <td>{{ $section->page?->title }}</td>
                                            <td><code>{{ $section->key }}</code></td>
                                            <td>{{ $section->title ?: '—' }}</td>
                                            <td>
                                                @if($section->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('edit-page-sections')
                                                    <a href="{{ route('page-sections.edit', $section) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">No sections found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">{{ $sections->links('admin.vendor.pagination.bootstrap-5') }}</div>
                            <small class="text-muted">Total {{ $sections->total() }} sections.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('admin.includes.footer')
