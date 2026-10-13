@include('admin.includes.header')

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Permissions</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Permissions</li>
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
                                    <div class="alert alert-success">{{session('message')}}</div>
                                @endif
                                <h4 class="card-title mb-3">Permissions</h4>
                                <a href="{{route('permissions.create')}}" class="btn btn-primary">+</a>
                                <form class="row g-2 my-3">
                                    <div class="col-md-6">
                                        <input class="form-control" name="q" value="{{request('q')}}" placeholder="Search permission name">
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select" name="group">
                                            <option value="">All groups</option>
                                            @foreach(\App\Support\PermissionGroups::sectionOrder() as $section)
                                                <option value="{{$section}}" @selected(request('group')===$section)>{{$section}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-outline-primary w-100">Filter</button>
                                    </div>
                                </form>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">

                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Group</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($permissions as $key => $permission)
                                            <tr>

                                                <th scope="row">{{$permissions->firstItem()+$key}}</th>
                                                <td>{{$permission->name}}</td>
                                                <td>{{$permission->group_name}}</td>
                                                <td><a href="{{route('permissions.edit',$permission->id)}}" class="btn btn-primary" style="margin-right: 15px" >Edit</a>
                                                    <form action="{{route('permissions.destroy', $permission->id)}}" method="post" style="display: inline-block">
                                                        {{ method_field('DELETE') }}
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>



                                        @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">{{$permissions->links('admin.vendor.pagination.bootstrap-5')}}</div>
                                <small class="text-muted">Total {{$permissions->total()}} permissions.</small>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>



@include('admin.includes.footer')
