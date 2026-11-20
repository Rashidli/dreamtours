@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-bookings.css') }}?v={{ @filemtime(public_path('assets/css/admin-bookings.css')) }}">
@php($money = fn ($v) => $v !== null ? money($v, $booking->currency) : '—')
@php($statusColors = ['new' => 'warning', 'contacted' => 'info', 'payment_pending' => 'info', 'confirmed' => 'success', 'cancelled' => 'secondary', 'completed' => 'secondary'])
@php($paymentColors = ['unpaid' => 'danger', 'proof_submitted' => 'warning', 'partially_paid' => 'info', 'deposit_paid' => 'info', 'paid' => 'success', 'complimentary' => 'success', 'refunded' => 'secondary'])
@php($tel = preg_replace('/[^\d+]/', '', (string) $booking->phone))
@php($wa = preg_replace('/\D+/', '', (string) $booking->whatsapp))
<div class="main-content"><div class="page-content"><div class="container-fluid">
  {{-- Page header: reference, status, quick contact actions --}}
  <div class="bk-header">
    <div class="bk-header-main">
      <a class="btn btn-light bk-back" href="{{ route('bookings.index') }}" title="Back to bookings" aria-label="Back to bookings"><i class="ri-arrow-left-line"></i></a>
      <div class="min-w-0">
        <div class="bk-title"><h4>{{ $booking->reference }}</h4>
          <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }}">{{ \App\Models\Booking::STATUSES[$booking->status] ?? $booking->status }}</span>
          <span class="badge bg-{{ $paymentColors[$booking->payment_status] ?? 'secondary' }}">{{ \App\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] ?? $booking->payment_status }}</span>
        </div>
        <div class="bk-meta">{{ str($booking->type)->replace('_', ' ')->ucfirst() }} · submitted {{ $booking->created_at?->format('M j, Y g:i A') }}@if($booking->updatedBy) · last updated {{ $booking->updated_at->format('M j, Y g:i A') }} by {{ $booking->updatedBy->name }}@endif</div>
      </div>
    </div>
    <div class="bk-header-actions">
      @if($tel)<a class="btn btn-light" href="tel:{{ $tel }}"><i class="ri-phone-line"></i>Call</a>@endif
      @if($wa)<a class="btn btn-light" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i>WhatsApp</a>@endif
      @if($booking->email)<a class="btn btn-light" href="mailto:{{ $booking->email }}"><i class="ri-mail-line"></i>Email</a>@endif
    </div>
  </div>
  @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="row">
    <div class="col-lg-8">
      {{-- Trip & date --}}
      <div class="card bk-card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-route-line"></i></span><div><h5>Trip & date</h5><p>What was booked and when it leaves.</p></div></div>
        <div class="bk-facts">
          <div><div class="bk-fact-label">Trip</div><div class="bk-fact-value">@if($booking->tour && $booking->tour->trashed()){{ $booking->tour->name ?: 'Tour #'.$booking->tour->id }} <span class="badge bg-secondary">Deleted tour</span>@elseif($booking->tour)<a href="{{ route('tours.edit', $booking->tour) }}">{{ $booking->tour->name }}</a>@else — @endif</div></div>
          <div><div class="bk-fact-label">Date</div><div class="bk-fact-value">@if($booking->departure){{ \App\Support\DepartureCalendar::range($booking->departure) }} · {{ $booking->departure->departs_at->format('g:i A') }} @can('list-bookings')<div class="bk-sub"><a href="{{ route('tour-departures.passengers', $booking->departure) }}">Passenger list for this date</a></div>@endcan @else — @endif</div></div>
          <div><div class="bk-fact-label">Passengers</div><div class="bk-fact-value">{{ $booking->participant_count }}@if($booking->seat_count !== $booking->participant_count) <div class="bk-sub">{{ $booking->seat_count }} {{ Str::plural('seat', $booking->seat_count) }} (lap infants have no seat)</div>@endif</div></div>
          @if($booking->roommate_request)<div><div class="bk-fact-label">Roommate request</div><div class="bk-fact-value">{{ $booking->roommate_request }}</div></div>@endif
        </div>
      </div></div>

      {{-- Contact details --}}
      <div class="card bk-card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-user-3-line"></i></span><div><h5>Contact details</h5><p>Who to call about this booking.</p></div>
          @can('edit-bookings')<button type="button" class="btn btn-light ms-auto" data-bs-toggle="modal" data-bs-target="#editDetails"><i class="ri-edit-line"></i> Edit</button>@endcan</div>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="bk-contact-name">{{ $booking->first_name }} {{ $booking->last_name }}</div>
            @if($booking->date_of_birth)<div class="bk-sub">Date of birth {{ $booking->date_of_birth->format('M j, Y') }}</div>@endif
            <ul class="bk-contact-list">
              <li><i class="ri-phone-line" aria-hidden="true"></i><a href="tel:{{ $tel }}">{{ $booking->phone }}</a></li>
              @if($booking->whatsapp)<li><i class="ri-whatsapp-line" aria-hidden="true"></i><a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">{{ $booking->whatsapp }}</a></li>@endif
              <li><i class="ri-mail-line" aria-hidden="true"></i><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></li>
            </ul>
          </div>
          <div class="col-md-6">
            <div class="bk-facts" style="grid-template-columns:1fr">
              @can('list-leads')@if($bookingLead = \App\Models\Lead::where('booking_id', $booking->id)->orWhere('request_booking_id', $booking->id)->first())<div><div class="bk-fact-label">Lead</div><div class="bk-fact-value"><a href="{{ route('leads.show', $bookingLead) }}">{{ $bookingLead->reference }}</a> <span class="badge bg-{{ $bookingLead->stage?->color }}">{{ $bookingLead->stage?->name }}</span></div></div>@endif @endcan
              @if($booking->customer_id)<div><div class="bk-fact-label">Customer record</div><div class="bk-fact-value">@can('list-customers')<a href="{{ route('customers.show', $booking->customer_id) }}">{{ $booking->customer?->full_name ?? 'Customer #'.$booking->customer_id }}</a>@else{{ $booking->customer?->full_name }}@endcan</div></div>@endif
              <div><div class="bk-fact-label">Emergency contact</div><div class="bk-fact-value">@if($booking->emergency_contact_name || $booking->emergency_contact_phone){{ $booking->emergency_contact_name }} @if($booking->emergency_contact_phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $booking->emergency_contact_phone) }}">{{ $booking->emergency_contact_phone }}</a>@endif @else<span class="text-muted fw-normal">Not given</span>@endif</div></div>
            </div>
          </div>
        </div>
        @php($formNotes = collect(['meal_selection' => 'Meal preference', 'special_requests' => 'Special requests', 'food_allergies' => 'Food allergies', 'promo_code' => 'Promo code', 'comments' => 'Comments', 'address' => 'Address', 'mobility_information' => 'Mobility', 'source' => 'Source'])->filter(fn ($label, $field) => filled($booking->{$field})))
        @if($formNotes->isNotEmpty())
        <div class="tf-subtle bk-notes">
          <div class="tf-label fw-semibold mb-2">From the booking form</div>
          <div class="bk-facts">
          @foreach($formNotes as $field => $label)
            <div><div class="bk-fact-label">{{ $label }}</div><div class="bk-fact-value">{!! nl2br(e($booking->{$field})) !!}</div></div>
          @endforeach
          </div>
        </div>
        @endif
      </div></div>

      {{-- Passengers & seats --}}
      <div class="card bk-card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-group-line"></i></span><div><h5>Passengers & seats</h5><p>Fares, rooms, pickup points and seat numbers.</p></div>
          @if($booking->seat_notice_sent_at)<span class="badge bg-success ms-auto">Seat numbers sent {{ $booking->seat_notice_sent_at->format('M j, H:i') }}</span>@endif
        </div>
        <form method="post" action="{{ route('bookings.seats', $booking) }}">@csrf @method('PUT')
          <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>#</th><th>Name</th><th>Fare</th><th>Room</th><th>Pickup</th><th>Meal</th><th style="width:120px">Seat</th></tr></thead>
            <tbody>
            @forelse($booking->passengers as $i => $p)
              <tr><td class="text-muted">{{ $i + 1 }}</td><td><span class="fw-semibold">{{ $p->first_name }} {{ $p->last_name }}</span>@if($p->date_of_birth)<div class="bk-sub">Born {{ $p->date_of_birth->format('M j, Y') }}</div>@endif</td>
                <td>{{ \App\Models\TourDeparturePrice::CODES[$p->price_code][0] ?? ($p->category ?: '—') }}@if($p->price !== null)@can('view-financial-info')<div class="bk-sub">{{ $money($p->price) }}</div>@endcan @endif</td>
                <td>{{ $p->room_number ?: '—' }}</td><td>{{ $p->pickupPoint?->name ?: '—' }}</td><td>{{ $p->meal_selection ?: '—' }}</td>
                <td>@can('edit-bookings')<input class="form-control bk-seat-input" name="seats[{{ $p->id }}]" value="{{ old('seats.'.$p->id, $p->seat_number) }}" placeholder="e.g. 12" aria-label="Seat for {{ $p->first_name }} {{ $p->last_name }}">@else{{ $p->seat_number ?: '—' }}@endcan</td></tr>
            @empty
              <tr><td colspan="7" class="bk-empty"><i class="ri-group-line" aria-hidden="true"></i><strong>No passenger details</strong>This request was sent without a passenger list.@can('edit-bookings') Use “Edit” in contact details to add passengers.@endcan</td></tr>
            @endforelse
            </tbody>
          </table></div>
          @can('edit-bookings')@if($booking->passengers->isNotEmpty())<div class="mt-3"><button class="btn btn-primary"><i class="ri-save-line"></i> Save seat numbers</button></div>@endif @endcan
        </form>
        @can('edit-bookings')@if($booking->passengers->isNotEmpty())
          <form class="bk-card-foot" method="post" action="{{ route('bookings.seat-notice', $booking) }}">@csrf
            <span class="text-muted">{{ $seatBlocker ?: 'Seat numbers are also sent automatically 2 days before departure.' }}</span>
            <button class="btn btn-light" @disabled($seatBlocker) onclick="return confirm('Email the seat numbers to {{ $booking->email }}?')"><i class="ri-mail-send-line"></i> {{ $booking->seat_notice_sent_at ? 'Resend' : 'Send' }} seat numbers to customer</button>
          </form>
        @endif @endcan
      </div></div>

      @can('view-financial-info')@if($booking->tour_departure_id)@include('admin.bookings._ledger')@endif @endcan

      {{-- Edit contact and passengers (dialog, opened from Contact details) --}}
      @can('edit-bookings')
      <div class="modal fade" id="editDetails" tabindex="-1" aria-labelledby="editDetailsTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable">
          <form class="modal-content" method="post" action="{{ route('bookings.details', $booking) }}">@csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title" id="editDetailsTitle">Edit contact details & passengers</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-3"><label class="form-label fw-semibold">First name</label><input class="form-control" name="first_name" value="{{ old('first_name', $booking->first_name) }}" required></div>
              <div class="col-md-3"><label class="form-label fw-semibold">Last name</label><input class="form-control" name="last_name" value="{{ old('last_name', $booking->last_name) }}" required></div>
              <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $booking->email) }}" required></div>
              <div class="col-md-3"><label class="form-label fw-semibold">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $booking->phone) }}" required></div>
              <div class="col-md-3"><label class="form-label fw-semibold">WhatsApp</label><input class="form-control" name="whatsapp" value="{{ old('whatsapp', $booking->whatsapp) }}"><div class="form-text">With country code.</div></div>
              <div class="col-md-3"><label class="form-label fw-semibold">Emergency contact name</label><input class="form-control" name="emergency_contact_name" value="{{ old('emergency_contact_name', $booking->emergency_contact_name) }}"></div>
              <div class="col-md-3"><label class="form-label fw-semibold">Emergency contact phone</label><input class="form-control" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $booking->emergency_contact_phone) }}"></div>
            </div>
            <h6 class="mt-4 mb-1 fw-semibold">Passengers</h6>
            <p class="form-text mt-0 mb-2">Removing or adding passengers updates the seats left on this date.</p>
            <div class="table-responsive bk-pax-edit"><table class="table align-middle">
              <thead><tr><th>First name</th><th>Last name</th><th>Pickup point</th><th>Meal</th><th class="text-center">Remove</th></tr></thead>
              <tbody>
              @foreach($booking->passengers as $p)
                <tr>
                  <td><input class="form-control" name="passengers[{{ $p->id }}][first_name]" value="{{ old('passengers.'.$p->id.'.first_name', $p->first_name) }}" required></td>
                  <td><input class="form-control" name="passengers[{{ $p->id }}][last_name]" value="{{ old('passengers.'.$p->id.'.last_name', $p->last_name) }}" required></td>
                  <td><select class="form-select" name="passengers[{{ $p->id }}][pickup_point_id]"><option value="">—</option>@foreach($pickupOptions as $o)<option value="{{ $o->id }}" @selected((int) old('passengers.'.$p->id.'.pickup_point_id', $p->pickup_point_id) === $o->id)>{{ $o->name }}</option>@endforeach</select></td>
                  <td>@if($mealOptions)<select class="form-select" name="passengers[{{ $p->id }}][meal_selection]"><option value="">—</option>@foreach($mealOptions as $m)<option @selected(old('passengers.'.$p->id.'.meal_selection', $p->meal_selection) === $m)>{{ $m }}</option>@endforeach</select>@else<input class="form-control" name="passengers[{{ $p->id }}][meal_selection]" value="{{ $p->meal_selection }}">@endif</td>
                  <td class="text-center"><input type="checkbox" class="form-check-input" name="passengers[{{ $p->id }}][remove]" value="1" aria-label="Remove {{ $p->first_name }} {{ $p->last_name }}"></td>
                </tr>
              @endforeach
              @foreach(range(0, 1) as $n)
                <tr class="bk-new-row">
                  <td><input class="form-control" name="new_passengers[{{ $n }}][first_name]" placeholder="Add passenger: first name"></td>
                  <td><input class="form-control" name="new_passengers[{{ $n }}][last_name]" placeholder="Last name"></td>
                  <td><select class="form-select" name="new_passengers[{{ $n }}][pickup_point_id]"><option value="">—</option>@foreach($pickupOptions as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach</select></td>
                  <td>@if($mealOptions)<select class="form-select" name="new_passengers[{{ $n }}][meal_selection]"><option value="">—</option>@foreach($mealOptions as $m)<option>{{ $m }}</option>@endforeach</select>@else<input class="form-control" name="new_passengers[{{ $n }}][meal_selection]">@endif</td>
                  <td></td>
                </tr>
              @endforeach
              </tbody>
            </table></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-save-line"></i> Save details</button></div>
          </form>
      </div></div>
      @if($errors->hasAny(['first_name', 'last_name', 'email', 'phone', 'passengers']))<script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('editDetails')).show());</script>@endif
      @endcan
    </div>

    <div class="col-lg-4">
      @can('view-financial-info')@include('admin.bookings._money')@endcan

      @can('edit-bookings')
      <div class="card"><div class="card-body">
        <h5 class="card-title mb-3">Status & price</h5>
        <form method="post" action="{{ route('bookings.update', $booking) }}">@csrf @method('PUT')
          <div class="mb-3"><label class="form-label fw-semibold" for="booking-status">Booking status</label><select class="form-select" id="booking-status" name="status">@foreach(\App\Models\Booking::STATUSES as $key => $label)<option value="{{ $key }}" @selected($booking->status === $key)>{{ $label }}</option>@endforeach</select>
            <div class="form-text">Cancelled bookings free their seats on the date. Confirmed emails the customer once.</div></div>
          @if($booking->tour_departure_id)
          <div class="mb-3"><label class="form-label fw-semibold" for="booking-total">Price before discounts ({{ $booking->currency ?: 'CAD' }})</label><input class="form-control" id="booking-total" type="number" step="0.01" min="0" name="total_amount" value="{{ old('total_amount', $booking->total_amount) }}">
            <div class="form-text">Payments, refunds and discounts are recorded in the payment ledger, never typed here.</div></div>
          @endif
          <div class="mb-3"><label class="form-label fw-semibold" for="booking-notes">Internal notes <span class="text-muted fw-normal">(staff only)</span></label><textarea class="form-control" id="booking-notes" rows="4" name="internal_notes">{{ old('internal_notes', $booking->internal_notes) }}</textarea></div>
          <button class="btn btn-primary w-100">Save</button>
        </form>
      </div></div>
      @endcan

      <div class="card"><div class="card-body">
        <h5 class="card-title mb-3">Agreements</h5>
        <ul class="bk-checks">
          <li>@if($booking->terms_accepted_at)<i class="ri-checkbox-circle-fill bk-yes"></i><span>Terms accepted <span class="text-muted">{{ $booking->terms_accepted_at->format('M j, Y g:i A') }}</span></span>@else<i class="ri-indeterminate-circle-line bk-no"></i><span class="text-muted">Terms not recorded</span>@endif</li>
          <li>@if($booking->privacy_accepted_at)<i class="ri-checkbox-circle-fill bk-yes"></i><span>Privacy policy read <span class="text-muted">{{ $booking->privacy_accepted_at->format('M j, Y g:i A') }}</span></span>@else<i class="ri-indeterminate-circle-line bk-no"></i><span class="text-muted">Privacy not recorded</span>@endif</li>
          <li>@if($booking->marketing_consent)<i class="ri-checkbox-circle-fill bk-yes"></i><span>Agreed to news & promotions</span>@else<i class="ri-close-circle-line bk-no"></i><span class="text-muted">No marketing consent</span>@endif</li>
          @foreach($booking->legalAcceptances as $accepted)
            <li><i class="ri-checkbox-circle-fill bk-yes"></i><span>{{ $accepted->document->title }} <a href="{{ route('legal-documents.version', $accepted) }}" title="Open the exact wording accepted">version {{ $accepted->version }}</a> <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($accepted->pivot->accepted_at)->format('M j, Y g:i A') }}</span></span></li>
          @endforeach
          @if($booking->waiver_accepted_at && $booking->waiver_text)<li><i class="ri-checkbox-circle-fill bk-yes"></i><div class="min-w-0">Trip waiver accepted <span class="text-muted">{{ $booking->waiver_accepted_at->format('M j, Y g:i A') }}</span><details><summary>Trip waiver text agreed to</summary><div class="bk-waiver">{!! nl2br(e(strip_tags($booking->waiver_text))) !!}</div></details></div></li>@endif
          @if($booking->confirmation_sent_at)<li><i class="ri-mail-send-line bk-no"></i><span class="text-muted">Confirmation email sent {{ $booking->confirmation_sent_at->format('M j, Y g:i A') }}</span></li>@endif
        </ul>
      </div></div>
    </div>
  </div>
  <div class="card bk-card"><div class="card-body">
    <div class="tf-head"><span class="tf-icon"><i class="ri-history-line"></i></span><div><h5>Activity history <span class="text-muted fw-normal">({{ $activity->count() }}{{ $activity->count() === 100 ? '+' : '' }})</span></h5><p>Every change to this booking and its passengers.</p></div></div>
    <ul class="bk-activity">
    @forelse($activity as $log)
      <li>
        <div class="bk-activity-time" title="{{ $log->created_at->utc()->format('Y-m-d H:i:s') }} UTC">{{ $log->created_at->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }}</div>
        <div class="bk-activity-body">
          <div><strong>{{ $log->actor_name }}</strong> · {{ str($log->action)->replace('_', ' ') }}@if($log->subject_type !== \App\Models\Booking::class) · <span class="text-muted">{{ $log->subject_label }}</span>@endif</div>
          @include('admin.activity_log.entry')
        </div>
      </li>
    @empty
      <li class="bk-empty"><i class="ri-history-line" aria-hidden="true"></i><strong>No recorded activity yet</strong>Changes made from now on appear here.</li>
    @endforelse
    </ul>
  </div></div>
</div></div></div>
@include('admin.includes.footer')
