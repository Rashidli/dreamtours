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
<h4 class="card-title mb-3">Bookings & requests</h4>
<form class="row g-2 my-3">
 <div class="col-md-3"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Reference, customer, passenger, email or phone"></div>
 <div class="col-md-2"><select class="form-select" name="tour_id" onchange="this.form.tour_departure_id && (this.form.tour_departure_id.value='');this.form.submit()"><option value="">All trips</option>@foreach($tours as $t)<option value="{{$t->id}}" @selected((string)request('tour_id')===(string)$t->id)>{{$t->name}}</option>@endforeach</select></div>
 @if($departures->isNotEmpty())<div class="col-md-2"><select class="form-select" name="tour_departure_id"><option value="">All dates</option>@foreach($departures as $d)<option value="{{$d->id}}" @selected((string)request('tour_departure_id')===(string)$d->id)>{{$d->departs_at->format('M j, Y')}}</option>@endforeach</select></div>@endif
 <div class="col-md-2"><select class="form-select" name="pickup_point_id"><option value="">All pickup points</option>@foreach($pickups as $p)<option value="{{$p->id}}" @selected((string)request('pickup_point_id')===(string)$p->id)>{{$p->name}}</option>@endforeach</select></div>
 <div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(\App\Models\Booking::STATUSES as $key=>$label)<option value="{{$key}}" @selected(request('status')===$key)>{{$label}}</option>@endforeach</select></div>
 <div class="col-md-2"><select class="form-select" name="payment_status"><option value="">All payments</option>@foreach(\App\Models\Booking::PAYMENT_STATUSES as $key=>$label)<option value="{{$key}}" @selected(request('payment_status')===$key)>{{$label}}</option>@endforeach</select></div>
 <div class="col-md-2"><select class="form-select" name="type"><option value="">All types</option>@foreach(['tour','cruise','private_tour','invoice','callback','contact'] as $x)<option value="{{$x}}" @selected(request('type')===$x)>{{str($x)->replace('_',' ')->title()}}</option>@endforeach</select></div>
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
  <td><span class="badge bg-{{['new'=>'warning','payment_pending'=>'info','confirmed'=>'success','cancelled'=>'secondary','completed'=>'dark'][$b->status]??'light'}}">{{\App\Models\Booking::STATUSES[$b->status]??$b->status}}</span></td>
  <td class="text-nowrap"><a class="btn btn-sm btn-info" href="{{route('bookings.show',$b)}}"><i class="ri-eye-line"></i></a> @can('delete-bookings')<form class="d-inline" method="post" action="{{route('bookings.destroy',$b)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete request?')"><i class="ri-delete-bin-line"></i></button></form>@endcan</td>
 </tr>
@empty<tr><td colspan="9">No requests.</td></tr>@endforelse
</tbody></table></div>{{$bookings->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div></div></div>@include('admin.includes.footer')
