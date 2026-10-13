@include('admin.includes.header')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Add role</h4>
                        <a href="{{route('roles.index')}}" class="btn btn-sm btn-light">Back to roles</a>
                    </div>
                </div>
            </div>

            <form action="{{route('roles.store')}}" method="post">
                @csrf

                <div class="row">
                    <div class="col-lg-6 col-xl-4">
                        <div class="card">
                            <div class="card-body">
                                <label for="role-name" class="form-label">Role name</label>
                                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                                       id="role-name" value="{{old('name')}}" placeholder="e.g. content-editor">
                                @error('name') <div class="invalid-feedback">{{$message}}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Permissions</h5>

                @include('admin.roles._permissions', ['checked' => old('permission', [])])

                <div class="row">
                    <div class="col-12 py-3">
                        <button class="btn btn-primary">Save</button>
                        <a href="{{route('roles.index')}}" class="btn btn-light">Cancel</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@include('admin.includes.footer')
