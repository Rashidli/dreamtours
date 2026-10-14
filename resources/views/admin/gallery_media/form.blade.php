@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit media</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('domain-content.index','gallery-albums')}}">Gallery albums</a></li>
                                <li class="breadcrumb-item active">Media</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')<form method="post" enctype="multipart/form-data" action="{{route('gallery-media.update',[$album,$medium])}}">@csrf @method('PUT')<div class="card"><div class="card-body"><h4 class="card-title mb-4">Media</h4>@include('admin.includes.validation_errors')
<div class="row"><div class="col-md-3 mb-3"><label>Type</label><select class="form-select" name="type"><option @selected($medium->type==='image')>image</option><option @selected($medium->type==='video')>video</option></select></div><div class="col-md-4 mb-3"><label>Upload photo or video</label><input type="file" class="form-control" name="file" accept="image/*,video/mp4,video/webm,video/quicktime"></div><div class="col-md-3 mb-3"><label>…or a link</label><input class="form-control" name="path" value="{{old('path',$medium->getRawOriginal('path'))}}">@if($medium->type==='image')<a href="{{$medium->path}}" target="_blank" rel="noopener"><img src="{{$medium->path}}" alt="" class="mt-2" style="height:64px;border-radius:6px"></a>@endif</div></div>
<div class="row">@include('admin.includes.media_field',['field'=>'thumbnail','label'=>'Thumbnail','cols'=>6,'current'=>$medium->getRawOriginal('thumbnail'),'preview'=>$medium->thumbnail])</div><div class="mb-3"><label>Alt text</label><input class="form-control" name="en[alt_text]" value="{{old('en.alt_text',$medium->translate('en')?->alt_text)}}"></div><div class="mb-3"><label>Title</label><input class="form-control" name="en[caption]" value="{{old('en.caption',$medium->translate('en')?->caption)}}"></div><div class="mb-3" style="max-width:200px"><label>Display order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{old('sort_order',$medium->sort_order??0)}}"></div>
<button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save</button> <a class="btn btn-light" href="{{route('gallery-media.index',$album)}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back</a></div></div></form></div></div></div>@include('admin.includes.footer')
