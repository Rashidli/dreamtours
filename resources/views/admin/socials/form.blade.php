@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Social link</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('socials.index')}}">Social links</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<form method="post" action="{{$social->exists?route('socials.update',$social):route('socials.store')}}">@csrf @if($social->exists)@method('PUT')@endif<div class="card"><div class="card-body"><h4>{{$social->exists?'Edit social link':'Add social link'}}</h4>@include('admin.includes.validation_errors')<div class="row"><div class="col-md-4 mb-3"><label>Platform name</label><input class="form-control" name="title" value="{{old('title',$social->title)}}" required></div><div class="col-md-3 mb-3"><label>Icon key</label><input class="form-control" name="icon" value="{{old('icon',$social->icon)}}" placeholder="facebook"></div><div class="col-md-5 mb-3"><label>Link</label><input class="form-control" name="link" value="{{old('link',$social->link)}}" required></div></div><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$social->exists?$social->is_active:true))> Active</label><div class="mt-3"><button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save</button> <a class="btn btn-light" href="{{route('socials.index')}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back</a></div></div></div></form></div></div></div>@include('admin.includes.footer')
