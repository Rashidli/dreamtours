@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Contact item</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('contact_items.index')}}">Contact information</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<form method="post" action="{{$contact_item->exists?route('contact_items.update',$contact_item):route('contact_items.store')}}">@csrf @if($contact_item->exists)@method('PUT')@endif<div class="card"><div class="card-body"><h4>{{$contact_item->exists?'Edit contact item':'Add contact item'}}</h4>@include('admin.includes.validation_errors')<div class="row"><div class="col-md-6 mb-3"><label>Title</label><input class="form-control" name="en[title]" value="{{old('en.title',$contact_item->translate('en')?->title)}}" required></div><div class="col-md-6 mb-3"><label>Value</label><input class="form-control" name="en[value]" value="{{old('en.value',$contact_item->translate('en')?->value)}}" required></div><div class="col-md-6 mb-3"><label>Link</label><input class="form-control" name="link" value="{{old('link',$contact_item->link)}}"></div><div class="col-md-3 mb-3"><label>Icon key</label><input class="form-control" name="icon" value="{{old('icon',$contact_item->icon)}}"></div></div><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$contact_item->exists?$contact_item->is_active:true))> Active</label><div class="mt-3"><button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save</button> <a class="btn btn-light" href="{{route('contact_items.index')}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back</a></div></div></div></form></div></div></div>@include('admin.includes.footer')
