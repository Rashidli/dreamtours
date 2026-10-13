@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Site copy</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Site copy</li>
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
                                <h4 class="card-title mb-0">Site copy</h4>
                                <small class="text-muted">Button, heading and label text used across the site.</small>
                            </div>

                            <form class="row g-2 mb-3" method="get">
                                <div class="col-md-9">
                                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search text">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary">Search</button>
                                    <a class="btn btn-light" href="{{ route('words.index') }}">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:70px">#</th>
                                            <th>Text</th>
                                            <th style="width:110px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($words as $key => $word)
                                        <tr>
                                            <td>{{ $words->firstItem() + $key }}</td>
                                            <td>{{ $word->title }}</td>
                                            <td>
                                                @can('edit-words')
                                                    <a href="{{ route('words.edit', $word) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted">No text found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">{{ $words->links('admin.vendor.pagination.bootstrap-5') }}</div>
                            <small class="text-muted">Total {{ $words->total() }} items.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('admin.includes.footer')
