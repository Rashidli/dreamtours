@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Bookings & requests</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Bookings</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body">@if(session('message'))<div class="alert alert-success">{{session('message')}}</div>@endif
<h4 class="card-title mb-3">Requests</h4>
<form class="row g-2 my-3">
 <div class="col-md-3"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Reference, customer, email or phone"></div>
 <div class="col-md-2"><select class="form-select" name="type"><option value="">All types</option>@foreach(['tour','cruise','private_tour','visa','invoice','callback','contact'] as $x)<option value="{{$x}}" @selected(request('type')===$x)>{{str($x)->replace('_',' ')->title()}}</option>@endforeach</select></div>
 <div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['new','contacted','confirmed','cancelled','completed'] as $x)<option @selected(request('status')===$x)>{{$x}}</option>@endforeach</select></div>
 <div class="col-md-3"><select class="form-select" name="payment_status"><option value="">All payment statuses</option>@foreach(\App\Models\Booking::PAYMENT_STATUSES as $key=>$label)<option value="{{$key}}" @selected(request('payment_status')===$key)>{{$label}}</option>@endforeach</select></div>
 <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-light" href="{{route('bookings.index')}}">Clear</a></div>
</form>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Reference</th><th>Type</th><th>Customer</th><th>Tour / departure</th><th>People</th><th>Total / paid</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($bookings as $b)
 @php($paymentClass=['unpaid'=>'secondary','proof_submitted'=>'warning','deposit_paid'=>'info','paid'=>'success','refunded'=>'dark'][$b->payment_status]??'secondary')
 <tr>
  <td><strong>{{$b->reference}}</strong><br><small class="text-muted">{{$b->created_at?->format('Y-m-d H:i')}}</small></td>
  <td>{{str($b->type)->replace('_',' ')->title()}}</td>
  <td>{{$b->first_name}} {{$b->last_name}}<br><small>{{$b->phone}} · {{$b->email}}</small></td>
  <td>{{$b->tour?->name?:'—'}}@if($b->departure)<br><small>{{$b->departure->departs_at->format('M j, Y')}}</small>@endif</td>
  <td>{{$b->participant_count}}</td>
  <td>@if($b->total_amount){{money($b->total_amount,$b->currency)}}<br><small>{{money($b->amount_paid,$b->currency)}} paid</small>@else — @endif</td>
  <td><span class="badge bg-{{$paymentClass}}">{{\App\Models\Booking::PAYMENT_STATUSES[$b->payment_status]??$b->payment_status}}</span></td>
  <td>{{$b->status}}</td>
  <td class="text-nowrap"><a class="btn btn-sm btn-info" href="{{route('bookings.show',$b)}}"><i class="ri-eye-line"></i></a> @can('delete-bookings')<form class="d-inline" method="post" action="{{route('bookings.destroy',$b)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete request?')"><i class="ri-delete-bin-line"></i></button></form>@endcan</td>
 </tr>
@empty<tr><td colspan="9">No requests.</td></tr>@endforelse
</tbody></table></div>{{$bookings->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
