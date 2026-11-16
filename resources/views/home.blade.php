@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $user = auth()->user();
  $hour = (int) now($tz)->format('G');
@endphp
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div><h4 class="mb-1">Good {{ $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening') }}, {{ str($user->name)->before(' ') }}</h4><p class="text-muted mb-0">{{ now($tz)->format('l, F j, Y') }} · Toronto time</p></div>
  <a class="btn btn-light" href="{{ route('front.home') }}" target="_blank"><i class="ri-external-link-line me-1"></i>Open website</a>
</div>
@include('admin.includes.flash')

@if($dash['tiles'])
{{-- Every number opens the records behind it (CRM §2). --}}
<div class="row g-3 mb-3">
  @foreach($dash['tiles'] as [$label, $value, $note, $colour, $icon, $url])
    <div class="col-sm-6 col-xl-3"><a href="{{ $url }}" class="crm-tile" style="border-top:3px solid var(--bs-{{ $colour }})">
      <div class="label"><i class="{{ $icon }} me-1"></i>{{ $label }}</div>
      <div class="value {{ in_array($colour, ['danger', 'warning'], true) && $value ? 'text-'.$colour : '' }}">{{ $value }}</div>
      <div class="note">{{ $note }} <i class="ri-arrow-right-s-line"></i></div>
    </a></div>
  @endforeach
</div>
@endif

<div class="row g-3">
  @if($dash['trips'] !== null)
  <div class="col-xl-8">
    <div class="card" id="trips" style="scroll-margin-top:80px"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title mb-0"><i class="ri-bus-line me-1"></i>Trips in the next {{ \App\Support\Dashboard::TRIP_DAYS }} days</h5>@can('list-vehicle-layouts')<a href="{{ route('vehicle-layouts.index') }}">Seating overview</a>@endcan</div>
      <div class="table-responsive"><table class="table table-hover crm-table mb-0">
        <thead><tr><th>Date</th><th>Tour</th><th style="min-width:160px">Occupancy</th><th class="text-end">Seats left</th>@if($user->can('view-financial-info'))<th class="text-end">Unpaid</th>@endif<th>Seating</th><th></th></tr></thead>
        <tbody>
        @forelse($dash['trips'] as $d)
          @php
            $taken = $d->capacity - $d->available_seats;
            $pct = $d->capacity ? min(100, round($taken / $d->capacity * 100)) : 0;
            $stage = \App\Http\Controllers\Admin\VehicleLayoutController::stage($d);
            [$sLabel, $sColour] = [[0 => ['No bus yet', 'danger'], 1 => ['Seats to assign', 'warning'], 2 => ['Draft', 'warning'], 3 => ['Published', 'success'], 4 => ['—', 'light']][$stage][0], [0 => 'danger', 1 => 'warning', 2 => 'warning', 3 => 'success', 4 => 'light'][$stage]];
          @endphp
          <tr>
            <td class="text-nowrap"><span class="fw-semibold">{{ $d->departs_at->format('D, M j') }}</span><span class="sub">{{ $d->departs_at->format('g:i A') }} · {{ $d->departs_at->isToday() ? 'today' : ($d->departs_at->isTomorrow() ? 'tomorrow' : $d->departs_at->startOfDay()->diffForHumans(now()->startOfDay(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW])) }}</span></td>
            <td>{{ str($d->tour?->name)->limit(38) }}@if($d->staff->isNotEmpty())<span class="sub"><i class="ri-flag-line"></i> {{ $d->staff->map(fn ($s) => $s->user?->name)->filter()->unique()->implode(', ') }}</span>@endif</td>
            <td><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar bg-{{ $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success') }}" style="width:{{ $pct }}%"></div></div><span class="text-nowrap">{{ $d->passengers_count }} / {{ $d->capacity }}</span></div></td>
            <td class="amount fw-normal {{ $d->available_seats ? '' : 'text-danger' }}">{{ $d->available_seats }}</td>
            @if($user->can('view-financial-info'))<td class="amount fw-normal {{ $d->unpaid_count ? 'text-danger' : 'text-muted' }}">@if($d->unpaid_count)<a class="text-danger" href="{{ route('payments.index', ['tab' => 'outstanding', 'trip_from' => $d->departs_at->toDateString(), 'trip_to' => $d->departs_at->toDateString(), 'tour' => $d->tour_id]) }}">{{ $d->unpaid_count }}</a>@else 0 @endif</td>@endif
            <td>@if($d->passengers_count)<span class="badge rounded-pill bg-{{ $sColour }} {{ $sColour === 'warning' ? 'text-dark' : '' }}">{{ $sLabel }}</span>@else<span class="text-muted">—</span>@endif</td>
            <td class="text-end text-nowrap"><a class="btn btn-sm btn-light" href="{{ route('tour-departures.passengers', $d) }}" title="Passenger list"><i class="ri-group-line"></i></a> <a class="btn btn-sm btn-light" href="{{ route('seating.show', $d) }}" title="Seating"><i class="ri-layout-grid-line"></i></a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="crm-empty"><i class="ri-calendar-line"></i>No departures in the next {{ \App\Support\Dashboard::TRIP_DAYS }} days.</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div></div>
  </div>
  @endif

  <div class="{{ $dash['trips'] !== null ? 'col-xl-4' : 'col-xl-6' }}">
    @if($dash['followUps'] !== null)
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title mb-0"><i class="ri-alarm-line me-1"></i>Follow-ups due</h5><a href="{{ route('leads.index', ['view' => 'list', 'due' => 1]) }}">All</a></div>
      @forelse($dash['followUps'] as $lead)
        <a href="{{ route('leads.show', $lead) }}" class="d-flex justify-content-between gap-2 text-body {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
          <span><span class="fw-semibold">{{ $lead->full_name }}</span><span class="d-block text-muted">{{ $lead->tour?->name ? str($lead->tour->name)->limit(30) : ($lead->follow_up_note ?: 'Follow up') }}</span></span>
          <span class="text-end text-nowrap"><span class="text-danger">{{ $lead->next_follow_up_at->timezone($tz)->isToday() ? $lead->next_follow_up_at->timezone($tz)->format('g:i A') : $lead->next_follow_up_at->timezone($tz)->format('M j') }}</span><span class="d-block text-muted">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</span></span>
        </a>
      @empty
        <div class="crm-empty py-2"><i class="ri-check-double-line"></i>Nothing due. Nice.</div>
      @endforelse
    </div></div>
    @endif

    @if($dash['payments'] !== null)
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line me-1"></i>Received in the last {{ \App\Support\Dashboard::RECENT_PAYMENT_DAYS }} days</h5><a href="{{ $dash['payments']['recentUrl'] }}">{{ money($dash['payments']['recentTotal']) }}</a></div>
      @forelse($dash['payments']['recent'] as $p)
        <div class="d-flex justify-content-between gap-2 {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
          <span><a href="{{ route('bookings.show', $p->booking_id) }}" class="fw-semibold">{{ $p->booking?->reference }}</a> · {{ trim($p->booking?->first_name.' '.$p->booking?->last_name) }}<span class="d-block text-muted">{{ $p->paid_on->format('M j') }} · {{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}</span></span>
          <span class="amount">{{ money($p->amount, $p->currency) }}</span>
        </div>
      @empty
        <div class="crm-empty py-2"><i class="ri-inbox-line"></i>No payments confirmed this week.</div>
      @endforelse
    </div></div>
    @endif
  </div>

  {{-- Website content shortcuts, each only for people who can manage it. --}}
  @php
    $content = array_filter([
      $user->can('list-tours') ? ['Tours', $stats['tours'], $stats['published_tours'].' published', route('tours.index'), 'ri-suitcase-line'] : null,
      $user->can('list-pages') ? ['CMS pages', $stats['pages'], 'Pages and sections', route('cms-pages.index'), 'ri-pages-line'] : null,
      $user->can('list-gallery-albums') ? ['Gallery albums', $stats['gallery_albums'], 'Albums and media', route('domain-content.index', 'gallery-albums'), 'ri-gallery-line'] : null,
      $user->can('list-visa-services') ? ['Visa services', $stats['visa_services'], 'Countries and requirements', route('domain-content.index', 'visa-services'), 'ri-passport-line'] : null,
      $user->can('list-contacts') ? ['Contact requests', $stats['contacts'], $stats['unread_contacts'].' unread', route('contacts.index'), 'ri-mail-line'] : null,
      $user->can('list-users') ? ['Users', $stats['users'], 'Staff accounts', route('users.index'), 'ri-user-line'] : null,
    ]);
  @endphp
  @if($content)
  <div class="col-12"><div class="card"><div class="card-body">
    <h5 class="card-title mb-3"><i class="ri-layout-masonry-line me-1"></i>Website & content</h5>
    <div class="row g-3">
      @foreach($content as [$label, $value, $note, $url, $icon])
        <div class="col-6 col-md-4 col-xl-2"><a href="{{ $url }}" class="crm-tile"><div class="label"><i class="{{ $icon }} me-1"></i>{{ $label }}</div><div class="value">{{ $value }}</div><div class="note">{{ $note }}</div></a></div>
      @endforeach
    </div>
  </div></div></div>
  @endif
</div>
</div></div></div>@include('admin.includes.footer')
