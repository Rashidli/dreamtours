@include('admin.includes.header')
@php($money = fn ($v) => $v !== null ? money($v, $booking->currency) : '—')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Booking {{ $booking->reference }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('bookings.index') }}">Bookings</a></li><li class="breadcrumb-item active">{{ $booking->reference }}</li></ol></div>
  </div></div></div>
  @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="row">
    <div class="col-lg-8">
      <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h4 class="mb-1">{{ $booking->reference }} <span class="badge bg-{{ ['new' => 'warning', 'payment_pending' => 'info', 'confirmed' => 'success', 'cancelled' => 'secondary', 'completed' => 'dark'][$booking->status] ?? 'secondary' }} align-middle">{{ \App\Models\Booking::STATUSES[$booking->status] ?? $booking->status }}</span></h4>
            <p class="text-muted mb-0">{{ str($booking->type)->replace('_', ' ')->title() }} · submitted {{ $booking->created_at?->format('Y-m-d H:i') }}@if($booking->updatedBy) · last updated {{ $booking->updated_at->format('Y-m-d H:i') }} by {{ $booking->updatedBy->name }}@endif</p>
          </div>
          <a class="btn btn-light" href="{{ route('bookings.index') }}"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-6">
            <p class="mb-1"><b>Trip:</b> @if($booking->tour)<a href="{{ route('tours.edit', $booking->tour) }}">{{ $booking->tour->name }}</a>@else — @endif</p>
            <p class="mb-1"><b>Date:</b> @if($booking->departure){{ \App\Support\DepartureCalendar::range($booking->departure) }} · {{ $booking->departure->departs_at->format('g:i A') }} @can('list-bookings')<a href="{{ route('tour-departures.passengers', $booking->departure) }}" class="small">(passenger list)</a>@endcan @else — @endif</p>
            <p class="mb-1"><b>Passengers:</b> {{ $booking->participant_count }}</p>
            @if($booking->roommate_request)<p class="mb-1"><b>Roommate request:</b> {{ $booking->roommate_request }}</p>@endif
          </div>
          <div class="col-md-6">
            <p class="mb-1"><b>Main contact:</b> {{ $booking->first_name }} {{ $booking->last_name }}@if($booking->date_of_birth) <small class="text-muted">(DOB {{ $booking->date_of_birth->format('Y-m-d') }})</small>@endif</p>
            <p class="mb-1"><b>Phone:</b> <a href="tel:{{ preg_replace('/[^\d+]/', '', $booking->phone) }}">{{ $booking->phone }}</a></p>
            @if($booking->whatsapp)<p class="mb-1"><b>WhatsApp:</b> <a href="https://wa.me/{{ preg_replace('/\D+/', '', $booking->whatsapp) }}" target="_blank" rel="noopener">{{ $booking->whatsapp }}</a></p>@endif
            <p class="mb-1"><b>Email:</b> <a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></p>
            <p class="mb-1"><b>Emergency contact:</b> {{ $booking->emergency_contact_name ?: '—' }} {{ $booking->emergency_contact_phone }}</p>
          </div>
        </div>
        <div class="row mt-2">
          @foreach(['meal_selection' => 'Meal preference', 'special_requests' => 'Special requests', 'food_allergies' => 'Food allergies', 'promo_code' => 'Promo code', 'comments' => 'Comments', 'address' => 'Address', 'mobility_information' => 'Mobility', 'source' => 'Source'] as $field => $label)
            @if($booking->{$field})<div class="col-md-6"><p class="mb-1"><b>{{ $label }}:</b> {!! nl2br(e($booking->{$field})) !!}</p></div>@endif
          @endforeach
        </div>
      </div></div>

      {{-- Passengers & seats --}}
      <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Passengers & seats</h5>
          @if($booking->seat_notice_sent_at)<span class="badge bg-success">Seat numbers sent {{ $booking->seat_notice_sent_at->format('M j, H:i') }}</span>@endif
        </div>
        <form method="post" action="{{ route('bookings.seats', $booking) }}">@csrf @method('PUT')
          <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>#</th><th>Name</th><th>Fare</th><th>Room</th><th>Pickup</th><th>Meal</th><th style="width:110px">Seat</th></tr></thead>
            <tbody>
            @forelse($booking->passengers as $i => $p)
              <tr><td>{{ $i + 1 }}</td><td>{{ $p->first_name }} {{ $p->last_name }}@if($p->date_of_birth)<br><small class="text-muted">DOB {{ $p->date_of_birth->format('Y-m-d') }}</small>@endif</td>
                <td>{{ \App\Models\TourDeparturePrice::CODES[$p->price_code][0] ?? ($p->category ?: '—') }}@if($p->price !== null)<br><small>{{ $money($p->price) }}</small>@endif</td>
                <td>{{ $p->room_number ?: '—' }}</td><td>{{ $p->pickupPoint?->name ?: '—' }}</td><td>{{ $p->meal_selection ?: '—' }}</td>
                <td>@can('edit-bookings')<input class="form-control form-control-sm" name="seats[{{ $p->id }}]" value="{{ old('seats.'.$p->id, $p->seat_number) }}" placeholder="e.g. 12">@else{{ $p->seat_number ?: '—' }}@endcan</td></tr>
            @empty
              <tr><td colspan="7" class="text-muted">No passenger details.</td></tr>
            @endforelse
            </tbody>
          </table></div>
          @can('edit-bookings')@if($booking->passengers->isNotEmpty())<button class="btn btn-primary"><i class="ri-save-line"></i> Save seat numbers</button>@endif @endcan
        </form>
        @can('edit-bookings')@if($booking->passengers->isNotEmpty())
          <form class="mt-3" method="post" action="{{ route('bookings.seat-notice', $booking) }}">@csrf
            <button class="btn btn-success" @disabled($seatBlocker) onclick="return confirm('Email the seat numbers to {{ $booking->email }}?')"><i class="ri-mail-send-line"></i> {{ $booking->seat_notice_sent_at ? 'Resend' : 'Send' }} seat numbers to customer</button>
            <small class="text-muted d-block mt-1">{{ $seatBlocker ?: 'Seat numbers are also sent automatically 2 days before departure.' }}</small>
          </form>
        @endif @endcan
      </div></div>

      {{-- Edit contact and passengers --}}
      @can('edit-bookings')
      <div class="card"><div class="card-body">
        <details @if($errors->hasAny(['first_name', 'last_name', 'email', 'phone', 'passengers'])) open @endif>
          <summary class="h5 mb-0" style="cursor:pointer">Edit contact details & passengers</summary>
          <form class="mt-3" method="post" action="{{ route('bookings.details', $booking) }}">@csrf @method('PUT')
            <div class="row g-2">
              <div class="col-md-3"><label class="form-label small">First name</label><input class="form-control" name="first_name" value="{{ old('first_name', $booking->first_name) }}" required></div>
              <div class="col-md-3"><label class="form-label small">Last name</label><input class="form-control" name="last_name" value="{{ old('last_name', $booking->last_name) }}" required></div>
              <div class="col-md-6"><label class="form-label small">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $booking->email) }}" required></div>
              <div class="col-md-3"><label class="form-label small">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $booking->phone) }}" required></div>
              <div class="col-md-3"><label class="form-label small">WhatsApp (with country code)</label><input class="form-control" name="whatsapp" value="{{ old('whatsapp', $booking->whatsapp) }}"></div>
              <div class="col-md-3"><label class="form-label small">Emergency contact name</label><input class="form-control" name="emergency_contact_name" value="{{ old('emergency_contact_name', $booking->emergency_contact_name) }}"></div>
              <div class="col-md-3"><label class="form-label small">Emergency contact phone</label><input class="form-control" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $booking->emergency_contact_phone) }}"></div>
            </div>
            <h6 class="mt-4">Passengers <small class="text-muted fw-normal">— removing or adding passengers updates the seats left on this date</small></h6>
            <div class="table-responsive"><table class="table table-sm align-middle">
              <thead><tr><th>First name</th><th>Last name</th><th>Pickup point</th><th>Meal</th><th class="text-center">Remove</th></tr></thead>
              <tbody>
              @foreach($booking->passengers as $p)
                <tr>
                  <td><input class="form-control form-control-sm" name="passengers[{{ $p->id }}][first_name]" value="{{ old('passengers.'.$p->id.'.first_name', $p->first_name) }}" required></td>
                  <td><input class="form-control form-control-sm" name="passengers[{{ $p->id }}][last_name]" value="{{ old('passengers.'.$p->id.'.last_name', $p->last_name) }}" required></td>
                  <td><select class="form-select form-select-sm" name="passengers[{{ $p->id }}][pickup_point_id]"><option value="">—</option>@foreach($pickupOptions as $o)<option value="{{ $o->id }}" @selected((int) old('passengers.'.$p->id.'.pickup_point_id', $p->pickup_point_id) === $o->id)>{{ $o->name }}</option>@endforeach</select></td>
                  <td>@if($mealOptions)<select class="form-select form-select-sm" name="passengers[{{ $p->id }}][meal_selection]"><option value="">—</option>@foreach($mealOptions as $m)<option @selected(old('passengers.'.$p->id.'.meal_selection', $p->meal_selection) === $m)>{{ $m }}</option>@endforeach</select>@else<input class="form-control form-control-sm" name="passengers[{{ $p->id }}][meal_selection]" value="{{ $p->meal_selection }}">@endif</td>
                  <td class="text-center"><input type="checkbox" class="form-check-input" name="passengers[{{ $p->id }}][remove]" value="1"></td>
                </tr>
              @endforeach
              @foreach(range(0, 1) as $n)
                <tr class="table-light">
                  <td><input class="form-control form-control-sm" name="new_passengers[{{ $n }}][first_name]" placeholder="Add passenger: first name"></td>
                  <td><input class="form-control form-control-sm" name="new_passengers[{{ $n }}][last_name]" placeholder="Last name"></td>
                  <td><select class="form-select form-select-sm" name="new_passengers[{{ $n }}][pickup_point_id]"><option value="">—</option>@foreach($pickupOptions as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach</select></td>
                  <td>@if($mealOptions)<select class="form-select form-select-sm" name="new_passengers[{{ $n }}][meal_selection]"><option value="">—</option>@foreach($mealOptions as $m)<option>{{ $m }}</option>@endforeach</select>@else<input class="form-control form-control-sm" name="new_passengers[{{ $n }}][meal_selection]">@endif</td>
                  <td></td>
                </tr>
              @endforeach
              </tbody>
            </table></div>
            <button class="btn btn-primary"><i class="ri-save-line"></i> Save details</button>
          </form>
        </details>
      </div></div>
      @endcan
    </div>

    <div class="col-lg-4">
      <div class="card"><div class="card-body">
        <h5>Payment</h5>
        <table class="table table-sm"><tbody>
          <tr><td>Total</td><td class="text-end"><b>{{ $money($booking->total_amount) }}</b></td></tr>
          <tr><td>Deposit due</td><td class="text-end">{{ $money($booking->deposit_amount) }}</td></tr>
          <tr><td>Paid so far</td><td class="text-end">{{ $money($booking->amount_paid) }}</td></tr>
          <tr><td>Balance</td><td class="text-end"><b>{{ $booking->total_amount !== null ? $money($booking->balance_due) : '—' }}</b></td></tr>
        </tbody></table>
        @if($booking->payment_proof)<a class="btn btn-outline-primary w-100 mb-2" target="_blank" href="{{ route('bookings.payment-proof', $booking) }}"><i class="ri-file-image-line"></i> View payment screenshot</a><p class="small text-muted">The screenshot does not confirm payment — check it, then record the amount below.</p>@endif
      </div></div>

      @can('edit-bookings')
      <div class="card"><div class="card-body">
        <h5>Status & payment</h5>
        <form method="post" action="{{ route('bookings.update', $booking) }}">@csrf @method('PUT')
          <div class="mb-2"><label class="form-label">Booking status</label><select class="form-select" name="status">@foreach(\App\Models\Booking::STATUSES as $key => $label)<option value="{{ $key }}" @selected($booking->status === $key)>{{ $label }}</option>@endforeach</select>
            <small class="text-muted">Cancelled bookings free their seats on the date.</small></div>
          <div class="mb-2"><label class="form-label">Payment status</label><select class="form-select" name="payment_status">@foreach(\App\Models\Booking::PAYMENT_STATUSES as $key => $label)<option value="{{ $key }}" @selected($booking->payment_status === $key)>{{ $label }}</option>@endforeach</select></div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label">Total ({{ $booking->currency ?: 'CAD' }})</label><input class="form-control" type="number" step="0.01" min="0" name="total_amount" value="{{ old('total_amount', $booking->total_amount) }}"></div>
            <div class="col-6"><label class="form-label">Deposit / paid</label><input class="form-control" type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid', $booking->amount_paid) }}"></div>
          </div>
          <small class="text-muted d-block mb-2">Balance = total − paid. Correct the total if a discount or extra applies.</small>
          <div class="mb-3"><label class="form-label">Internal notes <small class="text-muted">(staff only)</small></label><textarea class="form-control" rows="4" name="internal_notes">{{ old('internal_notes', $booking->internal_notes) }}</textarea></div>
          <button class="btn btn-primary w-100">Save</button>
        </form>
      </div></div>
      @endcan

      <div class="card"><div class="card-body">
        <h5>Agreements</h5>
        <ul class="list-unstyled small mb-0">
          <li>{!! $booking->terms_accepted_at ? '✓ Terms accepted '.$booking->terms_accepted_at->format('Y-m-d H:i') : '— Terms not recorded' !!}</li>
          <li>{!! $booking->privacy_accepted_at ? '✓ Privacy Policy read '.$booking->privacy_accepted_at->format('Y-m-d H:i') : '— Privacy not recorded' !!}</li>
          <li>{{ $booking->marketing_consent ? '✓ Agreed to news & promotions' : '✗ No marketing consent' }}</li>
          @if($booking->waiver_accepted_at)<li>✓ Waiver accepted {{ $booking->waiver_accepted_at->format('Y-m-d H:i') }} <details><summary class="text-primary" style="cursor:pointer">Waiver text agreed to</summary><div class="border rounded p-2 mt-1" style="max-height:200px;overflow:auto">{!! nl2br(e(strip_tags($booking->waiver_text))) !!}</div></details></li>@endif
          @if($booking->confirmation_sent_at)<li class="mt-1 text-muted">Confirmation email sent {{ $booking->confirmation_sent_at->format('Y-m-d H:i') }}</li>@endif
        </ul>
      </div></div>
    </div>
  </div>
</div></div></div>
@include('admin.includes.footer')
