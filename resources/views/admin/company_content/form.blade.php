@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit content</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('company-content.index')}}">Team & partners</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<form method="post" enctype="multipart/form-data" action="{{$item->exists?route('company-content.update',$item):route('company-content.store')}}">@csrf @if($item->exists)@method('PUT')@endif<div class="card"><div class="card-body"><h4 class="card-title mb-4">Details</h4>@include('admin.includes.validation_errors')<div class="row"><div class="col-md-4 mb-3"><label>Type</label><select class="form-select" name="type">@foreach($types as $type)<option @selected(old('type',$item->type)===$type)>{{$type}}</option>@endforeach</select></div><div class="col-md-8 mb-3"><label>Title</label><input class="form-control" name="en[title]" value="{{old('en.title',$item->translate('en')?->title)}}" required></div></div><div class="mb-3"><label>Position / subtitle</label><input class="form-control" name="en[subtitle]" value="{{old('en.subtitle',$item->translate('en')?->subtitle)}}"></div><div class="mb-3"><label>Description</label><textarea class="form-control" name="en[description]">{{old('en.description',$item->translate('en')?->description)}}</textarea></div><div class="row">@include('admin.includes.media_field',['field'=>'image','label'=>'Image / photo','cols'=>4,'current'=>$item->getRawOriginal('image'),'preview'=>$item->image])<div class="col-md-3"><label>Link</label><input class="form-control" name="url" value="{{old('url',$item->url)}}"></div><div class="col-md-2"><label>Value</label><input class="form-control" name="value" value="{{old('value',$item->value)}}"></div><div class="col-md-2 pt-4"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$item->exists?$item->is_active:true))> Active</label></div></div><button class="btn btn-primary mt-3"><i class="ri-save-line align-middle me-1"></i> Save</button></div></div></form></div></div></div>@include('admin.includes.footer')
