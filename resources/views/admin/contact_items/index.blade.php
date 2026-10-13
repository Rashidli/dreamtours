@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Contact information</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Contact information</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">
@if(session('message'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="d-flex justify-content-between"><h4 class="card-title mb-3">Items</h4>@can('create-contact_items')<a class="btn btn-primary" href="{{route('contact_items.create')}}"><i class="ri-add-line align-middle me-1"></i> New</a>@endcan</div><form class="row g-2 my-3"><div class="col-md-7"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Search title or value"></div><div class="col-md-3"><select class="form-select" name="status"><option value="">All</option><option value="1" @selected(request('status')==='1')>Active</option><option value="0" @selected(request('status')==='0')>Inactive</option></select></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form><table class="table"><thead><tr><th>Title</th><th>Value</th><th>Icon</th><th>Status</th><th>Actions</th></tr></thead><tbody>@foreach($contact_items as $item)<tr><td>{{$item->title}}</td><td>{{$item->value}}</td><td>{{$item->icon}}</td><td>{{$item->is_active?'Active':'Inactive'}}</td><td><a class="btn btn-sm btn-primary" title="Edit" href="{{route('contact_items.edit',$item)}}"><i class="ri-pencil-line"></i></a> <form class="d-inline" method="post" action="{{route('contact_items.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form></td></tr>@endforeach</tbody></table>{{$contact_items->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
