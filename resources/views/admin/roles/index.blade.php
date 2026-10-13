@include('admin.includes.header')

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Roles</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Roles</li>
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
                                <h4 class="card-title">Roles</h4>
                                <a href="{{route('roles.create')}}" class="btn btn-primary">+</a>
                                <br>
                                <br>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">

                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($roles as $key => $role)
                                            <tr>
                                                <td scope="row">{{$roles->firstItem()+$key}}</td>
                                                <td>{{$role->name}}</td>
                                                <td>
                                                    @if($role->name === 'superadmin')
                                                        <span class="badge bg-secondary">System role — cannot be changed</span>
                                                    @else
                                                        <a href="{{route('roles.edit',$role->id)}}" class="btn btn-primary" style="margin-right: 15px" >Edit</a>
                                                        <form action="{{route('roles.destroy', $role->id)}}" method="post" style="display: inline-block">
                                                            {{ method_field('DELETE') }}
                                                            @csrf
                                                            <button  type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">{{$roles->links('admin.vendor.pagination.bootstrap-5')}}</div>
                                <small class="text-muted">Total {{$roles->total()}} roles.</small>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>



@include('admin.includes.footer')

<script>

</script>
