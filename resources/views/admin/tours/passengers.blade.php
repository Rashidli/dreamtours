@include('admin.includes.header')
@php
  $tour = $departure->tour;
  $booked = $departure->bookedPassengers();
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Passengers — {{ $tour->name }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('tours.edit', $tour) }}#departures">{{ $tour->name }}</a></li><li class="breadcrumb-item active">{{ $departure->departs_at->format('M j, Y') }}</li></ol></div>
  </div></div></div>

  <div class="row">
    @foreach([['Date', \App\Support\DepartureCalendar::range($departure).' · '.$departure->departs_at->format('g:i A')], ['Seat capacity', $departure->capacity], ['Booked passengers', $booked], ['Held offline', $departure->held_seats], ['Seats left', $departure->available_seats], ['Bookings', $departure->bookings->where('status', '!=', 'cancelled')->count()]] as [$label, $value])
      <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">{{ $label }}</p><h5 class="mb-0">{{ $value }}</h5></div></div></div>
    @endforeach
  </div>

  <div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
      <h5 class="mb-0">{{ $passengers->count() }} {{ str('passenger')->plural($passengers->count()) }} <span class="badge bg-{{ $departure->is_bookable ? 'success' : 'secondary' }}">{{ $departure->status_label }}</span></h5>
      <div class="d-flex gap-2">
        <form class="d-flex gap-2"><select class="form-select form-select-sm" name="pickup" onchange="this.form.submit()"><option value="">All pickup points</option>@foreach($pickups as $p)<option value="{{ $p->id }}" @selected(request('pickup') === (string) $p->id)>{{ $p->name }}</option>@endforeach</select></form>
        <button class="btn btn-sm btn-light" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
        <a class="btn btn-sm btn-light" href="{{ route('tours.edit', $tour) }}#departure-{{ $departure->id }}"><i class="ri-arrow-left-line"></i> Back to dates</a>
      </div>
    </div>
    <div class="table-responsive mt-3"><table class="table table-sm align-middle">
      <thead><tr><th>#</th><th>Passenger</th><th>Pickup point</th><th>Meal</th><th>Seat</th><th>Booking</th><th>Contact</th><th>Payment</th></tr></thead>
      <tbody>
      @forelse($passengers as $i => $row)
        @php([$p, $b] = [$row['passenger'], $row['booking']])
        <tr>
          <td>{{ $i + 1 }}</td>
          <td><strong>{{ $p->first_name }} {{ $p->last_name }}</strong>@if($p->category && $p->category !== 'adult') <small class="text-muted">({{ $p->category }})</small>@endif</td>
          <td>{{ $p->pickupPoint?->name ?? '—' }}</td>
          <td>{{ $p->meal_selection ?: '—' }}</td>
          <td>{{ $p->seat_number ?: '—' }}</td>
          <td><a href="{{ route('bookings.show', $b) }}">{{ $b->reference }}</a><br><small class="text-muted">{{ \App\Models\Booking::STATUSES[$b->status] ?? $b->status }}</small></td>
          <td><small>{{ $b->first_name }} {{ $b->last_name }}<br>{{ $b->phone }}</small></td>
          <td><small>{{ \App\Models\Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status }}@if($b->total_amount !== null)<br>balance {{ money($b->balance_due, $b->currency) }}@endif</small></td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-muted">No passengers on this date yet.</td></tr>
      @endforelse
      </tbody>
    </table></div>
    @if($passengers->isNotEmpty())
      <p class="small text-muted mb-0">By pickup point: {{ $passengers->groupBy(fn ($r) => $r['passenger']->pickupPoint?->name ?? 'Not set')->map->count()->map(fn ($n, $name) => "$name: $n")->implode(' · ') }}</p>
    @endif
    @if($cancelled->isNotEmpty())
      <p class="small text-muted mt-2 mb-0">Cancelled bookings (not counted): {{ $cancelled->map(fn ($b) => $b->reference.' ('.$b->participant_count.')')->implode(', ') }}</p>
    @endif
  </div></div>
</div></div></div>
@include('admin.includes.footer')
