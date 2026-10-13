@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Team, partners & stats</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Team & partners</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">@if(session('message'))<div class="alert alert-success">{{session('message')}}</div>@endif<div class="d-flex justify-content-between"><h4>Team, partners & stats</h4><a class="btn btn-primary" href="{{route('company-content.create')}}"><i class="ri-add-line align-middle me-1"></i> New</a></div><form class="row g-2 my-3"><div class="col-md-5"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Search"></div><div class="col-md-3"><select class="form-select" name="type"><option value="">All types</option>@foreach($types as $type)<option @selected(request('type')===$type)>{{$type}}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="status"><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form><table class="table"><thead><tr><th>Title</th><th>Type</th><th>Value</th><th>Status</th><th>Actions</th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{$item->title}}</td><td>{{$item->type}}</td><td>{{$item->value}}</td><td>{{$item->is_active?'Active':'Inactive'}}</td><td><a class="btn btn-sm btn-primary" title="Edit" href="{{route('company-content.edit',$item)}}"><i class="ri-pencil-line"></i></a><form class="d-inline" method="post" action="{{route('company-content.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form></td></tr>@endforeach</tbody></table>{{$items->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
