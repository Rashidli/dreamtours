@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Social links</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Social links</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">
@if(session('message'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="d-flex justify-content-between"><h4 class="card-title mb-3">Links</h4>@can('create-socials')<a class="btn btn-primary" href="{{route('socials.create')}}"><i class="ri-add-line align-middle me-1"></i> New</a>@endcan</div><form class="row g-2 my-3"><div class="col-md-7"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Search platform"></div><div class="col-md-3"><select class="form-select" name="status"><option value="">All</option><option value="1" @selected(request('status')==='1')>Active</option><option value="0" @selected(request('status')==='0')>Inactive</option></select></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form><table class="table"><thead><tr><th>Name</th><th>Icon</th><th>Link</th><th>Status</th><th>Actions</th></tr></thead><tbody>@foreach($socials as $social)<tr><td>{{$social->title}}</td><td>{{$social->icon}}</td><td>{{$social->link}}</td><td>{{$social->is_active?'Active':'Inactive'}}</td><td><a class="btn btn-sm btn-primary" title="Edit" href="{{route('socials.edit',$social)}}"><i class="ri-pencil-line"></i></a> <form class="d-inline" method="post" action="{{route('socials.destroy',$social)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form></td></tr>@endforeach</tbody></table>{{$socials->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
