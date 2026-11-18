{{-- Departure dates of a tour. On "New tour" the first dates are part of the main form; on edit each date is managed on its own. --}}
@php
  $statuses = \App\Models\TourDeparture::STATUSES;
  $staffUsers = auth()->user()->can('assign-departure-staff') ? \App\Models\User::with('roles')->orderBy('name')->get() : collect();
  $badge = ['available' => 'success', 'limited' => 'warning', 'sold_out' => 'danger', 'closed' => 'secondary', 'hidden' => 'dark', 'cancelled' => 'secondary'];
  $upcomingCount = $tour->exists ? $tour->departures->filter(fn ($d) => $d->departs_at?->isFuture())->count() : 0;
  $pastCount = $tour->exists ? $tour->departures->count() - $upcomingCount : 0;
@endphp
<div class="card tf-section" id="departures"><div class="card-body">
  <div class="tf-head">
    <span class="tf-icon"><i class="ri-calendar-event-line"></i></span>
    <div class="flex-grow-1"><h5>Dates & prices</h5><p>One trip, many dates: the description, itinerary and photos are shared; each date has its own seats, prices, status and pick-up points. Visitors pick a date in the calendar on the tour page.</p></div>
    @if($tour->exists)<div class="d-flex flex-wrap gap-2 align-items-center flex-shrink-0"><span class="badge bg-success bg-opacity-10 text-success">{{ $upcomingCount }} upcoming</span>@if($pastCount)<span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $pastCount }} past</span>@endif @can('create-tour-departures')<a href="#add-dates" class="btn btn-outline-primary btn-sm"><i class="ri-add-line"></i> Add dates</a>@endcan</div>@endif
  </div>

  @if(!$tour->exists)
    {{-- First dates, saved together with the new tour --}}
    <div class="tf-subtle">
      <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3"><label class="form-label fw-semibold">Price per person</label><input form="tour-main-form" type="number" step="0.01" min="0" class="form-control" name="departure[price]" value="{{ old('departure.price') }}" placeholder="0.00"></div>
        <div class="col-sm-6 col-md-2"><label class="form-label fw-semibold">Currency</label><input form="tour-main-form" class="form-control" name="departure[currency]" value="{{ old('departure.currency', 'CAD') }}" maxlength="3"></div>
        <div class="col-sm-6 col-md-3"><label class="form-label fw-semibold">Seats per date</label><input form="tour-main-form" type="number" min="1" class="form-control" name="departure[capacity]" value="{{ old('departure.capacity', 56) }}"></div>
        <div class="col-sm-6 col-md-4"><label class="form-label fw-semibold">Status</label><select form="tour-main-form" class="form-select" name="departure[status]">@foreach($statuses as $key => [$label])<option value="{{ $key }}" @selected(old('departure.status', 'available') === $key)>{{ $label }}</option>@endforeach</select></div>
      </div>
      <div id="new-tour-dates">
        @foreach(old('dates', [[]]) as $i => $row)
          <div class="row g-3 mb-2 date-row align-items-end">
            <div class="col-sm-5"><label class="form-label fw-semibold">Departure date & time</label><input form="tour-main-form" type="datetime-local" class="form-control" name="dates[{{ $i }}][departs_at]" value="{{ $row['departs_at'] ?? '' }}"></div>
            <div class="col-sm-5"><label class="form-label fw-semibold">Return <span class="text-muted fw-normal">(multi-day)</span></label><input form="tour-main-form" type="datetime-local" class="form-control" name="dates[{{ $i }}][returns_at]" value="{{ $row['returns_at'] ?? '' }}"></div>
            <div class="col-sm-2"><button type="button" class="btn btn-outline-danger w-100 remove-date" title="Remove this date" aria-label="Remove this date"><i class="ri-close-line"></i></button></div>
          </div>
        @endforeach
      </div>
      <button type="button" class="btn btn-outline-primary mt-1" data-add-date="#new-tour-dates" data-form="tour-main-form"><i class="ri-add-line"></i> Add another date</button>
      <div class="form-text mt-2">Leave the date empty to create the tour without dates. Adult / child / room prices and per-date pick-up points can be set after saving.</div>
    </div>
  @else
    @if($errors->has('departure'))<div class="alert alert-danger">{{ $errors->first('departure') }}</div>@endif

    @forelse($tour->departures as $departure)
      @php($booked = $departure->bookedSeats())
      @php($taken = min($departure->capacity, $booked + $departure->held_seats))
      <div class="tf-date @if($departure->departs_at->isPast()) tf-date-past @endif" id="departure-{{ $departure->id }}">
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
                  <td><span class="badge bg-primary bg-opacity-10 text-primary"><i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $s->role_label }}</span>@if($s->vehicle) <span class="badge bg-light text-body">{{ $s->vehicle->label }}</span>@endif @if($s->note)<span class="sub">{{ $s->note }}</span>@endif</td>
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
              @if($departure->vehicles->isNotEmpty())<div class="col-md-4"><label class="form-label" for="staff-bus-{{ $departure->id }}">Bus <span class="text-muted fw-normal">(optional)</span></label><select class="form-select" id="staff-bus-{{ $departure->id }}" name="departure_vehicle_id"><option value="">Whole trip</option>@foreach($departure->vehicles as $v)<option value="{{ $v->id }}">{{ $v->label }}</option>@endforeach</select></div>@endif
              <div class="col-md-4"><label class="form-label" for="staff-note-{{ $departure->id }}">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="staff-note-{{ $departure->id }}" name="note" maxlength="255" placeholder="e.g. Bus 2, joins at Mississauga"></div>
              <div class="col-12 d-flex justify-content-between align-items-center gap-2"><span class="text-muted">They see this trip under <b>My trips</b> as soon as you save. @can('create-users')No account? <a href="{{ route('users.create') }}">Create one</a> with the Tour Leader role.@endcan</span><button class="btn btn-primary text-nowrap"><i class="ri-user-add-line me-1"></i>Assign</button></div>
            </form>
          </div>
        </div></div></div>
        @endcan
        <div class="tf-date-top">
          <div class="tf-date-tile" aria-hidden="true"><span>{{ $departure->departs_at->format('D') }}</span><strong>{{ $departure->departs_at->format('j') }}</strong><span>{{ $departure->departs_at->format('M Y') }}</span></div>
          <div class="tf-date-main">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold fs-6">{{ \App\Support\DepartureCalendar::range($departure) }}</span><span class="text-muted">· {{ $departure->departs_at->format('g:i A') }}</span>
              <span class="badge bg-{{ $badge[$departure->public_status] ?? 'secondary' }} bg-opacity-10 text-{{ $badge[$departure->public_status] ?? 'secondary' }}">{{ $departure->status_label }}</span>
              @if($departure->departs_at->isPast())<span class="badge bg-light text-dark">Past</span>@endif
            </div>
            <div class="tf-seats mt-2" title="{{ $booked }} booked, {{ $departure->held_seats }} held offline, {{ $departure->available_seats }} left of {{ $departure->capacity }}">
              <div class="progress" style="height:8px"><div class="progress-bar bg-primary" style="width:{{ $departure->capacity ? round($booked / $departure->capacity * 100) : 0 }}%"></div><div class="progress-bar bg-warning" style="width:{{ $departure->capacity ? round(max(0, $taken - $booked) / $departure->capacity * 100) : 0 }}%"></div></div>
              <div class="mt-1"><b>{{ $booked }}</b> booked · <b>{{ $departure->held_seats }}</b> held · <b class="{{ $departure->available_seats ? 'text-success' : 'text-danger' }}">{{ $departure->available_seats }} left</b> <span class="text-muted">of {{ $departure->capacity }}</span></div>
            </div>
            <div class="tf-date-meta mt-2">
              <span><i class="ri-map-pin-line"></i> {{ $departure->pickupPoints->isNotEmpty() ? $departure->pickupPoints->pluck('name')->implode(', ') : 'Pick-ups: same as the tour' }}</span>
              <span><i class="ri-team-line"></i>
              @forelse($departure->staff as $s)
                <span class="badge bg-primary bg-opacity-10 text-primary me-1" title="{{ $s->role_label }}"><i class="{{ \App\Models\DepartureStaff::ROLES[$s->role][1] ?? 'ri-user-line' }} me-1"></i>{{ $s->user?->name }}</span>
              @empty
                <span class="text-muted">No leader or driver assigned</span>
              @endforelse
              </span>
            </div>
          </div>
          <div class="tf-date-actions">
            @can('assign-departure-staff')<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#staff-{{ $departure->id }}"><i class="ri-team-line"></i> Staff ({{ $departure->staff->count() }})</button>@endcan
            @if(auth()->user()->can('list-bookings'))<a class="btn btn-sm btn-outline-primary" href="{{ route('seating.show', $departure) }}"><i class="ri-layout-grid-line"></i> Seating{{ $departure->vehicles->isNotEmpty() ? ' ('.$departure->vehicles->count().')' : '' }}</a>@endif
            @if($departure->staff->isNotEmpty() || auth()->user()->can('list-bookings'))<a class="btn btn-sm btn-outline-secondary" href="{{ route('my-trips.show', $departure) }}" title="What the leader sees"><i class="ri-map-2-line"></i> Trip page</a>@endif
            @can('list-bookings')<a class="btn btn-sm btn-outline-primary" href="{{ route('tour-departures.passengers', $departure) }}"><i class="ri-group-line"></i> Passengers ({{ $booked }})</a>@endcan
            @can('delete-tour-departures')<form method="post" action="{{ route('tour-departures.destroy', [$tour, $departure]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this date?')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          </div>
        </div>

        <div class="tf-prices">
          <span class="tf-label me-1">Prices</span>
          @forelse($departure->prices as $price)
            <span class="tf-price">{{ $price->label }} <b>{{ money($price->price, $departure->currency) }}</b>
              @can('edit-tour-departures')<form method="post" action="{{ route('tour-departure-prices.destroy', [$departure, $price]) }}" class="d-inline">@csrf @method('DELETE')<button class="tf-price-remove" title="Remove this price" aria-label="Remove {{ $price->label }} price" onclick="return confirm('Remove this price?')"><i class="ri-close-line"></i></button></form>@endcan
            </span>
          @empty
            <span class="tf-price">Per person <b>{{ money($departure->price, $departure->currency) }}</b></span>
          @endforelse
        </div>

        @can('edit-tour-departures')
          <div class="tf-date-tools">
            <form class="tf-price-add" method="post" action="{{ route('tour-departure-prices.store', $departure) }}">@csrf
              <select class="form-select" name="code" aria-label="Fare">@foreach(\App\Models\TourDeparturePrice::CODES as $code => [$label])<option value="{{ $code }}">{{ $tour->priceLabel($code) }}</option>@endforeach</select>
              <input type="number" step="0.01" min="0" class="form-control" name="price" placeholder="Price" aria-label="Price" required>
              <div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="apply-all-{{ $departure->id }}" name="apply_to_all" value="1"><label class="form-check-label text-nowrap" for="apply-all-{{ $departure->id }}">All dates</label></div>
              <button class="btn btn-outline-primary text-nowrap"><i class="ri-add-line"></i> Add price</button>
            </form>
            <details class="tf-details" @if($errors->has('capacity.'.$departure->id)) open @endif>
              <summary class="btn btn-outline-secondary"><i class="ri-pencil-line"></i> Edit date</summary>
              <form class="row g-3 tf-subtle mt-2 mx-0" method="post" action="{{ route('tour-departures.update', [$tour, $departure]) }}">@csrf @method('PUT')
                <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Departure</label><input type="datetime-local" class="form-control" name="departs_at" value="{{ $departure->departs_at?->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Return</label><input type="datetime-local" class="form-control" name="returns_at" value="{{ $departure->returns_at?->format('Y-m-d\TH:i') }}"></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label fw-semibold">Base price</label><input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ $departure->price }}" required></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label fw-semibold">Old price</label><input type="number" step="0.01" min="0" class="form-control" name="old_price" value="{{ $departure->old_price }}" placeholder="Crossed out"></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label fw-semibold">Currency</label><input class="form-control" name="currency" value="{{ $departure->currency }}" maxlength="3" required></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Seats</label><input type="number" min="1" class="form-control @error('capacity.'.$departure->id) is-invalid @enderror" name="capacity" value="{{ $departure->capacity }}" required></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Held offline</label><input type="number" min="0" class="form-control" name="held_seats" value="{{ $departure->held_seats }}" title="Seats taken outside the website (phone bookings, staff, guides)"></div>
                <div class="col-sm-6 col-lg-4"><label class="form-label fw-semibold">Status</label><select class="form-select" name="status">@foreach($statuses as $key => [$label])<option value="{{ $key }}" @selected($departure->status === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-4"><label class="form-label fw-semibold">Pick-up points <span class="text-muted fw-normal">(empty = tour's)</span></label>
                  <select class="form-select js-select2-multi" multiple name="pickup_point_ids[]" data-placeholder="Same as the tour">@foreach($pickupPoints as $point)<option value="{{ $point->id }}" @selected($departure->pickupPoints->contains('id', $point->id))>{{ $point->name }}</option>@endforeach</select></div>
                <div class="col-12 d-flex align-items-center gap-2"><button class="btn btn-primary"><i class="ri-save-line"></i> Save date</button>
                  @error('capacity.'.$departure->id)<span class="text-danger">{{ $message }}</span>@enderror</div>
              </form>
            </details>
          </div>
        @endcan
      </div>
    @empty
      <div class="crm-empty text-center text-muted py-4"><i class="ri-calendar-line fs-2 d-block mb-1"></i>No dates yet — add the first ones below.</div>
    @endforelse

    @can('create-tour-departures')
      <div class="tf-subtle tf-section mt-3" id="add-dates">
        <div class="tf-label fs-6 mb-3"><i class="ri-calendar-check-line text-primary me-1"></i> Add dates</div>
        <form method="post" action="{{ route('tour-departures.store', $tour) }}">@csrf
          <div class="row g-3 mb-3">
            <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Base price</label><input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $tour->departures->last()?->price) }}" required></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Currency</label><input class="form-control" name="currency" value="{{ old('currency', $tour->departures->last()?->currency ?? 'CAD') }}" maxlength="3" required></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Seats per date</label><input type="number" min="1" class="form-control" name="capacity" value="{{ old('capacity', $tour->departures->last()?->capacity ?? 56) }}" required></div>
            <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Status</label><select class="form-select" name="status">@foreach($statuses as $key => [$label])<option value="{{ $key }}" @selected(old('status', 'available') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-3"><label class="form-label fw-semibold">Pick-up points <span class="text-muted fw-normal">(empty = tour's)</span></label>
              <select class="form-select js-select2-multi" multiple name="pickup_point_ids[]" data-placeholder="Same as the tour">@foreach($pickupPoints as $point)<option value="{{ $point->id }}">{{ $point->name }}</option>@endforeach</select></div>
          </div>
          <div id="more-dates">
            @foreach(old('dates', [[]]) as $i => $row)
              <div class="row g-3 mb-2 date-row align-items-end">
                <div class="col-sm-5"><label class="form-label fw-semibold">Departure date & time</label><input type="datetime-local" class="form-control" name="dates[{{ $i }}][departs_at]" value="{{ $row['departs_at'] ?? '' }}" required></div>
                <div class="col-sm-5"><label class="form-label fw-semibold">Return <span class="text-muted fw-normal">(multi-day)</span></label><input type="datetime-local" class="form-control" name="dates[{{ $i }}][returns_at]" value="{{ $row['returns_at'] ?? '' }}"></div>
                <div class="col-sm-2"><button type="button" class="btn btn-outline-danger w-100 remove-date" title="Remove this date" aria-label="Remove this date"><i class="ri-close-line"></i></button></div>
              </div>
            @endforeach
          </div>
          <div class="d-flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-outline-primary" data-add-date="#more-dates"><i class="ri-add-line"></i> Another date</button><button class="btn btn-primary"><i class="ri-calendar-check-line"></i> Save dates</button></div>
          <div class="form-text mt-2">Adult / child / infant / room prices can be added to each date after saving (an infant price of 0 = free). Tick “All dates” to copy a price to every date.</div>
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
