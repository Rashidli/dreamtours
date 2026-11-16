@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php($tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $customer->full_name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item active">{{ $customer->full_name }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@if($customer->merged_into_id)<div class="alert alert-info">This customer was merged into <a href="{{ route('customers.show', $customer->merged_into_id) }}">{{ $customer->mergedInto?->full_name ?? 'customer #'.$customer->merged_into_id }}</a>. Their bookings and requests are there now.</div>
@elseif($customer->trashed())<div class="alert alert-warning d-flex justify-content-between align-items-center">This customer is archived. Their bookings and history are kept.@can('delete-customers')<form method="post" action="{{ route('customers.restore', $customer->id) }}">@csrf<button class="btn btn-sm btn-warning">Restore</button></form>@endcan</div>@endif

<div class="row">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h4 class="mb-1">{{ $customer->display_name }}</h4>
          @if($customer->status)<span class="badge bg-{{ $customer->status->color }}">{{ $customer->status->name }}</span>@endif
          @foreach($customer->tags as $tag)<span class="badge bg-{{ $tag->color }} bg-opacity-10 text-{{ $tag->color }} border border-{{ $tag->color }}">{{ $tag->name }}</span>@endforeach
        </div>
        @can('create-bookings')@unless($customer->trashed())<a class="btn btn-sm btn-outline-primary" href="{{ route('bookings.create', ['customer' => $customer->id]) }}"><i class="ri-add-line"></i> Book for this customer</a>@endunless @endcan
        @can('edit-customers')@unless($customer->trashed())<a class="btn btn-sm btn-primary" href="{{ route('customers.edit', $customer) }}"><i class="ri-pencil-line"></i> Edit</a>@endunless @endcan
      </div>
      <p class="mt-3 mb-1">
        @if($customer->isMarketingEligible())<span class="badge bg-success">Can receive promotions</span>
        @elseif($customer->status?->blocks_marketing)<span class="badge bg-dark">Do not contact — no promotions</span>
        @else<span class="badge bg-light text-muted">No marketing consent</span>@endif
      </p>
      @if($customer->marketing_opt_in_source)<p class="text-muted mb-0">Consent: {{ $customer->marketing_opt_in_source }}, {{ $customer->marketing_opt_in_at?->timezone($tz)->format('M j, Y') }}</p>@endif
      <hr>
      <dl class="row mb-0">
        <dt class="col-5 text-muted fw-normal">Customer ID</dt><dd class="col-7">#{{ $customer->id }}</dd>
        <dt class="col-5 text-muted fw-normal">Email</dt><dd class="col-7 text-break">@if($customer->email)<a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@else — @endif</dd>
        <dt class="col-5 text-muted fw-normal">Phone</dt><dd class="col-7">@if($customer->phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}">{{ $customer->phone }}</a>@else — @endif</dd>
        <dt class="col-5 text-muted fw-normal">WhatsApp</dt><dd class="col-7">@if($customer->whatsapp)<a href="https://wa.me/{{ preg_replace('/\D/', '', $customer->whatsapp) }}" target="_blank" rel="noopener">{{ $customer->whatsapp }}</a>@else — @endif</dd>
        @can('view-customer-birthdays')<dt class="col-5 text-muted fw-normal">Date of birth</dt><dd class="col-7">{{ $customer->date_of_birth?->format('M j, Y') ?? '—' }}</dd>@endcan
        <dt class="col-5 text-muted fw-normal">City</dt><dd class="col-7">{{ $customer->city ?: '—' }}</dd>
        <dt class="col-5 text-muted fw-normal">Language</dt><dd class="col-7">{{ $customer->language ?: '—' }}</dd>
        <dt class="col-5 text-muted fw-normal">Source</dt><dd class="col-7">{{ \App\Models\Customer::SOURCES[$customer->source] ?? ($customer->source ?: '—') }}</dd>
        <dt class="col-5 text-muted fw-normal">Assigned to</dt><dd class="col-7">{{ $customer->assignedUser?->name ?? '—' }}</dd>
        <dt class="col-5 text-muted fw-normal">Customer since</dt><dd class="col-7">{{ $customer->created_at?->timezone($tz)->format('M j, Y') }}</dd>
        <dt class="col-5 text-muted fw-normal">Last activity</dt><dd class="col-7">{{ $customer->last_activity_at?->timezone($tz)->format('M j, Y g:i A') ?? '—' }}</dd>
      </dl>
      @if($customer->notes)<hr><h6 class="text-muted text-uppercase">Notes</h6><p class="mb-0">{!! nl2br(e($customer->notes)) !!}</p>@endif
    </div></div>
  </div>

  <div class="col-lg-8">
    <div class="row g-3 mb-3">
      @php($fin = auth()->user()->can('view-financial-info'))
      @foreach(array_filter([['Trips', $stats->trips_count, null], $fin ? ['Total paid', money($stats->total_paid), null] : null, $fin ? ['Outstanding', money($stats->outstanding), $stats->outstanding > 0 ? 'text-danger' : null] : null, ['Last trip', $stats->last_trip_at ? \Illuminate\Support\Carbon::parse($stats->last_trip_at)->format('M j, Y') : '—', null]]) as [$label, $value, $class])
        <div class="col-6 col-md-3"><div class="card mb-0"><div class="card-body py-3"><p class="text-muted mb-1">{{ $label }}</p><h5 class="mb-0 {{ $class }}">{{ $value }}</h5></div></div></div>
      @endforeach
    </div>

    <div class="card"><div class="card-body">
      <h5 class="card-title">Bookings & requests <small class="text-muted fw-normal">({{ $customer->bookings->count() }})</small></h5>
      <div class="table-responsive"><table class="table crm-table align-middle mb-0">
        <thead><tr><th>Reference</th><th>Trip</th><th>Departure</th><th class="text-end">Guests</th>@if($fin)<th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th>@endif<th>Status</th></tr></thead>
        <tbody>
        @forelse($customer->bookings as $booking)
          {{-- Ledger figures (BookingMoney): due after discounts, net paid, and the outstanding it computed. --}}
          @php($balance = $booking->tour_departure_id ? (float) $booking->outstanding_amount : 0)
          <tr>
            <td>@can('list-bookings')<a href="{{ route('bookings.show', $booking) }}">{{ $booking->reference }}</a>@else{{ $booking->reference }}@endcan</td>
            <td>{{ $booking->tour?->name ?? \App\Mail\EnquiryReceived::LABELS[$booking->type] ?? ucfirst($booking->type) }}</td>
            <td>{{ $booking->departure?->departs_at?->format('M j, Y') ?? '—' }}</td>
            <td class="text-end">{{ $booking->participant_count }}</td>
            @if($fin)
            <td class="text-end">{{ $booking->tour_departure_id ? money(bcsub((string) $booking->total_amount, (string) $booking->discount_amount, 2)) : '—' }}</td>
            <td class="text-end">{{ $booking->tour_departure_id ? money($booking->amount_paid) : '—' }}</td>
            <td class="text-end {{ $balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $booking->tour_departure_id ? money($balance) : '—' }}</td>
            @endif
            <td><span class="badge bg-light text-body">{{ \App\Models\Booking::STATUSES[$booking->status] ?? $booking->status }}</span></td>
          </tr>
        @empty
          <tr><td colspan="{{ $fin ? 8 : 5 }}" class="text-muted">No bookings or requests yet.</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div></div>

    @if($companions->isNotEmpty())
    <div class="card"><div class="card-body">
      <h5 class="card-title">Travelled with</h5>
      <p class="mb-0">@foreach($companions as $passenger)<span class="badge bg-light text-body me-1 mb-1">{{ $passenger->first_name }} {{ $passenger->last_name }}</span>@endforeach</p>
    </div></div>
    @endif

    @if($customer->visaEnquiries->isNotEmpty())
    <div class="card"><div class="card-body">
      <h5 class="card-title">Visa enquiries</h5>
      <ul class="list-unstyled mb-0 small">@foreach($customer->visaEnquiries as $enquiry)<li><a href="{{ route('visa-enquiries.show', $enquiry) }}">{{ $enquiry->reference }}</a> · {{ $enquiry->country }} · {{ $enquiry->created_at?->timezone($tz)->format('M j, Y') }}</li>@endforeach</ul>
    </div></div>
    @endif

    <div class="card"><div class="card-body">
      <h5 class="card-title">Activity history</h5>
      @forelse($activity as $log)
        <div class="d-flex gap-3 py-2 border-top">
          <div class="text-muted text-nowrap" style="width:150px">{{ $log->created_at->timezone($tz)->format('M j, Y g:i A') }}</div>
          <div class="flex-grow-1 small"><strong>{{ $log->actor_name }}</strong> · {{ str($log->action)->replace('_', ' ') }} · <span class="text-muted">{{ $log->moduleLabel() }}: {{ $log->subject_label }}</span></div>
        </div>
      @empty
        <p class="text-muted mb-0">No recorded activity yet.</p>
      @endforelse
    </div></div>
  </div>
</div>

@can('merge-customers')@unless($customer->trashed())
<form class="d-flex justify-content-end align-items-center gap-2 mb-2" method="get" action="{{ route('customer-merge.compare') }}">
  <input type="hidden" name="keep" value="{{ $customer->id }}">
  <label class="text-muted mb-0" for="merge-other">Same person as customer #</label>
  <input class="form-control" style="width:90px" type="number" min="1" name="other" id="merge-other" required>
  <button class="btn btn-sm btn-outline-secondary"><i class="ri-git-merge-line"></i> Compare & merge</button>
</form>
@endunless @endcan
@can('delete-customers')@unless($customer->trashed())
<form method="post" action="{{ route('customers.destroy', $customer) }}" class="text-end mb-4" onsubmit="return confirm('Archive this customer? Their bookings and history are kept and they can be restored.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Archive customer</button></form>
@endunless @endcan
</div></div></div>@include('admin.includes.footer')
