{{-- Departure dates of a tour. On "New tour" the first dates are part of the main form; on edit each date is managed on its own. --}}
@php
  $statuses = \App\Models\TourDeparture::STATUSES;
  $staffUsers = auth()->user()->can('assign-departure-staff') ? \App\Models\User::with('roles')->orderBy('name')->get() : collect();
  $badge = ['available' => 'success', 'limited' => 'warning', 'sold_out' => 'danger', 'closed' => 'secondary', 'hidden' => 'dark', 'cancelled' => 'secondary'];
@endphp
<div class="card" id="departures"><div class="card-body">
  <h5 class="mb-1">Departure dates</h5>
  <p class="text-muted small mb-3">One trip, many dates: the trip's description, itinerary and photos are shared; each date has its own seats, price, status and pickup points. Visitors pick a date in the calendar on the trip page.</p>

  @if(!$tour->exists)
    {{-- First dates, saved together with the new tour --}}
    <div class="row g-2 mb-2">
      <div class="col-md-2"><label class="form-label">Price per person</label><input form="tour-main-form" type="number" step="0.01" min="0" class="form-control" name="departure[price]" value="{{ old('departure.price') }}"></div>
      <div class="col-md-1"><label class="form-label">Currency</label><input form="tour-main-form" class="form-control" name="departure[currency]" value="{{ old('departure.currency', 'CAD') }}" maxlength="3"></div>
      <div class="col-md-2"><label class="form-label">Seats per date</label><input form="tour-main-form" type="number" min="1" class="form-control" name="departure[capacity]" value="{{ old('departure.capacity', 56) }}"></div>
      <div class="col-md-3"><label class="form-label">Status</label><select form="tour-main-form" class="form-select" name="departure[status]">@foreach($statuses as $key => [$label])<option value="{{ $key }}" @selected(old('departure.status', 'available') === $key)>{{ $label }}</option>@endforeach</select></div>
    </div>
    <div id="new-tour-dates">
      @foreach(old('dates', [[]]) as $i => $row)
        <div class="row g-2 mb-2 date-row">
          <div class="col-md-3"><label class="form-label small">Departure date & time</label><input form="tour-main-form" type="datetime-local" class="form-control" name="dates[{{ $i }}][departs_at]" value="{{ $row['departs_at'] ?? '' }}"></div>
          <div class="col-md-3"><label class="form-label small">Return <small class="text-muted">(multi-day)</small></label><input form="tour-main-form" type="datetime-local" class="form-control" name="dates[{{ $i }}][returns_at]" value="{{ $row['returns_at'] ?? '' }}"></div>
          <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-date" title="Remove this date"><i class="ri-close-line"></i></button></div>
        </div>
      @endforeach
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" data-add-date="#new-tour-dates" data-form="tour-main-form"><i class="ri-add-line"></i> Add another date</button>
    <p class="text-muted small mt-2 mb-0">Leave the date empty to create the tour without dates. Price lists (adult / child / rooms) and per-date pickup points can be set after saving.</p>
  @else
    @if($errors->has('departure'))<div class="alert alert-danger">{{ $errors->first('departure') }}</div>@endif

    @forelse($tour->departures as $departure)
      @php($booked = $departure->bookedSeats())
      <div class="border rounded p-3 mb-3" id="departure-{{ $departure->id }}">
        @can('assign-departure-staff')
        {{-- Who works this date. Assigning opens the trip to that person at once; removing closes it. --}}
        <div class="modal fade" id="staff-{{ $departure->id }}" tabindex="-1" aria-labelledby="staff-title-{{ $departure->id }}" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title" id="staff-title-{{ $departure->id }}">Trip staff · {{ $departure->departs_at->format('D, M j, Y') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
          <div class="modal-body">
            <table class="table crm-table mb-3">
              <tbody>
              @forelse($departure->staff as $s)
                <tr>
                  <td><span class="fw-semibold">{{ $s->user?->name }}</span><span class="sub">{{ collect([$s->user?->phone, $s->user?->email])->filter()->implode(' · ') }}</span></td>
                  <td><span class="badge bg-primary bg-opacity-10 text-primary"><i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $s->role_label }}</span>@if($s->note)<span class="sub">{{ $s->note }}</span>@endif</td>
                  <td class="text-end"><form method="post" action="{{ route('departure-staff.destroy', [$departure, $s]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Remove '.$s->user?->name.' from this date? They will no longer see this trip.') }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-user-unfollow-line"></i> Remove</button></form></td>
                </tr>
              @empty
                <tr><td class="crm-empty"><i class="ri-team-line"></i>Nobody assigned yet.</td></tr>
              @endforelse
              </tbody>
            </table>
            <form method="post" action="{{ route('departure-staff.store', $departure) }}" class="row g-3 align-items-end crm-filters border-top pt-3">@csrf
              <div class="col-md-5"><label class="form-label" for="staff-user-{{ $departure->id }}">Person</label><select class="form-select" id="staff-user-{{ $departure->id }}" name="user_id" required><option value="">Choose…</option>@foreach($staffUsers as $u)<option value="{{ $u->id }}">{{ $u->name }}{{ $u->roles->isNotEmpty() ? ' — '.$u->roles->pluck('name')->implode(', ') : '' }}</option>@endforeach</select></div>
              <div class="col-md-3"><label class="form-label" for="staff-role-{{ $departure->id }}">Works as</label><select class="form-select" id="staff-role-{{ $departure->id }}" name="role">@foreach(\App\Models\DepartureStaff::ROLES as $value => [$label])<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
              <div class="col-md-4"><label class="form-label" for="staff-note-{{ $departure->id }}">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="staff-note-{{ $departure->id }}" name="note" maxlength="255" placeholder="e.g. Bus 2, joins at Mississauga"></div>
              <div class="col-12 d-flex justify-content-between align-items-center gap-2"><span class="text-muted">They see this trip under <b>My trips</b> as soon as you save. @can('create-users')No account? <a href="{{ route('users.create') }}">Create one</a> with the Tour Leader role.@endcan</span><button class="btn btn-primary text-nowrap"><i class="ri-user-add-line me-1"></i>Assign</button></div>
            </form>
          </div>
        </div></div></div>
        @endcan
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
          <div>
            <strong>{{ \App\Support\DepartureCalendar::range($departure) }}</strong> <span class="text-muted">· {{ $departure->departs_at->format('g:i A') }}</span>
            <span class="badge bg-{{ $badge[$departure->public_status] ?? 'secondary' }} ms-1">{{ $departure->status_label }}</span>
            @if($departure->departs_at->isPast())<span class="badge bg-light text-dark ms-1">Past</span>@endif
            <div class="small text-muted mt-1">
              Seats: <b>{{ $booked }}</b> booked + <b>{{ $departure->held_seats }}</b> held of <b>{{ $departure->capacity }}</b> → <b class="{{ $departure->available_seats ? '' : 'text-danger' }}">{{ $departure->available_seats }} left</b>
              · Pickups: {{ $departure->pickupPoints->isNotEmpty() ? $departure->pickupPoints->pluck('name')->implode(', ') : 'same as the tour' }}
            </div>
            <div class="mt-1">
              <i class="ri-team-line text-muted me-1"></i>
              @forelse($departure->staff as $s)
                <span class="badge bg-primary bg-opacity-10 text-primary me-1" title="{{ $s->role_label }}"><i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $s->user?->name }}</span>
              @empty
                <span class="text-muted">No leader or driver assigned</span>
              @endforelse
            </div>
          </div>
          <div class="d-flex gap-1">
            @can('assign-departure-staff')<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#staff-{{ $departure->id }}"><i class="ri-team-line"></i> Staff ({{ $departure->staff->count() }})</button>@endcan
            @if($departure->staff->isNotEmpty() || auth()->user()->can('list-bookings'))<a class="btn btn-sm btn-outline-secondary" href="{{ route('my-trips.show', $departure) }}" title="What the leader sees"><i class="ri-map-2-line"></i> Trip page</a>@endif
            @can('list-bookings')<a class="btn btn-sm btn-outline-primary" href="{{ route('tour-departures.passengers', $departure) }}"><i class="ri-group-line"></i> Passengers ({{ $booked }})</a>@endcan
            @can('delete-tour-departures')<form method="post" action="{{ route('tour-departures.destroy', [$tour, $departure]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this date?')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          </div>
        </div>

        <div class="mt-2 small">
          @forelse($departure->prices as $price)
            <span class="d-inline-flex align-items-center gap-1 me-3">{{ $price->label }}: <b>{{ money($price->price, $departure->currency) }}</b>
              @can('edit-tour-departures')<form method="post" action="{{ route('tour-departure-prices.destroy', [$departure, $price]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0" title="Remove" onclick="return confirm('Remove this price?')"><i class="ri-close-line"></i></button></form>@endcan
            </span>
          @empty
            {{ money($departure->price, $departure->currency) }} <span class="text-muted">per person</span>
          @endforelse
          @can('edit-tour-departures')
            <form class="d-inline-flex gap-1 mt-1" method="post" action="{{ route('tour-departure-prices.store', $departure) }}">@csrf
              <select class="form-select form-select-sm" name="code" style="width:150px">@foreach(\App\Models\TourDeparturePrice::CODES as $code => [$label])<option value="{{ $code }}">{{ $tour->priceLabel($code) }}</option>@endforeach</select>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm" style="width:90px" name="price" placeholder="Price" required>
              <div class="form-check form-check-inline align-self-center mb-0 me-0"><input class="form-check-input" type="checkbox" id="apply-all-{{ $departure->id }}" name="apply_to_all" value="1"><label class="form-check-label small text-nowrap" for="apply-all-{{ $departure->id }}">all dates</label></div>
              <button class="btn btn-sm btn-outline-primary">Add price</button>
            </form>
          @endcan
        </div>

        @can('edit-tour-departures')
          <details class="mt-2" @if($errors->has('capacity.'.$departure->id)) open @endif>
            <summary class="text-primary small" style="cursor:pointer">Edit this date</summary>
            <form class="row g-2 mt-1" method="post" action="{{ route('tour-departures.update', [$tour, $departure]) }}">@csrf @method('PUT')
              <div class="col-md-3"><label class="form-label small">Departure</label><input type="datetime-local" class="form-control" name="departs_at" value="{{ $departure->departs_at?->format('Y-m-d\TH:i') }}" required></div>
              <div class="col-md-3"><label class="form-label small">Return</label><input type="datetime-local" class="form-control" name="returns_at" value="{{ $departure->returns_at?->format('Y-m-d\TH:i') }}"></div>
              <div class="col-md-2"><label class="form-label small">Base price</label><input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ $departure->price }}" required></div>
              <div class="col-md-2"><label class="form-label small">Old price</label><input type="number" step="0.01" min="0" class="form-control" name="old_price" value="{{ $departure->old_price }}"></div>
              <div class="col-md-2"><label class="form-label small">Currency</label><input class="form-control" name="currency" value="{{ $departure->currency }}" maxlength="3" required></div>
              <div class="col-md-2"><label class="form-label small">Seat capacity</label><input type="number" min="1" class="form-control" name="capacity" value="{{ $departure->capacity }}" required></div>
              <div class="col-md-2"><label class="form-label small">Seats held offline</label><input type="number" min="0" class="form-control" name="held_seats" value="{{ $departure->held_seats }}" title="Seats taken outside the website (phone bookings, staff, guides)"></div>
              <div class="col-md-4"><label class="form-label small">Status</label><select class="form-select" name="status">@foreach($statuses as $key => [$label])<option value="{{ $key }}" @selected($departure->status === $key)>{{ $label }}</option>@endforeach</select></div>
              <div class="col-md-4"><label class="form-label small">Pickup points for this date <small class="text-muted">(empty = tour's)</small></label>
                <select class="form-select js-select2-multi" multiple name="pickup_point_ids[]" data-placeholder="Same as the tour">@foreach($pickupPoints as $point)<option value="{{ $point->id }}" @selected($departure->pickupPoints->contains('id', $point->id))>{{ $point->name }}</option>@endforeach</select></div>
              <div class="col-12"><button class="btn btn-primary btn-sm"><i class="ri-save-line"></i> Save date</button>
                @error('capacity.'.$departure->id)<span class="text-danger small ms-2">{{ $message }}</span>@enderror</div>
            </form>
          </details>
        @endcan
      </div>
    @empty
      <p class="text-muted">No dates yet — add the first ones below.</p>
    @endforelse

    @can('create-tour-departures')
      <div class="border rounded p-3 bg-light" id="add-dates">
        <h6>Add dates</h6>
        <form method="post" action="{{ route('tour-departures.store', $tour) }}">@csrf
          <div class="row g-2 mb-2">
            <div class="col-md-2"><label class="form-label small">Base price</label><input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $tour->departures->last()?->price) }}" required></div>
            <div class="col-md-1"><label class="form-label small">Currency</label><input class="form-control" name="currency" value="{{ old('currency', $tour->departures->last()?->currency ?? 'CAD') }}" maxlength="3" required></div>
            <div class="col-md-2"><label class="form-label small">Seats per date</label><input type="number" min="1" class="form-control" name="capacity" value="{{ old('capacity', $tour->departures->last()?->capacity ?? 56) }}" required></div>
            <div class="col-md-3"><label class="form-label small">Status</label><select class="form-select" name="status">@foreach($statuses as $key => [$label])<option value="{{ $key }}" @selected(old('status', 'available') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small">Pickup points <small class="text-muted">(empty = tour's)</small></label>
              <select class="form-select js-select2-multi" multiple name="pickup_point_ids[]" data-placeholder="Same as the tour">@foreach($pickupPoints as $point)<option value="{{ $point->id }}">{{ $point->name }}</option>@endforeach</select></div>
          </div>
          <div id="more-dates">
            @foreach(old('dates', [[]]) as $i => $row)
              <div class="row g-2 mb-2 date-row">
                <div class="col-md-3"><label class="form-label small">Departure date & time</label><input type="datetime-local" class="form-control" name="dates[{{ $i }}][departs_at]" value="{{ $row['departs_at'] ?? '' }}" required></div>
                <div class="col-md-3"><label class="form-label small">Return <small class="text-muted">(multi-day)</small></label><input type="datetime-local" class="form-control" name="dates[{{ $i }}][returns_at]" value="{{ $row['returns_at'] ?? '' }}"></div>
                <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-date" title="Remove this date"><i class="ri-close-line"></i></button></div>
              </div>
            @endforeach
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm" data-add-date="#more-dates"><i class="ri-add-line"></i> Another date</button>
          <button class="btn btn-primary btn-sm ms-2"><i class="ri-calendar-check-line"></i> Save dates</button>
          <p class="text-muted small mt-2 mb-0">Adult / child / infant / room prices can be added to each date after saving (an infant price of 0 = free) (use “all dates” to copy a price to every date).</p>
        </form>
      </div>
    @endcan
  @endif
</div></div>
<script>
  // Repeatable date rows: clone the last row, clear it and renumber the dates[n] names.
  document.addEventListener('click', function (e) {
    const add = e.target.closest('[data-add-date]');
    if (add) {
      const box = document.querySelector(add.dataset.addDate);
      const rows = box.querySelectorAll('.date-row');
      const row = rows[rows.length - 1].cloneNode(true);
      const n = rows.length;
      row.querySelectorAll('input').forEach(function (input) { input.value = ''; input.name = input.name.replace(/dates\[\d+\]/, 'dates[' + n + ']'); });
      box.appendChild(row);
      row.querySelector('input').focus();
    }
    const remove = e.target.closest('.remove-date');
    if (remove) {
      const box = remove.closest('#more-dates, #new-tour-dates');
      if (box.querySelectorAll('.date-row').length > 1) remove.closest('.date-row').remove();
      else remove.closest('.date-row').querySelectorAll('input').forEach(function (input) { input.value = ''; });
    }
  });
</script>
