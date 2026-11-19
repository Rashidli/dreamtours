@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php($tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)
@php($bookingTones = ['new' => 'info', 'contacted' => 'info', 'payment_pending' => 'warning', 'confirmed' => 'success', 'completed' => 'secondary', 'cancelled' => 'danger'])

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
@if($customer->merged_into_id)<div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-git-merge-line"></i><div>This customer was merged into <a href="{{ route('customers.show', $customer->merged_into_id) }}">{{ $customer->mergedInto?->full_name ?? 'customer #'.$customer->merged_into_id }}</a>. Their bookings and requests are there now.</div></div>
@elseif($customer->trashed())<div class="alert alert-warning d-flex flex-wrap gap-2 justify-content-between align-items-center"><span><i class="ri-inbox-archive-line me-1"></i>This customer is archived. Their bookings and history are kept.</span>@can('delete-customers')<form method="post" action="{{ route('customers.restore', $customer->id) }}">@csrf<button class="btn btn-light"><i class="ri-arrow-go-back-line"></i> Restore</button></form>@endcan</div>@endif

<div class="row gx-lg-4">
  <div class="col-lg-4">
    <div class="card cs-profile"><div class="card-body">
      <div class="cs-profile-head">
        <div class="cs-profile-id">
          <span class="cs-avatar cs-avatar-xl" aria-hidden="true">{{ mb_strtoupper(mb_substr($customer->first_name ?? '', 0, 1).mb_substr($customer->last_name ?? '', 0, 1)) ?: '?' }}</span>
          <div class="min-w-0">
          <h4 class="cs-profile-name">{{ $customer->display_name }}</h4>
          <div class="cs-badges">
          @if($customer->status)<span class="badge bg-{{ $customer->status->color }}">{{ $customer->status->name }}</span>@endif
          @foreach($customer->tags as $tag)<span class="badge cs-tag">{{ $tag->name }}</span>@endforeach
          </div>
          </div>
        </div>
        @can('create-bookings')@unless($customer->trashed())<a class="btn btn-light flex-shrink-0" href="{{ route('bookings.create', ['customer' => $customer->id]) }}"><i class="ri-add-line"></i> Book for this customer</a>@endunless @endcan
        @can('edit-customers')@unless($customer->trashed())<a class="btn btn-light flex-shrink-0" href="{{ route('customers.edit', $customer) }}"><i class="ri-pencil-line"></i> Edit</a>@endunless @endcan
      </div>
      <p class="cs-consent">
        @if($customer->isMarketingEligible())<span class="badge bg-success bg-opacity-10 text-success"><i class="ri-mail-send-line"></i> Can receive promotions</span>
        @elseif($customer->status?->blocks_marketing)<span class="badge bg-dark"><i class="ri-mail-forbid-line"></i> Do not contact — no promotions</span>
        @else<span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="ri-mail-forbid-line"></i> No marketing consent</span>@endif
      </p>
      @if($customer->marketing_opt_in_source)<p class="cs-consent-note">Consent: {{ $customer->marketing_opt_in_source }}, {{ $customer->marketing_opt_in_at?->timezone($tz)->format('M j, Y') }}</p>@endif
      <hr class="cs-hr">
      <dl class="cs-dl mb-0">
        <dt>Customer ID</dt><dd>#{{ $customer->id }}</dd>
        <dt>Email</dt><dd class="text-break">@if($customer->email)<a class="cs-action-link" href="mailto:{{ $customer->email }}"><i class="ri-mail-line"></i>{{ $customer->email }}</a>@else — @endif</dd>
        <dt>Phone</dt><dd>@if($customer->phone)<a class="cs-action-link" href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}"><i class="ri-phone-line"></i>{{ $customer->phone }}</a>@else — @endif</dd>
        <dt>WhatsApp</dt><dd>@if($customer->whatsapp)<a class="cs-action-link" href="https://wa.me/{{ preg_replace('/\D/', '', $customer->whatsapp) }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i>{{ $customer->whatsapp }}</a>@else — @endif</dd>
        @can('view-customer-birthdays')<dt>Date of birth</dt><dd>{{ $customer->date_of_birth?->format('M j, Y') ?? '—' }}</dd>@endcan
        <dt>City</dt><dd>{{ $customer->city ?: '—' }}</dd>
        <dt>Language</dt><dd>{{ $customer->language ?: '—' }}</dd>
        <dt>Source</dt><dd>{{ \App\Models\Customer::SOURCES[$customer->source] ?? ($customer->source ?: '—') }}</dd>
        <dt>Assigned to</dt><dd>{{ $customer->assignedUser?->name ?? '—' }}</dd>
        <dt>Customer since</dt><dd>{{ $customer->created_at?->timezone($tz)->format('M j, Y') }}</dd>
        <dt>Last activity</dt><dd>{{ $customer->last_activity_at?->timezone($tz)->format('M j, Y g:i A') ?? '—' }}</dd>
      </dl>
      @if($customer->notes)<hr class="cs-hr"><h6 class="cs-section-label">Notes</h6><p class="cs-note mb-0">{!! nl2br(e($customer->notes)) !!}</p>@endif
    </div></div>
  </div>

  <div class="col-lg-8">
    <div class="card cs-stat-card"><div class="cs-stat-row">
      @php($fin = auth()->user()->can('view-financial-info'))
      @foreach(array_filter([['Trips', $stats->trips_count, null], $fin ? ['Total paid', money($stats->total_paid), null] : null, $fin ? ['Outstanding', money($stats->outstanding), $stats->outstanding > 0 ? 'text-danger' : null] : null, ['Last trip', $stats->last_trip_at ? \Illuminate\Support\Carbon::parse($stats->last_trip_at)->format('M j, Y') : '—', null]]) as [$label, $value, $class])
        <div class="cs-stat"><p class="cs-stat-label">{{ $label }}</p><p class="cs-stat-value {{ $class }}">{{ $value }}</p></div>
      @endforeach
    </div></div>

    <div class="card tl-card cs-panel"><div class="card-body p-0">
      <div class="cs-panel-head"><h5 class="cs-panel-title"><i class="ri-file-list-3-line"></i> Bookings & requests <span class="text-muted fw-normal">{{ $customer->bookings->count() }}</span></h5></div>
      <div class="table-responsive"><table class="table align-middle tl-table mb-0">
        <thead><tr><th>Reference</th><th>Trip</th><th>Departure</th><th class="text-end">Guests</th>@if($fin)<th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th>@endif<th>Status</th></tr></thead>
        <tbody>
        @forelse($customer->bookings as $booking)
          {{-- Ledger figures (BookingMoney): due after discounts, net paid, and the outstanding it computed. --}}
          @php($balance = $booking->tour_departure_id ? (float) $booking->outstanding_amount : 0)
          <tr>
            <td class="text-nowrap fw-semibold">@can('list-bookings')<a href="{{ route('bookings.show', $booking) }}">{{ $booking->reference }}</a>@else{{ $booking->reference }}@endcan</td>
            <td>{{ $booking->tour?->name ?? \App\Mail\EnquiryReceived::LABELS[$booking->type] ?? ucfirst($booking->type) }}</td>
            <td class="text-nowrap">{{ $booking->departure?->departs_at?->format('M j, Y') ?? '—' }}</td>
            <td class="text-end cs-num">{{ $booking->participant_count }}</td>
            @if($fin)
            <td class="text-end cs-num">{{ $booking->tour_departure_id ? money(bcsub((string) $booking->total_amount, (string) $booking->discount_amount, 2)) : '—' }}</td>
            <td class="text-end cs-num">{{ $booking->tour_departure_id ? money($booking->amount_paid) : '—' }}</td>
            <td class="text-end cs-num {{ $balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $booking->tour_departure_id ? money($balance) : '—' }}</td>
            @endif
            <td><span class="badge bg-{{ $bookingTones[$booking->status] ?? 'secondary' }} bg-opacity-10 text-{{ $bookingTones[$booking->status] ?? 'secondary' }}">{{ \App\Models\Booking::STATUSES[$booking->status] ?? $booking->status }}</span></td>
          </tr>
        @empty
          <tr><td colspan="{{ $fin ? 8 : 5 }}" class="cs-empty cs-empty-sm"><i class="ri-suitcase-line"></i>No bookings or requests yet.</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div></div>

    @if($companions->isNotEmpty())
    <div class="card"><div class="card-body">
      <h5 class="cs-panel-title mb-3"><i class="ri-team-line"></i> Travelled with</h5>
      <p class="cs-badges mb-0">@foreach($companions as $passenger)<span class="cs-person-chip"><i class="ri-user-3-line"></i>{{ $passenger->first_name }} {{ $passenger->last_name }}</span>@endforeach</p>
    </div></div>
    @endif

    @if($customer->visaEnquiries->isNotEmpty())
    <div class="card"><div class="card-body">
      <h5 class="cs-panel-title mb-3"><i class="ri-passport-line"></i> Visa enquiries</h5>
      <ul class="list-unstyled cs-list mb-0">@foreach($customer->visaEnquiries as $enquiry)<li><a class="fw-semibold" href="{{ route('visa-enquiries.show', $enquiry) }}">{{ $enquiry->reference }}</a> <span class="text-muted">· {{ $enquiry->country }} · {{ $enquiry->created_at?->timezone($tz)->format('M j, Y') }}</span></li>@endforeach</ul>
    </div></div>
    @endif

    <div class="card"><div class="card-body">
      <h5 class="cs-panel-title mb-3"><i class="ri-history-line"></i> Activity history</h5>
      @forelse($activity as $log)
        <div class="cs-timeline-item">
          <div class="cs-timeline-time">{{ $log->created_at->timezone($tz)->format('M j, Y g:i A') }}</div>
          <div class="cs-timeline-body"><strong>{{ $log->actor_name }}</strong> · {{ str($log->action)->replace('_', ' ') }} · <span class="text-muted">{{ $log->moduleLabel() }}: {{ $log->subject_label }}</span></div>
        </div>
      @empty
        <p class="text-muted mb-0">No recorded activity yet.</p>
      @endforelse
    </div></div>
  </div>
</div>

<div class="cs-footer-actions">
@can('merge-customers')@unless($customer->trashed())
<form class="cs-tool" method="get" action="{{ route('customer-merge.compare') }}">
  <input type="hidden" name="keep" value="{{ $customer->id }}">
  <label class="cs-tool-label" for="merge-other"><i class="ri-git-merge-line"></i> Same person as customer #</label>
  <input class="form-control cs-tool-input" type="number" min="1" name="other" id="merge-other" required placeholder="ID">
  <button class="btn btn-light"><i class="ri-git-merge-line"></i> Compare & merge</button>
</form>
@endunless @endcan
@can('delete-customers')@unless($customer->trashed())
<form method="post" action="{{ route('customers.destroy', $customer) }}" class="cs-archive" onsubmit="return confirm('Archive this customer? Their bookings and history are kept and they can be restored.')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="ri-inbox-archive-line"></i> Archive customer</button></form>
@endunless @endcan
</div>
</div></div></div>@include('admin.includes.footer')
