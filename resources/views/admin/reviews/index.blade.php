@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Reviews</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Reviews</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">
@if(session('message'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="d-flex justify-content-between"><h4 class="card-title mb-3">Reviews @if($pendingCount)<a href="{{route('reviews.index',['status'=>'pending'])}}" class="badge bg-warning text-dark ms-2">{{$pendingCount}} waiting for approval</a>@endif</h4>@can('create-reviews')<a class="btn btn-primary" href="{{route('reviews.create')}}"><i class="ri-add-line align-middle me-1"></i> New</a>@endcan</div>
<p class="text-muted small">Reviews sent from a tour page arrive as <b>Pending</b> and are not visible on the website until you approve them. Approved reviews show on their tour page; tick “Feature on home &amp; About” in a review to show it there as well.</p>
<form class="row g-2 my-3"><div class="col-md-3"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Search name or text"></div><div class="col-md-3"><select class="form-select" name="tour_id"><option value="">All tours</option>@foreach($tours as $tour)<option value="{{$tour->id}}" @selected((string)$tour->id===request('tour_id'))>{{$tour->name}}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="rating"><option value="">All ratings</option>@foreach(range(1,5) as $rating)<option value="{{$rating}}" @selected((string)$rating===request('rating'))>{{$rating}}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(\App\Models\Review::STATUSES as $key=>$label)<option value="{{$key}}" @selected(request('status')===$key)>{{$label}}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-light" href="{{route('reviews.index')}}">Clear</a></div></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Reviewer</th><th>Tour</th><th style="width:40%">Review</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($reviews as $review)
 @php($badge=['pending'=>'warning','approved'=>'success','rejected'=>'secondary'][$review->status]??'secondary')
 <tr>
  <td><b>{{$review->name}}</b><br><small class="text-muted">{{$review->position}}@if($review->email)<br>{{$review->email}}@endif</small></td>
  <td>{{$review->tour?->name?:'General'}}@if($review->travel_date)<br><small class="text-muted">Travelled {{$review->travel_date->format('M Y')}}</small>@endif</td>
  <td><small>{{str($review->content)->limit(220)}}</small></td>
  <td class="text-nowrap">{{str_repeat('★',$review->rating)}}<span class="text-muted">{{str_repeat('★',5-$review->rating)}}</span></td>
  <td><span class="badge bg-{{$badge}}">{{\App\Models\Review::STATUSES[$review->status]??$review->status}}</span>@if($review->is_active)<br><small class="text-muted">Featured</small>@endif</td>
  <td class="text-nowrap">
   @can('edit-reviews')
    @if($review->status!=='approved')<form class="d-inline" method="post" action="{{route('reviews.moderate',$review)}}">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-success" title="Approve"><i class="ri-check-line"></i></button></form>@endif
    @if($review->status!=='rejected')<form class="d-inline" method="post" action="{{route('reviews.moderate',$review)}}">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-outline-secondary" title="Reject"><i class="ri-close-line"></i></button></form>@endif
    <a class="btn btn-sm btn-primary" title="Edit" href="{{route('reviews.edit',$review)}}"><i class="ri-pencil-line"></i></a>
   @endcan
   @can('delete-reviews')<form class="d-inline" method="post" action="{{route('reviews.destroy',$review)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form>@endcan
  </td>
 </tr>
@empty<tr><td colspan="6">No reviews.</td></tr>@endforelse
</tbody></table></div>{{$reviews->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
