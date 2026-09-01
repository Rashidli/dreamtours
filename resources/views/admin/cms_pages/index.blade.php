@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="card"><div class="card-body">
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Website pages</h4>
            @can('create-pages')<a class="btn btn-primary" href="{{ route('cms-pages.create') }}">Add page</a>@endcan
        </div>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search title or key"></div>
            <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><option value="1" @selected(request('status')==='1')>Active</option><option value="0" @selected(request('status')==='0')>Inactive</option></select></div>
            <div class="col-md-3"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-light" href="{{ route('cms-pages.index') }}">Reset</a></div>
        </form>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Title</th><th>Key</th><th>Template</th><th>Sections</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse($pages as $page)<tr><td>{{ $page->title }}</td><td><code>{{ $page->key }}</code></td><td>{{ $page->template }}</td><td>{{ $page->sections()->count() }}</td><td>{{ $page->is_active ? 'Active' : 'Inactive' }}</td><td>
            @can('edit-pages')<a class="btn btn-sm btn-primary" href="{{ route('cms-pages.edit', $page) }}">Edit</a>@endcan
            @can('create-page-sections')<a class="btn btn-sm btn-info" href="{{ route('page-sections.create', ['page_id'=>$page->id]) }}">Add section</a>@endcan
            @can('delete-pages')<form class="d-inline" method="post" action="{{ route('cms-pages.destroy', $page) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this page and its sections?')">Delete</button></form>@endcan
        </td></tr>@empty<tr><td colspan="6" class="text-center">No pages found.</td></tr>@endforelse
        </tbody></table></div>{{ $pages->links('admin.vendor.pagination.bootstrap-5') }}
    </div></div>
</div></div></div>
@include('admin.includes.footer')
