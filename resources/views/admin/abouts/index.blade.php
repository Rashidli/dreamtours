@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">About content</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">About</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">
@if(session('message'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="d-flex justify-content-between"><h4 class="card-title mb-3">Content</h4><a class="btn btn-primary" href="{{route('abouts.create')}}"><i class="ri-add-line align-middle me-1"></i> New</a></div><form class="row g-2 my-3"><div class="col-md-9"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Search key or title"></div><div class="col-md-3"><button class="btn btn-outline-primary">Filter</button></div></form><table class="table"><thead><tr><th>Key</th><th>Title</th><th>Image</th><th>Actions</th></tr></thead><tbody>@foreach($abouts as $about)<tr><td>{{$about->key}}</td><td>{{$about->title}}</td><td>{{$about->image}}</td><td><a class="btn btn-sm btn-primary" title="Edit" href="{{route('abouts.edit',$about)}}"><i class="ri-pencil-line"></i></a> <form class="d-inline" method="post" action="{{route('abouts.destroy',$about)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form></td></tr>@endforeach</tbody></table>{{$abouts->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
