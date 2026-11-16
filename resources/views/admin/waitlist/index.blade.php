@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php($tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Waitlist</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Waitlist</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <ul class="nav nav-tabs border-0">
    <li class="nav-item"><a class="nav-link {{ $f['show'] === 'active' ? 'active' : '' }}" href="{{ route('waitlist.index', array_filter(['departure' => $f['departure']])) }}">Waiting</a></li>
    <li class="nav-item"><a class="nav-link {{ $f['show'] === 'closed' ? 'active' : '' }}" href="{{ route('waitlist.index', array_filter(['departure' => $f['departure'], 'show' => 'closed'])) }}">Booked & removed</a></li>
  </ul>
  <div class="d-flex gap-2">
    @if($f['departure'])<a class="btn btn-light" href="{{ route('waitlist.index', array_filter(['show' => $f['show'] === 'closed' ? 'closed' : null])) }}">All dates</a>@endif
    @can('edit-bookings')<button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#add-waitlist" aria-expanded="false"><i class="ri-user-add-line me-1"></i>Add to waitlist</button>@endcan
  </div>
</div>
<p class="text-muted">Guests can join the waitlist of a full date from the tour page. When seats free up you get an alert about the first person whose party fits: offer the seat (by phone, or by email), then press <b>Book</b> — the booking form opens with their details and the entry closes once the booking is saved.</p>

@can('edit-bookings')
<div class="collapse {{ $errors->any() ? 'show' : '' }}" id="add-waitlist"><div class="card crm-filters"><div class="card-body">
  <form method="post" action="{{ route('waitlist.store') }}" class="row g-3 align-items-end">@csrf
    <div class="col-md-6 col-xl-4"><label class="form-label" for="w-dep">Date</label><select class="form-select" id="w-dep" name="tour_departure_id" required><option value="">Choose…</option>@foreach($departures->groupBy(fn ($d) => $d->tour?->name) as $tourName => $dates)<optgroup label="{{ $tourName }}">@foreach($dates as $d)<option value="{{ $d->id }}" @selected(old('tour_departure_id', $f['departure']) == $d->id)>{{ $d->departs_at->format('D, M j, Y') }} · {{ max(0, $d->available_seats) }} left</option>@endforeach</optgroup>@endforeach</select></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="w-first">First name</label><input class="form-control" id="w-first" name="first_name" value="{{ old('first_name') }}" required maxlength="100"></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="w-last">Last name</label><input class="form-control" id="w-last" name="last_name" value="{{ old('last_name') }}" required maxlength="100"></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="w-email">Email</label><input class="form-control" type="email" id="w-email" name="email" value="{{ old('email') }}"></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="w-phone">Phone</label><input class="form-control" id="w-phone" name="phone" value="{{ old('phone') }}" maxlength="30"></div>
    <div class="col-sm-4 col-xl-1"><label class="form-label" for="w-guests">Seats</label><input class="form-control" type="number" min="1" max="50" id="w-guests" name="guests" value="{{ old('guests', 1) }}" required></div>
    <div class="col-sm-8 col-xl-7"><label class="form-label" for="w-note">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="w-note" name="note" value="{{ old('note') }}" maxlength="500" placeholder="e.g. Flexible on the date"></div>
    <div class="col-xl-4"><button class="btn btn-primary w-100"><i class="ri-add-line me-1"></i>Add</button></div>
  </form>
</div></div></div>
@endcan

@forelse($groups as $entries)
  @php($d = $entries->first()->departure)
  @php($left = max(0, $d->available_seats))
  <div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
      <div><h5 class="mb-0">{{ $d->tour?->name }}</h5><span class="text-muted">{{ $d->departs_at->format('D, M j, Y g:i A') }} · {{ $entries->count() }} {{ $f['show'] === 'active' ? 'waiting' : 'closed' }}</span></div>
      <div class="d-flex gap-2 align-items-center">
        <span class="badge rounded-pill {{ $left ? 'bg-success' : 'bg-light text-body' }} fs-6">{{ $left }} {{ str('seat')->plural($left) }} free now</span>
        <a class="btn btn-sm btn-light" href="{{ route('tour-departures.passengers', $d) }}"><i class="ri-group-line"></i> Passengers</a>
      </div>
    </div>
    <div class="table-responsive"><table class="table crm-table mb-0">
      <thead><tr><th>#</th><th>Guest</th><th class="text-end">Seats</th><th>Joined</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @foreach($entries as $i => $e)
        @php([$sLabel, $sColour] = \App\Models\WaitlistEntry::STATUSES[$e->status])
        <tr class="{{ $f['show'] === 'active' && $e->guests <= $left ? 'table-success' : '' }}">
          <td>{{ $f['show'] === 'active' ? $i + 1 : '—' }}</td>
          <td><span class="fw-semibold">{{ $e->full_name }}</span><span class="sub">{{ collect([$e->email, $e->phone])->filter()->implode(' · ') }}@if($e->note) · “{{ str($e->note)->limit(60) }}”@endif</span></td>
          <td class="amount fw-normal">{{ $e->guests }}</td>
          <td class="text-nowrap">{{ $e->created_at->timezone($tz)->format('M j, g:i A') }}<span class="sub">{{ $e->source === 'website' ? 'from the website' : 'added by staff' }}</span></td>
          <td><span class="badge rounded-pill bg-{{ $sColour }} {{ $sColour === 'warning' ? 'text-dark' : '' }}">{{ $sLabel }}</span>
            @if($e->offered_at)<span class="sub">{{ $e->offered_at->timezone($tz)->format('M j, g:i A') }}{{ $e->offeredBy ? ' · '.$e->offeredBy->name : '' }}</span>@endif
            @if($e->booking)<span class="sub"><a href="{{ route('bookings.show', $e->booking) }}">{{ $e->booking->reference }}</a></span>@endif
            @if($e->removed_reason)<span class="sub">{{ $e->removed_reason }}</span>@endif</td>
          <td class="text-end text-nowrap">
            @if($f['show'] === 'active')
              @can('edit-bookings')
                <form class="d-inline" method="post" action="{{ route('waitlist.move', $e) }}">@csrf<input type="hidden" name="direction" value="up"><button class="btn btn-sm btn-light" title="Move up" @disabled($i === 0)><i class="ri-arrow-up-line"></i></button></form>
                <form class="d-inline" method="post" action="{{ route('waitlist.move', $e) }}">@csrf<input type="hidden" name="direction" value="down"><button class="btn btn-sm btn-light" title="Move down" @disabled($loop->last)><i class="ri-arrow-down-line"></i></button></form>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Offer</button>
                  <div class="dropdown-menu dropdown-menu-end">
                    <form method="post" action="{{ route('waitlist.offer', $e) }}">@csrf<button class="dropdown-item"><i class="ri-phone-line me-1"></i>Offered by phone</button></form>
                    @if($e->email)<form method="post" action="{{ route('waitlist.offer', $e) }}">@csrf<input type="hidden" name="email" value="1"><button class="dropdown-item"><i class="ri-mail-send-line me-1"></i>Email the offer</button></form>@endif
                  </div>
                </div>
                @can('create-bookings')<a class="btn btn-sm btn-primary" href="{{ route('bookings.create', ['waitlist' => $e->id]) }}"><i class="ri-calendar-check-line"></i> Book</a>@endcan
                <form class="d-inline" method="post" action="{{ route('waitlist.remove', $e) }}" onsubmit="const r = prompt('Why remove {{ addslashes($e->full_name) }} from the waitlist?', 'No longer interested'); if (!r) return false; this.reason.value = r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-outline-danger" title="Remove"><i class="ri-close-line"></i></button></form>
              @endcan
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </div></div>
@empty
  <div class="card"><div class="card-body crm-empty"><i class="ri-list-ordered"></i>{{ $f['show'] === 'active' ? 'Nobody is waiting for a seat.' : 'No booked or removed entries yet.' }}</div></div>
@endforelse
</div></div></div>@include('admin.includes.footer')
