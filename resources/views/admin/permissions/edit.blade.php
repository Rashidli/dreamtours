@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit permission</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('permissions.index')}}">Permissions</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="card">
                        <div class="card-body">
                            @if(session('message'))
                                <div class="alert alert-success">{{session('message')}}</div>
                            @endif
                            <h4 class="card-title">Permission Edit</h4>
                            <form action="{{route('permissions.update',$permission->id)}}" method="post">
                                @csrf
                                {{ method_field('PUT') }}
                                <div class="mb-3">
                                    <label for="example-email-input" class="col-form-label">Name</label>
                                    <input class="form-control" value="{{$permission->name}}" type="text" name="name"
                                           id="example-email-input">
                                    @if($errors->first('name'))
                                        <small class="form-text text-danger">{{$errors->first('name')}}</small>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label for="example-email-input" class="col-form-label">Group name</label>
                                    <input class="form-control" value="{{old('group_name',$permission->group_name)}}" type="text"
                                           name="group_name" id="example-email-input" list="permissionGroups">
                                    <datalist id="permissionGroups">
                                        @foreach(\App\Support\PermissionGroups::sectionOrder() as $section)
                                            <option value="{{ $section }}">
                                        @endforeach
                                    </datalist>
                                    @if($errors->first('group_name'))
                                        <small class="form-text text-danger">{{$errors->first('group_name')}}</small>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-primary">Submit</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div>
        </div>
    </div>
</div>
@include('admin.includes.footer')
