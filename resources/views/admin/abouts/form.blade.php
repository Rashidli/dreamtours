@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit about content</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('abouts.index')}}">About</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')<form method="post" enctype="multipart/form-data" action="{{$about->exists?route('abouts.update',$about):route('abouts.store')}}">@csrf @if($about->exists)@method('PUT')@endif<div class="card"><div class="card-body"><h4>{{$about->exists?'Edit about content':'Add about content'}}</h4>@include('admin.includes.validation_errors')<div class="row"><div class="col-md-4 mb-3"><label>Unique key</label><input class="form-control" name="key" value="{{old('key',$about->key?:'main')}}" required list="about-keys"><datalist id="about-keys"><option value="main">Our story</option><option value="vision">Vision</option><option value="mission">Mission</option><option value="founder">Founder</option></datalist><small class="text-muted">main = Our story · vision · mission · founder (title: name &amp; role, image: photo)</small></div><div class="col-md-8 mb-3"><label>Title</label><input class="form-control" name="en[title]" value="{{old('en.title',$about->translate('en')?->title)}}" required></div></div><div class="mb-3"><label>Description</label><textarea class="js-editor"  rows="7" name="en[description]">{{old('en.description',$about->translate('en')?->description)}}</textarea></div><div class="row"><div class="col-md-6"><label>Image upload</label><input class="form-control" type="file" name="image" accept="image/*"></div><div class="col-md-6"><label>Or image URL/path</label><input class="form-control" name="image_path" value="{{old('image_path',$about->image)}}"></div></div><button class="btn btn-primary mt-3"><i class="ri-save-line align-middle me-1"></i> Save</button> <a class="btn btn-light mt-3" href="{{route('abouts.index')}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back</a></div></div></form></div></div></div>@include('admin.includes.footer')
