@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Album media</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('domain-content.index','gallery-albums')}}">Gallery albums</a></li>
                                <li class="breadcrumb-item active">{{$album->title}}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between"><h4>{{$album->title}} — Media</h4><a class="btn btn-light" href="{{route('domain-content.edit',['gallery-albums',$album])}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back to album</a></div>
<form class="row g-2 my-3"><div class="col-md-6"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Search path or caption"></div><div class="col-md-3"><select class="form-select" name="type"><option value="">All types</option><option value="image" @selected(request('type')==='image')>Image</option><option value="video" @selected(request('type')==='video')>Video</option></select></div><div class="col-md-3"><button class="btn btn-outline-primary">Filter</button></div></form>
<table class="table"><thead><tr><th>Type</th><th>File</th><th>Title</th><th>Actions</th></tr></thead><tbody>@foreach($media as $medium)<tr><td>{{$medium->type}}</td><td>{{$medium->path}}</td><td>{{$medium->caption}}</td><td><a class="btn btn-sm btn-primary" title="Edit" href="{{route('gallery-media.edit',[$album,$medium])}}"><i class="ri-pencil-line"></i></a> <form class="d-inline" method="post" action="{{route('gallery-media.destroy',[$album,$medium])}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form></td></tr>@endforeach</tbody></table>{{$media->links('admin.vendor.pagination.bootstrap-5')}}
</div></div></div></div></div>@include('admin.includes.footer')
