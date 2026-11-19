@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-bookings.css') }}?v={{ @filemtime(public_path('assets/css/admin-bookings.css')) }}">
@php
  $statusColors = ['new' => 'warning', 'contacted' => 'info', 'payment_pending' => 'info', 'confirmed' => 'success', 'cancelled' => 'secondary', 'completed' => 'secondary'];
  $paymentColors = ['unpaid' => 'danger', 'proof_submitted' => 'warning', 'partially_paid' => 'info', 'deposit_paid' => 'info', 'paid' => 'success', 'complimentary' => 'success', 'refunded' => 'secondary'];
  $types = ['tour', 'cruise', 'private_tour', 'invoice', 'callback', 'contact'];
  $typeLabel = fn ($t) => str($t)->replace('_', ' ')->ucfirst();
  $canMoney = auth()->user()->can('view-financial-info');
  // Active filters (other than the status tab) as removable chips.
  $without = fn (...$keys) => route('bookings.index', collect(request()->query())->except(array_merge($keys, ['page']))->filter(fn ($v) => $v !== null && $v !== '')->all());
  $chips = collect();
  if (request()->filled('q')) $chips->push(['Search: “'.request('q').'”', $without('q')]);
  if (request()->filled('tour_id')) $chips->push(['Trip: '.($tours->firstWhere('id', (int) request('tour_id'))?->name ?? '#'.request('tour_id')), $without('tour_id', 'tour_departure_id')]);
  if (request()->filled('tour_departure_id')) $chips->push(['Date: '.($departures->firstWhere('id', (int) request('tour_departure_id'))?->departs_at?->format('M j, Y') ?? '#'.request('tour_departure_id')), $without('tour_departure_id')]);
  if (request()->filled('pickup_point_id')) $chips->push(['Pickup: '.($pickups->firstWhere('id', (int) request('pickup_point_id'))?->name ?? '#'.request('pickup_point_id')), $without('pickup_point_id')]);
  if (request()->filled('payment_status')) $chips->push(['Payment: '.(\App\Models\Booking::PAYMENT_STATUSES[request('payment_status')] ?? request('payment_status')), $without('payment_status')]);
  if (request()->filled('type')) $chips->push(['Type: '.$typeLabel(request('type')), $without('type')]);
  $filtered = $chips->isNotEmpty() || request()->filled('status');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4 class="mb-0">Bookings & requests</h4><span class="text-muted">{{ $bookings->total() }} {{ Str::plural('booking', $bookings->total()) }}{{ $filtered ? ' match the filters' : '' }}</span></div>
                        @can('create-bookings')<a class="btn btn-primary" href="{{ route('bookings.create') }}"><i class="ri-add-line"></i> New booking</a>@endcan
                    </div>
                </div>
            </div>
<div class="card tl-card"><div class="card-body p-0">@if(session('message'))<div class="alert alert-success m-3 mb-0">{{session('message')}}</div>@endif
<form id="booking-filters" method="get" action="{{ route('bookings.index') }}">
 <div class="tl-tabs-bar">
  <div class="tl-tabs" role="radiogroup" aria-label="Booking status"><input type="radio" class="btn-check" name="status" id="status-all" value="" @checked(!request()->filled('status')) onchange="this.form.submit()"><label class="tl-tab" for="status-all">All</label>@foreach(\App\Models\Booking::STATUSES as $key=>$label)<input type="radio" class="btn-check" name="status" id="status-{{ $key }}" value="{{ $key }}" @checked(request('status')===$key) onchange="this.form.submit()"><label class="tl-tab" for="status-{{ $key }}">{{ $label }}</label>@endforeach</div>
 </div>
 <div class="tl-search-bar">
  <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{request('q')}}" placeholder="Reference, customer, passenger, email or phone" aria-label="Search bookings">@if(request()->filled('q'))<a href="{{ $without('q') }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
  <button type="submit" class="visually-hidden">Search</button>
  <span class="tl-hint d-none d-md-inline">Press Enter to search</span>
 </div>
 <div class="bk-filter-bar">
  <span class="bk-filter-label"><i class="ri-filter-3-line" aria-hidden="true"></i> Filter</span>
  <select class="form-select" name="tour_id" aria-label="Trip" onchange="this.form.tour_departure_id && (this.form.tour_departure_id.value='');this.form.submit()"><option value="">All trips</option>@foreach($tours as $t)<option value="{{$t->id}}" @selected((string)request('tour_id')===(string)$t->id)>{{$t->name}}</option>@endforeach</select>
  @if($departures->isNotEmpty())<select class="form-select" name="tour_departure_id" aria-label="Departure date" onchange="this.form.submit()"><option value="">All dates</option>@foreach($departures as $d)<option value="{{$d->id}}" @selected((string)request('tour_departure_id')===(string)$d->id)>{{$d->departs_at->format('M j, Y')}}</option>@endforeach</select>@endif
  <select class="form-select" name="pickup_point_id" aria-label="Pickup point" onchange="this.form.submit()"><option value="">All pickup points</option>@foreach($pickups as $p)<option value="{{$p->id}}" @selected((string)request('pickup_point_id')===(string)$p->id)>{{$p->name}}</option>@endforeach</select>
  <select class="form-select" name="payment_status" aria-label="Payment" onchange="this.form.submit()"><option value="">All payments</option>@foreach(\App\Models\Booking::PAYMENT_STATUSES as $key=>$label)<option value="{{$key}}" @selected(request('payment_status')===$key)>{{$label}}</option>@endforeach</select>
  <select class="form-select" name="type" aria-label="Type" onchange="this.form.submit()"><option value="">All types</option>@foreach($types as $x)<option value="{{$x}}" @selected(request('type')===$x)>{{ $typeLabel($x) }}</option>@endforeach</select>
  <div class="input-group bk-booked" style="width:auto"><span class="input-group-text">Booked</span><input class="form-control" type="date" name="booked_from" value="{{request('booked_from')}}" aria-label="Booked from" onchange="this.form.submit()"><input class="form-control" type="date" name="booked_to" value="{{request('booked_to')}}" aria-label="Booked to" onchange="this.form.submit()"></div>
  @can('export-bookings')<div class="btn-group ms-auto"><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'xlsx', 'page' => null]) }}" title="Download the bookings matching these filters"><i class="ri-file-excel-2-line"></i> Excel</a><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}">CSV</a></div>@endcan
 </div>
</form>
@if($chips->isNotEmpty())
 <div class="tl-chips tl-chips-bar">
  @foreach($chips as [$label, $removeUrl])<a href="{{ $removeUrl }}" class="tl-chip" title="Remove this filter">{{ $label }} <i class="ri-close-line"></i></a>@endforeach
  <a href="{{ route('bookings.index', array_filter(['status' => request('status')])) }}" class="btn btn-link btn-sm text-danger">Clear all filters</a>
 </div>
@endif
<div class="table-responsive"><table class="table table-hover align-middle tl-table bk-list"><thead><tr><th>Booking</th><th class="d-none d-sm-table-cell">Customer</th><th class="d-none d-md-table-cell">Trip / date</th><th class="d-none d-md-table-cell">People</th>@if($canMoney)<th class="text-end d-none d-sm-table-cell">Total</th>@endif<th class="d-none d-md-table-cell">Payment</th><th class="d-none d-sm-table-cell">Status</th><th class="text-end">Actions</th></tr></thead><tbody>
@forelse($bookings as $b)
 <tr>
  <td><a class="bk-ref" href="{{route('bookings.show',$b)}}">{{$b->reference}}</a><div class="bk-sub">{{ $typeLabel($b->type) }} · {{$b->created_at?->format('M j, Y')}}</div><div class="d-sm-none mt-1">{{$b->first_name}} {{$b->last_name}} <span class="badge bg-{{ $statusColors[$b->status] ?? 'secondary' }}">{{\App\Models\Booking::STATUSES[$b->status]??$b->status}}</span></div></td>
  <td class="d-none d-sm-table-cell"><div class="fw-semibold">{{$b->first_name}} {{$b->last_name}}</div><div class="bk-sub">{{$b->phone ?: $b->email}}</div></td>
  <td class="d-none d-md-table-cell">{{$b->tour?->name?:'—'}}@if($b->departure)<div class="bk-sub">{{$b->departure->departs_at->format('M j, Y')}}</div>@endif</td>
  <td class="d-none d-md-table-cell">{{$b->participant_count}}@if($b->seat_count !== $b->participant_count)<div class="bk-sub">{{$b->seat_count}} {{ Str::plural('seat', $b->seat_count) }}</div>@endif</td>
  @if($canMoney)<td class="bk-money d-none d-sm-table-cell">@if($b->total_amount){{money($b->total_amount,$b->currency)}}<div class="bk-sub">{{money($b->amount_paid,$b->currency)}} paid</div>@else<span class="text-muted fw-normal">—</span>@endif</td>@endif
  <td class="d-none d-md-table-cell"><span class="badge bg-{{ $paymentColors[$b->payment_status] ?? 'secondary' }}">{{\App\Models\Booking::PAYMENT_STATUSES[$b->payment_status]??$b->payment_status}}</span></td>
  <td class="d-none d-sm-table-cell"><span class="badge bg-{{ $statusColors[$b->status] ?? 'secondary' }}">{{\App\Models\Booking::STATUSES[$b->status]??$b->status}}</span></td>
  <td class="bk-actions"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#booking-details-{{ $b->id }}">Details</button> @can('delete-bookings')<form class="d-inline" method="post" action="{{route('bookings.destroy',$b)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $b->reference }}" onclick="return confirm('Delete booking {{ $b->reference }}? This cannot be undone.')"><i class="ri-delete-bin-line"></i></button></form>@endcan</td>
 </tr>
@empty<tr><td colspan="8" class="bk-empty"><i class="ri-inbox-line" aria-hidden="true"></i>@if($filtered)<strong>No bookings match these filters</strong><a href="{{ route('bookings.index') }}">Clear all filters</a>@else<strong>No bookings or requests yet</strong>They appear here as soon as a customer books a trip or sends a request.@endif</td></tr>@endforelse
</tbody></table></div><div class="tl-pager">{{$bookings->links('admin.vendor.pagination.bootstrap-5')}}</div></div></div>

{{-- One details dialog per row: the list stays short, everything else is one click away. --}}
@foreach($bookings as $b)
@php
  $wa = preg_replace('/\D+/', '', (string) ($b->whatsapp ?: ''));
  $tel = preg_replace('/[^\d+]/', '', (string) $b->phone);
@endphp
<div class="modal fade" id="booking-details-{{ $b->id }}" tabindex="-1" aria-labelledby="booking-details-{{ $b->id }}-title" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title d-flex flex-wrap align-items-center gap-2" id="booking-details-{{ $b->id }}-title">{{ $b->reference }} <span class="badge bg-{{ $statusColors[$b->status] ?? 'secondary' }}">{{\App\Models\Booking::STATUSES[$b->status]??$b->status}}</span> <span class="badge bg-{{ $paymentColors[$b->payment_status] ?? 'secondary' }}">{{\App\Models\Booking::PAYMENT_STATUSES[$b->payment_status]??$b->payment_status}}</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body">
  <div class="bk-modal-section">
   <h6>Customer</h6>
   <dl class="bk-dl">
    <dt>Name</dt><dd>{{ $b->first_name }} {{ $b->last_name }}</dd>
    <dt>Phone</dt><dd>{{ $b->phone ?: '—' }}</dd>
    @if($b->whatsapp)<dt>WhatsApp</dt><dd>{{ $b->whatsapp }}</dd>@endif
    <dt>Email</dt><dd>{{ $b->email ?: '—' }}</dd>
   </dl>
   <div class="bk-quick mt-3">
    @if($tel)<a class="btn btn-light" href="tel:{{ $tel }}"><i class="ri-phone-line"></i>Call</a>@endif
    @if($wa)<a class="btn btn-light" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i>WhatsApp</a>@endif
    @if($b->email)<a class="btn btn-light" href="mailto:{{ $b->email }}"><i class="ri-mail-line"></i>Email</a>@endif
   </div>
  </div>
  <div class="bk-modal-section">
   <h6>Trip</h6>
   <dl class="bk-dl">
    <dt>Type</dt><dd>{{ $typeLabel($b->type) }}</dd>
    <dt>Trip</dt><dd>{{ $b->tour?->name ?: '—' }}</dd>
    <dt>Date</dt><dd>{{ $b->departure ? $b->departure->departs_at->format('D, M j, Y g:i A') : '—' }}</dd>
    <dt>People</dt><dd>{{ $b->participant_count }}@if($b->seat_count !== $b->participant_count) · {{ $b->seat_count }} {{ Str::plural('seat', $b->seat_count) }}@endif</dd>
    <dt>Submitted</dt><dd>{{ $b->created_at?->format('M j, Y g:i A') }}</dd>
    @if($b->comments)<dt>Comments</dt><dd>{{ Str::limit($b->comments, 300) }}</dd>@endif
   </dl>
  </div>
  @if($canMoney && $b->total_amount)
  <div class="bk-modal-section">
   <h6>Payment</h6>
   <dl class="bk-dl">
    <dt>Total</dt><dd class="fw-semibold">{{ money($b->total_amount, $b->currency) }}</dd>
    <dt>Paid</dt><dd>{{ money($b->amount_paid, $b->currency) }}</dd>
    @if($b->outstanding_amount !== null)<dt>Outstanding</dt><dd class="fw-semibold {{ (float) $b->outstanding_amount > 0 ? 'text-danger' : 'text-success' }}">{{ money($b->outstanding_amount, $b->currency) }}</dd>@endif
   </dl>
  </div>
  @endif
 </div>
 <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><a class="btn btn-primary" href="{{ route('bookings.show', $b) }}">Open booking <i class="ri-arrow-right-s-line"></i></a></div>
</div></div></div>
@endforeach
</div></div></div>@include('admin.includes.footer')
