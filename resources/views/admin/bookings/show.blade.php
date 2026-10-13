@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Request detail</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('bookings.index')}}">Bookings</a></li>
                                <li class="breadcrumb-item active">Detal</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@php($money=fn($v)=>$v!==null?money($v,$booking->currency):'—')
@if(session('message'))<div class="alert alert-success">{{session('message')}}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
<div class="row">
 <div class="col-lg-8">
  <div class="card"><div class="card-body">
   <div class="d-flex justify-content-between align-items-start"><div><h4 class="mb-1">{{$booking->reference}}</h4><p class="text-muted mb-0">{{str($booking->type)->replace('_',' ')->title()}} · submitted {{$booking->created_at?->format('Y-m-d H:i')}}</p></div><a class="btn btn-light" href="{{route('bookings.index')}}"><i class="ri-arrow-left-line"></i> Back</a></div>
   <hr>
   <div class="row">
    <div class="col-md-6">
     <p><b>Tour:</b> {{$booking->tour?->name?:'—'}}</p>
     <p><b>Departure:</b> {{$booking->departure?->departs_at?->format('D, M j, Y g:i A')?:'—'}}</p>
     <p><b>Pickup:</b> {{$booking->pickupPoint?->name?:'—'}}@if($booking->pickupPoint) <small class="text-muted">({{$booking->pickupPoint->address}})</small>@endif</p>
     <p><b>Passengers:</b> {{$booking->participant_count}}</p>
     @if($booking->roommate_request)<p><b>Roommate request:</b> {{$booking->roommate_request}}</p>@endif
    </div>
    <div class="col-md-6">
     <p><b>Main passenger:</b> {{$booking->first_name}} {{$booking->last_name}}@if($booking->date_of_birth) <small class="text-muted">(DOB {{$booking->date_of_birth->format('Y-m-d')}})</small>@endif</p>
     <p><b>Phone:</b> <a href="tel:{{preg_replace('/[^\d+]/','',$booking->phone)}}">{{$booking->phone}}</a></p>
     <p><b>Email:</b> <a href="mailto:{{$booking->email}}">{{$booking->email}}</a></p>
     <p><b>Emergency contact:</b> {{$booking->emergency_contact_name?:'—'}} {{$booking->emergency_contact_phone}}</p>
     @foreach(['whatsapp'=>'WhatsApp','address'=>'Address','age'=>'Age','mobility_information'=>'Mobility','source'=>'Source'] as $field=>$label)@if($booking->{$field})<p><b>{{$label}}:</b> {{$booking->{$field} }}</p>@endif @endforeach
    </div>
   </div>
   <div class="row">
    @foreach(['meal_selection'=>'Meal','special_requests'=>'Special requests','food_allergies'=>'Food allergies','promo_code'=>'Promo code','comments'=>'Comments'] as $field=>$label)
     @if($booking->{$field})<div class="col-md-6"><p><b>{{$label}}:</b> {!! nl2br(e($booking->{$field})) !!}</p></div>@endif
    @endforeach
   </div>
  </div></div>

  <div class="card"><div class="card-body">
   <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Passengers & seats</h5>
    @if($booking->seat_notice_sent_at)<span class="badge bg-success">Seat numbers sent {{$booking->seat_notice_sent_at->format('M j, H:i')}}</span>@endif
   </div>
   <form method="post" action="{{route('bookings.seats',$booking)}}">@csrf @method('PUT')
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>#</th><th>Name</th><th>Fare</th><th>Room</th><th>DOB</th><th style="width:120px">Seat</th></tr></thead><tbody>
    @forelse($booking->passengers as $i=>$p)
     <tr><td>{{$i+1}}</td><td>{{$p->first_name}} {{$p->last_name}}@if($p->notes)<br><small>{{$p->notes}}</small>@endif</td><td>{{\App\Models\TourDeparturePrice::CODES[$p->price_code][0]??($p->category?:'—')}}@if($p->price!==null)<br><small>{{$money($p->price)}}</small>@endif</td><td>{{$p->room_number?:'—'}}</td><td>{{$p->date_of_birth?->format('Y-m-d')?:'—'}}</td>
      <td>@can('edit-bookings')<input class="form-control form-control-sm" name="seats[{{$p->id}}]" value="{{old('seats.'.$p->id,$p->seat_number)}}" placeholder="e.g. 12">@else{{$p->seat_number?:'—'}}@endcan</td></tr>
    @empty<tr><td colspan="6">No passenger details.</td></tr>@endforelse
    </tbody></table></div>
    @can('edit-bookings')@if($booking->passengers->isNotEmpty())<button class="btn btn-primary"><i class="ri-save-line"></i> Save seat numbers</button>@endif @endcan
   </form>
   @can('edit-bookings')@if($booking->passengers->isNotEmpty())
    <form class="mt-3" method="post" action="{{route('bookings.seat-notice',$booking)}}">@csrf
     <button class="btn btn-success" @disabled($seatBlocker) onclick="return confirm('Email the seat numbers to {{$booking->email}}?')"><i class="ri-mail-send-line"></i> {{$booking->seat_notice_sent_at?'Resend':'Send'}} seat numbers to customer</button>
     <small class="text-muted d-block mt-1">{{$seatBlocker?:'Seat numbers are also sent automatically 2 days before departure.'}}</small>
    </form>
   @endif @endcan
  </div></div>
 </div>

 <div class="col-lg-4">
  <div class="card"><div class="card-body">
   <h5>Payment</h5>
   <table class="table table-sm"><tbody>
    <tr><td>Total</td><td class="text-end"><b>{{$money($booking->total_amount)}}</b></td></tr>
    <tr><td>Deposit due</td><td class="text-end">{{$money($booking->deposit_amount)}}</td></tr>
    <tr><td>Paid</td><td class="text-end">{{$money($booking->amount_paid)}}</td></tr>
    <tr><td>Balance</td><td class="text-end">{{$booking->total_amount!==null?$money($booking->balance_due):'—'}}</td></tr>
   </tbody></table>
   @if($booking->payment_proof)
    <a class="btn btn-outline-primary w-100 mb-2" target="_blank" href="{{route('bookings.payment-proof',$booking)}}"><i class="ri-file-image-line"></i> View payment screenshot</a>
   @endif
   @if($booking->policies_accepted_at)<p class="small text-muted mb-0">Policies accepted {{$booking->policies_accepted_at->format('Y-m-d H:i')}}</p>@endif
   @if($booking->confirmation_sent_at)<p class="small text-muted">Confirmation email sent {{$booking->confirmation_sent_at->format('Y-m-d H:i')}}</p>@endif
  </div></div>
  @can('edit-bookings')
  <div class="card"><div class="card-body">
   <h5>Update booking</h5>
   <form method="post" action="{{route('bookings.update',$booking)}}">@csrf @method('PUT')
    <div class="mb-2"><label class="form-label">Booking status</label><select class="form-select" name="status">@foreach(['new','contacted','confirmed','cancelled','completed'] as $s)<option @selected($booking->status===$s)>{{$s}}</option>@endforeach</select></div>
    <div class="mb-2"><label class="form-label">Payment status</label><select class="form-select" name="payment_status">@foreach(\App\Models\Booking::PAYMENT_STATUSES as $key=>$label)<option value="{{$key}}" @selected($booking->payment_status===$key)>{{$label}}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">Amount paid ({{$booking->currency?:'CAD'}})</label><input class="form-control" type="number" step="0.01" min="0" name="amount_paid" value="{{$booking->amount_paid}}"></div>
    <button class="btn btn-primary w-100">Save</button>
   </form>
  </div></div>
  @endcan
 </div>
</div>
</div></div></div>@include('admin.includes.footer')
