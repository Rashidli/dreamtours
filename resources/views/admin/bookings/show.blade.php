@include('admin.includes.header')
@php($money = fn ($v) => $v !== null ? money($v, $booking->currency) : '—')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Booking {{ $booking->reference }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('bookings.index') }}">Bookings</a></li><li class="breadcrumb-item active">{{ $booking->reference }}</li></ol></div>
  </div></div></div>
  @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  @if($booking->splitFrom || $booking->splits->isNotEmpty())
    <div class="alert alert-info py-2"><i class="ri-git-branch-line me-1"></i>
      @if($booking->splitFrom)Split from <a href="{{ route('bookings.show', $booking->splitFrom) }}">{{ $booking->splitFrom->reference }}</a>. @endif
      @if($booking->splits->isNotEmpty())Passengers split off: @foreach($booking->splits as $sp)<a href="{{ route('bookings.show', $sp) }}">{{ $sp->reference }}</a>{{ $sp->status === 'cancelled' ? ' (cancelled)' : ' ('.$sp->departure?->departs_at?->format('M j').')' }}{{ $loop->last ? '' : ', ' }}@endforeach @endif
    </div>
  @endif

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
            <p class="mb-1"><b>Passengers:</b> {{ $booking->participant_count }}@if($booking->seat_count !== $booking->participant_count) <span class="text-muted">· {{ $booking->seat_count }} {{ Str::plural('seat', $booking->seat_count) }} (lap infants have no seat)</span>@endif</p>
            @if($booking->roommate_request)<p class="mb-1"><b>Roommate request:</b> {{ $booking->roommate_request }}</p>@endif
          </div>
          <div class="col-md-6">
            <p class="mb-1"><b>Main contact:</b> {{ $booking->first_name }} {{ $booking->last_name }}@if($booking->date_of_birth) <small class="text-muted">(DOB {{ $booking->date_of_birth->format('Y-m-d') }})</small>@endif</p>
            @can('list-leads')@if($bookingLead = \App\Models\Lead::where('booking_id', $booking->id)->orWhere('request_booking_id', $booking->id)->first())<p class="mb-1"><b>Lead:</b> <a href="{{ route('leads.show', $bookingLead) }}">{{ $bookingLead->reference }} →</a> <span class="badge bg-{{ $bookingLead->stage?->color }}">{{ $bookingLead->stage?->name }}</span></p>@endif @endcan
            @if($booking->customer_id)<p class="mb-1"><b>Customer:</b> @can('list-customers')<a href="{{ route('customers.show', $booking->customer_id) }}">{{ $booking->customer?->full_name ?? 'Customer #'.$booking->customer_id }} →</a>@else{{ $booking->customer?->full_name }}@endcan</p>@endif
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
        @php($hasBuses = $booking->departure && $booking->departure->vehicles()->exists())
        <form method="post" action="{{ route('bookings.seats', $booking) }}">@csrf @method('PUT')
          <div class="table-responsive"><table class="table align-middle">
            @php($canSplit = auth()->user()->can('edit-bookings') && $booking->tour_departure_id && $booking->status !== 'cancelled')
            <thead><tr><th>#</th><th>Name</th><th>Fare</th><th>Room</th><th>Pickup</th><th>Meal</th><th style="width:110px">Seat</th>@if($canSplit)<th></th>@endif</tr></thead>
            <tbody>
            @forelse($booking->passengers as $i => $p)
              <tr><td>{{ $i + 1 }}</td><td>{{ $p->first_name }} {{ $p->last_name }}@if($p->date_of_birth)<br><small class="text-muted">DOB {{ $p->date_of_birth->format('Y-m-d') }}</small>@endif</td>
                <td>{{ \App\Models\TourDeparturePrice::CODES[$p->price_code][0] ?? ($p->category ?: '—') }}@if($p->price !== null)@can('view-financial-info')<br><small>{{ $money($p->price) }}</small>@endcan @endif</td>
                <td>{{ $p->room_number ?: '—' }}</td><td>{{ $p->pickupPoint?->name ?: '—' }}</td><td>{{ $p->meal_selection ?: '—' }}</td>
                <td>@if($hasBuses){{ \App\Support\Seating::label($p->departureVehicle, $p->seat_number) ?? '—' }}@elseif(auth()->user()->can('edit-bookings'))<input class="form-control" name="seats[{{ $p->id }}]" value="{{ old('seats.'.$p->id, $p->seat_number) }}" placeholder="e.g. 12">@else{{ $p->seat_number ?: '—' }}@endif</td>@if($canSplit)<td class="text-end text-nowrap"><button type="button" class="btn btn-sm btn-light" data-split="move" data-pax="{{ $p->id }}" data-name="{{ $p->first_name }} {{ $p->last_name }}" title="Move to another date"><i class="ri-arrow-left-right-line"></i></button>@if($booking->passengers->count() > 1) <button type="button" class="btn btn-sm btn-light text-danger" data-split="cancel" data-pax="{{ $p->id }}" data-name="{{ $p->first_name }} {{ $p->last_name }}" title="Cancel this passenger only"><i class="ri-user-unfollow-line"></i></button>@endif</td>@endif</tr>
            @empty
              <tr><td colspan="8" class="text-muted">No passenger details.</td></tr>
            @endforelse
            </tbody>
          </table></div>
          @if($hasBuses)
            <a class="btn btn-outline-primary" href="{{ route('seating.show', $booking->tour_departure_id) }}"><i class="ri-layout-grid-line me-1"></i>Seating board</a>
          @else
            @can('edit-bookings')@if($booking->passengers->isNotEmpty())<button class="btn btn-primary"><i class="ri-save-line"></i> Save seat numbers</button>@endif @endcan
          @endif
        </form>
        @can('edit-bookings')@if($booking->passengers->isNotEmpty())
          <form class="mt-3" method="post" action="{{ route('bookings.seat-notice', $booking) }}">@csrf
            <button class="btn btn-success" @disabled($seatBlocker) onclick="return confirm('Email the seat numbers to {{ $booking->email }}?')"><i class="ri-mail-send-line"></i> {{ $booking->seat_notice_sent_at ? 'Resend' : 'Send' }} seat numbers to customer</button>
            <small class="text-muted d-block mt-1">{{ $seatBlocker ?: 'Seat numbers are also sent automatically 2 days before departure.' }}</small>
          </form>
        @endif @endcan
      </div></div>

      @can('view-financial-info')@if($booking->tour_departure_id)@include('admin.bookings._ledger')@endif @endcan

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
            @include('admin.includes.capacity_override', ['id' => 'cap-details'])
            <button class="btn btn-primary mt-2"><i class="ri-save-line"></i> Save details</button>
          </form>
        </details>
      </div></div>
      @endcan
    </div>

    <div class="col-lg-4">
      @can('view-financial-info')@include('admin.bookings._money')@endcan

      @can('edit-bookings')
      <div class="card"><div class="card-body">
        <h5>Status & price</h5>
        <form method="post" action="{{ route('bookings.update', $booking) }}">@csrf @method('PUT')
          <div class="mb-2"><label class="form-label">Booking status</label><select class="form-select" name="status">@foreach(\App\Models\Booking::STATUSES as $key => $label)<option value="{{ $key }}" @selected($booking->status === $key)>{{ $label }}</option>@endforeach</select>
            <small class="text-muted">Cancelled bookings free their seats on the date.</small></div>
          @if($booking->tour_departure_id)
          <div class="mb-2"><label class="form-label">Price before discounts ({{ $booking->currency ?: 'CAD' }})</label><input class="form-control" type="number" step="0.01" min="0" name="total_amount" value="{{ old('total_amount', $booking->total_amount) }}">
            <small class="text-muted">Payments, refunds and discounts are recorded in the payment ledger, never typed here.</small></div>
          @endif
          <div class="mb-3"><label class="form-label">Internal notes <small class="text-muted">(staff only)</small></label><textarea class="form-control" rows="4" name="internal_notes">{{ old('internal_notes', $booking->internal_notes) }}</textarea></div>
          @include('admin.includes.capacity_override', ['id' => 'cap-status'])
          <button class="btn btn-primary w-100 mt-2">Save</button>
        </form>
      </div></div>
      @endcan

      <div class="card"><div class="card-body">
        <h5>Agreements</h5>
        <ul class="list-unstyled small mb-0">
          <li>{!! $booking->terms_accepted_at ? '✓ Terms accepted '.$booking->terms_accepted_at->format('Y-m-d H:i') : '— Terms not recorded' !!}</li>
          <li>{!! $booking->privacy_accepted_at ? '✓ Privacy Policy read '.$booking->privacy_accepted_at->format('Y-m-d H:i') : '— Privacy not recorded' !!}</li>
          <li>{{ $booking->marketing_consent ? '✓ Agreed to news & promotions' : '✗ No marketing consent' }}</li>
          @foreach($booking->legalAcceptances as $accepted)
            <li>✓ {{ $accepted->document->title }} <a href="{{ route('legal-documents.version', $accepted) }}" title="Open the exact wording accepted">version {{ $accepted->version }}</a> · {{ \Illuminate\Support\Carbon::parse($accepted->pivot->accepted_at)->format('Y-m-d H:i') }}</li>
          @endforeach
          @if($booking->waiver_accepted_at && $booking->waiver_text)<li>✓ Trip waiver accepted {{ $booking->waiver_accepted_at->format('Y-m-d H:i') }} <details><summary class="text-primary" style="cursor:pointer">Trip waiver text agreed to</summary><div class="border rounded p-2 mt-1" style="max-height:200px;overflow:auto">{!! nl2br(e(strip_tags($booking->waiver_text))) !!}</div></details></li>@endif
          @if($booking->confirmation_sent_at)<li class="mt-1 text-muted">Confirmation email sent {{ $booking->confirmation_sent_at->format('Y-m-d H:i') }}</li>@endif
        </ul>
      </div></div>
    </div>
  </div>
  <div class="card"><div class="card-body">
    <h5 class="card-title">Activity history <small class="text-muted fw-normal">({{ $activity->count() }}{{ $activity->count() === 100 ? '+' : '' }})</small></h5>
    @forelse($activity as $log)
      <div class="d-flex gap-3 py-2 border-top">
        <div class="small text-muted text-nowrap" style="width:150px" title="{{ $log->created_at->utc()->format('Y-m-d H:i:s') }} UTC">{{ $log->created_at->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }}</div>
        <div class="flex-grow-1">
          <div class="small"><strong>{{ $log->actor_name }}</strong> · {{ str($log->action)->replace('_', ' ') }}@if($log->subject_type !== \App\Models\Booking::class) · <span class="text-muted">{{ $log->subject_label }}</span>@endif</div>
          @include('admin.activity_log.entry')
        </div>
      </div>
    @empty
      <p class="text-muted small mb-0">No recorded activity yet. Changes made from now on appear here.</p>
    @endforelse
  </div></div>
@if(auth()->user()->can('edit-bookings') && $booking->tour_departure_id && $booking->status !== 'cancelled')
{{-- Inline @php only: this view already uses @php(...) at the top, and Blade cannot mix the two forms. --}}
@php($moveTo = \App\Models\TourDeparture::with('tour.translations')->where('departs_at', '>=', now())->whereNotIn('status', ['cancelled'])->whereKeyNot($booking->tour_departure_id)->orderBy('departs_at')->get()->sortBy(fn ($d) => [$d->tour_id === $booking->tour_id ? 0 : 1, $d->departs_at->timestamp]))
{{-- Move or cancel one passenger (CRM §6). One dialog, filled by the buttons in the passenger table. --}}
<div class="modal fade" id="splitPassenger" tabindex="-1" aria-labelledby="splitTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="">@csrf
  <div class="modal-header"><h5 class="modal-title" id="splitTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <div data-mode="move">
      <label class="form-label" for="split-dep">New date</label>
      <select class="form-select mb-2" id="split-dep" name="tour_departure_id">
        @foreach($moveTo->groupBy(fn ($d) => $d->tour?->name) as $tourName => $dates)
          <optgroup label="{{ $tourName }}{{ $dates->first()->tour_id === $booking->tour_id ? ' (same trip)' : '' }}">@foreach($dates as $d)<option value="{{ $d->id }}">{{ $d->departs_at->format('D, M j, Y g:i A') }} · {{ max(0, $d->available_seats) }} left</option>@endforeach</optgroup>
        @endforeach
      </select>
      <p class="text-muted" data-move-note></p>
      @include('admin.includes.capacity_override', ['id' => 'cap-move'])
    </div>
    <p class="text-muted" data-mode="cancel">The other passengers stay on this booking. The cancelled passenger is kept on a cancelled booking of their own, and this booking's price is recalculated without them. Refund any overpayment from Payments.</p>
    <label class="form-label mt-2" for="split-reason">Reason</label>
    <input class="form-control" id="split-reason" name="reason" maxlength="255" required placeholder="e.g. Asked to travel a week later">
    <p class="text-muted mt-2 mb-0">Kept in the activity history of both bookings.</p>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary" data-submit></button></div>
</form></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('splitPassenger'), form = el.querySelector('form');
  const urls = { move: @json(route('booking-passengers.move', [$booking, '__P__'])), cancel: @json(route('booking-passengers.cancel', [$booking, '__P__'])) };
  const single = {{ $booking->passengers->count() === 1 ? 'true' : 'false' }};
  document.querySelectorAll('[data-split]').forEach(btn => btn.addEventListener('click', () => {
    const mode = btn.dataset.split;
    form.action = urls[mode].replace('__P__', btn.dataset.pax);
    el.querySelectorAll('[data-mode]').forEach(x => x.classList.toggle('d-none', x.dataset.mode !== mode));
    form.tour_departure_id.disabled = mode !== 'move';
    el.querySelector('#splitTitle').textContent = (mode === 'move' ? 'Move ' : 'Cancel ') + btn.dataset.name + (mode === 'move' ? ' to another date' : ' only');
    el.querySelector('[data-move-note]').textContent = single ? 'This is the only passenger, so the whole booking moves; its price changes to the new date’s fare and its payments stay with it.' : 'They get a booking of their own on the new date, at that date’s fare. This booking keeps its payments and is recalculated without them.';
    const submit = el.querySelector('[data-submit]');
    submit.textContent = mode === 'move' ? 'Move passenger' : 'Cancel passenger';
    submit.className = 'btn ' + (mode === 'move' ? 'btn-primary' : 'btn-danger');
    form.reason.value = '';
    bootstrap.Modal.getOrCreateInstance(el).show();
  }));
});
</script>
@endif
</div></div></div>
@include('admin.includes.footer')
