@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $fin = auth()->user()->can('view-financial-info');
  $filtered = collect(request()->except(['page', 'sort']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
  $moreKeys = ['source', 'assigned', 'marketing', 'birthday_month', 'signup_from', 'signup_to', 'trip_from', 'trip_to', 'min_trips', 'min_spent', 'destination', 'outstanding', 'archived'];
  $moreOpen = collect(request()->only(['source', 'assigned', 'birthday_month', 'signup_from', 'signup_to', 'trip_from', 'trip_to', 'min_trips', 'min_spent', 'destination']))->filter()->isNotEmpty();
  $moreCount = collect(request()->only($moreKeys))->filter(fn ($v) => $v !== null && $v !== '')->count();

  // Query-string helpers for the view tabs and the removable filter chips.
  $query = collect(request()->query())->except(['page', 'export']);
  $clean = fn ($q) => collect($q)->filter(fn ($v) => $v !== null && $v !== '')->all();
  $without = fn (...$keys) => route('customers.index', $clean($query->except($keys)));
  $tabUrl = fn (array $set) => route('customers.index', $clean($query->except(['marketing', 'outstanding', 'archived'])->merge($set)));
  $tab = request()->boolean('archived') ? 'archived' : (request()->boolean('outstanding') ? 'outstanding' : (request('marketing') === 'eligible' ? 'promos' : (request('marketing') ? null : 'all')));

  $date = fn ($v) => rescue(fn () => \Illuminate\Support\Carbon::parse($v)->format('M j, Y'), $v, false);
  $chips = collect();
  if (request()->filled('q')) $chips->push(['Search: “'.request('q').'”', $without('q')]);
  if (request()->filled('status')) $chips->push(['Status: '.($statuses->firstWhere('id', (int) request('status'))?->name ?? '#'.request('status')), $without('status')]);
  if (request()->filled('tag')) $chips->push(['Tag: '.($tags->firstWhere('id', (int) request('tag'))?->name ?? '#'.request('tag')), $without('tag')]);
  if (request()->filled('source')) $chips->push(['Source: '.(\App\Models\Customer::SOURCES[request('source')] ?? request('source')), $without('source')]);
  if (request()->filled('assigned')) $chips->push(['Assigned to '.($users->firstWhere('id', (int) request('assigned'))?->name ?? '#'.request('assigned')), $without('assigned')]);
  if (request('marketing') === 'eligible') $chips->push(['Can receive promotions', $without('marketing')]);
  if (request('marketing') === 'not_eligible') $chips->push(['No promotions', $without('marketing')]);
  if (request()->filled('destination')) $chips->push(['Trip to '.($destinations->firstWhere('id', (int) request('destination'))?->name ?? '#'.request('destination')), $without('destination')]);
  if (request()->filled('min_trips')) $chips->push(['At least '.request('min_trips').' '.Str::plural('trip', (int) request('min_trips')), $without('min_trips')]);
  if (request()->filled('min_spent')) $chips->push(['Paid at least CA$'.request('min_spent'), $without('min_spent')]);
  if (request()->filled('signup_from') || request()->filled('signup_to')) $chips->push(['Signed up '.(request('signup_from') ? 'from '.$date(request('signup_from')).' ' : '').(request('signup_to') ? 'to '.$date(request('signup_to')) : ''), $without('signup_from', 'signup_to')]);
  if (request()->filled('trip_from') || request()->filled('trip_to')) $chips->push(['Last trip '.(request('trip_from') ? 'from '.$date(request('trip_from')).' ' : '').(request('trip_to') ? 'to '.$date(request('trip_to')) : ''), $without('trip_from', 'trip_to')]);
  if (request()->filled('birthday_month') && (int) request('birthday_month') >= 1 && (int) request('birthday_month') <= 12) $chips->push(['Birthday in '.now()->startOfYear()->month((int) request('birthday_month'))->format('F'), $without('birthday_month')]);
  if (request()->boolean('outstanding')) $chips->push(['Has outstanding balance', $without('outstanding')]);
  if (request()->boolean('archived')) $chips->push(['Archived only', $without('archived')]);

  $userNames = $users->pluck('name', 'id');
  $initials = fn ($c) => mb_strtoupper(mb_substr($c->first_name ?? '', 0, 1).mb_substr($c->last_name ?? '', 0, 1)) ?: '?';
  $tel = fn ($v) => preg_replace('/[^\d+]/', '', $v);
  $wa = fn ($v) => preg_replace('/\D/', '', $v);
  $canSecondary = auth()->user()->can('import-customers') || auth()->user()->can('export-customers') || auth()->user()->can('merge-customers') || auth()->user()->can('manage-customer-labels');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4 class="mb-0">{{ request()->boolean('archived') ? 'Archived customers' : 'Customers' }}</h4><span class="text-muted">{{ number_format($customers->total()) }} {{ Str::plural('customer', $customers->total()) }}{{ $chips->isNotEmpty() ? ' match the filters' : '' }}</span></div>
                        <div class="d-flex flex-wrap gap-2">
                          @if($canSecondary)
                          <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle cs-more-btn" type="button" id="cs-more" data-bs-toggle="dropdown" aria-expanded="false">More actions <i class="ri-arrow-down-s-line"></i></button>
                            <div class="dropdown-menu dropdown-menu-end cs-menu" aria-labelledby="cs-more">
                              @can('import-customers')<a class="dropdown-item" href="{{ route('customer-imports.create') }}"><i class="ri-upload-2-line"></i> Import customers</a>@endcan
                              @can('export-customers')<a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="ri-download-2-line"></i> Export these as CSV</a>@endcan
                              @can('merge-customers')<a class="dropdown-item" href="{{ route('customer-merge.index') }}"><i class="ri-git-merge-line"></i> Find duplicates</a>@endcan
                              @can('manage-customer-labels')<a class="dropdown-item" href="{{ route('customer-labels.index') }}"><i class="ri-price-tag-3-line"></i> Statuses & tags</a>@endcan
                            </div>
                          </div>
                          @endif
                          @can('create-customers')<a class="btn btn-primary" href="{{ route('customers.create') }}"><i class="ri-add-line"></i> New customer</a>@endcan
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<div class="card tl-card"><div class="card-body p-0">
  <form id="customer-filters" method="get" action="{{ route('customers.index') }}">
    {{-- View tabs (saved views): each keeps the search and the other filters. --}}
    <div class="tl-tabs-bar">
      <nav class="tl-tabs" aria-label="Customer views">
        <a class="tl-tab {{ $tab === 'all' ? 'active' : '' }}" href="{{ $tabUrl([]) }}" @if($tab === 'all') aria-current="page" @endif>All</a>
        <a class="tl-tab {{ $tab === 'promos' ? 'active' : '' }}" href="{{ $tabUrl(['marketing' => 'eligible']) }}" @if($tab === 'promos') aria-current="page" @endif>Can receive promotions</a>
        @if($fin)<a class="tl-tab {{ $tab === 'outstanding' ? 'active' : '' }}" href="{{ $tabUrl(['outstanding' => 1]) }}" @if($tab === 'outstanding') aria-current="page" @endif>Owes money</a>@endif
        @can('delete-customers')<a class="tl-tab {{ $tab === 'archived' ? 'active' : '' }}" href="{{ $tabUrl(['archived' => 1]) }}" @if($tab === 'archived') aria-current="page" @endif>Archived</a>@endcan
      </nav>
    </div>

    <div class="tl-search-bar cs-search-bar">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" id="c-q" name="q" value="{{ request('q') }}" placeholder="Search by name, email or phone" aria-label="Search by name, email or phone">@if(request()->filled('q'))<a href="{{ $without('q') }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
      <button type="submit" class="visually-hidden">Search</button>
      <div class="cs-quick-filters">
        <select class="form-select" id="c-status" name="status" aria-label="Status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->name }}{{ $status->archived_at ? ' (archived)' : '' }}</option>@endforeach</select>
        <select class="form-select" id="c-tag" name="tag" aria-label="Tag" onchange="this.form.submit()"><option value="">All tags</option>@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>@endforeach</select>
        <select class="form-select" id="c-sort" name="sort" aria-label="Sort by" onchange="this.form.submit()">@foreach($sorts as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'recent') === $value)>Sort: {{ $label }}</option>@endforeach</select>
        <button class="btn btn-light text-nowrap {{ $moreOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#moreFilters" aria-expanded="{{ $moreOpen ? 'true' : 'false' }}" aria-controls="moreFilters"><i class="ri-equalizer-line"></i> More filters @if($moreCount)<span class="badge bg-secondary ms-1">{{ $moreCount }}</span>@endif</button>
      </div>
    </div>

    <div class="collapse {{ $moreOpen ? 'show' : '' }}" id="moreFilters"><div class="cs-filters-panel">
      <div class="row g-3">
        <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-source">Source</label><select class="form-select" id="f-source" name="source"><option value="">Any source</option>@foreach(\App\Models\Customer::SOURCES as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-assigned">Assigned to</label><select class="form-select" id="f-assigned" name="assigned"><option value="">Anyone</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-marketing">Marketing</label><select class="form-select" id="f-marketing" name="marketing"><option value="">Any</option><option value="eligible" @selected(request('marketing') === 'eligible')>Can receive promotions</option><option value="not_eligible" @selected(request('marketing') === 'not_eligible')>No promotions</option></select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-destination">Trip destination</label><select class="form-select" id="f-destination" name="destination"><option value="">Any destination</option>@foreach($destinations as $destination)<option value="{{ $destination->id }}" @selected(request('destination') == $destination->id)>{{ $destination->name }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-trips">Trips taken (at least)</label><input class="form-control" id="f-trips" type="number" min="0" name="min_trips" value="{{ request('min_trips') }}"></div>
        @can('view-financial-info')<div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-spent">Total paid (at least, CAD)</label><input class="form-control" id="f-spent" type="number" min="0" step="1" name="min_spent" value="{{ request('min_spent') }}"></div>@endcan
        @can('view-customer-birthdays')<div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold" for="f-bday">Birthday month</label><select class="form-select" id="f-bday" name="birthday_month"><option value="">Any month</option>@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected(request('birthday_month') == $m)>{{ now()->startOfYear()->month($m)->format('F') }}</option>@endforeach</select></div>@endcan
        <div class="col-sm-6 col-lg-3 d-flex flex-column justify-content-end gap-1">
          @can('view-financial-info')<div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="c-out" name="outstanding" value="1" @checked(request('outstanding'))><label class="form-check-label" for="c-out">Has outstanding balance</label></div>@endcan
          @can('delete-customers')<div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="c-arch" name="archived" value="1" @checked(request('archived'))><label class="form-check-label" for="c-arch">Archived only</label></div>@endcan
        </div>
        <div class="col-md-6"><label class="form-label fw-semibold">Signed up</label><div class="input-group"><input class="form-control" type="date" name="signup_from" value="{{ request('signup_from') }}" aria-label="Signed up from"><span class="input-group-text">to</span><input class="form-control" type="date" name="signup_to" value="{{ request('signup_to') }}" aria-label="Signed up to"></div></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Last trip</label><div class="input-group"><input class="form-control" type="date" name="trip_from" value="{{ request('trip_from') }}" aria-label="Last trip from"><span class="input-group-text">to</span><input class="form-control" type="date" name="trip_to" value="{{ request('trip_to') }}" aria-label="Last trip to"></div></div>
      </div>
      <div class="cs-filters-actions">
        @if($filtered)<a class="btn btn-light" href="{{ route('customers.index', array_filter(['sort' => request('sort')])) }}">Reset</a>@endif
        <button class="btn btn-primary"><i class="ri-filter-3-line"></i> Apply filters</button>
      </div>
    </div></div>
  </form>

  @if($chips->isNotEmpty())
    <div class="tl-chips tl-chips-bar">
      @foreach($chips as [$label, $removeUrl])<a href="{{ $removeUrl }}" class="tl-chip" title="Remove this filter">{{ $label }} <i class="ri-close-line"></i></a>@endforeach
      <a href="{{ route('customers.index', array_filter(['sort' => request('sort')])) }}" class="btn btn-link btn-sm text-danger">Clear all filters</a>
    </div>
  @endif

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cs-table">
    <thead><tr>
      <th>Customer</th>
      <th class="d-none d-md-table-cell">Contact</th>
      <th class="text-end d-none d-lg-table-cell">Trips</th>
      @if($fin)<th class="text-end d-none d-lg-table-cell">Paid</th><th class="text-end d-none d-sm-table-cell">Outstanding</th>@endif
      <th class="d-none d-xl-table-cell">Last activity</th>
      <th class="text-end"><span class="visually-hidden">Actions</span></th>
    </tr></thead>
    <tbody>
    @forelse($customers as $customer)
      <tr>
        <td>
          <div class="cs-person">
            <span class="cs-avatar" aria-hidden="true">{{ $initials($customer) }}</span>
            <div class="cs-person-main">
              <a href="{{ route('customers.show', $customer->id) }}" class="fw-semibold text-body">{{ $customer->full_name ?: 'Customer #'.$customer->id }}</a>
              <div class="cs-badges">
                @if($customer->status)<span class="badge bg-{{ $customer->status->color }} bg-opacity-10 text-{{ $customer->status->color }}">{{ $customer->status->name }}</span>@endif
                @foreach($customer->tags as $tag)<span class="badge cs-tag">{{ $tag->name }}</span>@endforeach
                @if(!$customer->isMarketingEligible())<span class="cs-nopromo" title="No promotional messages"><i class="ri-mail-forbid-line"></i> No promos</span>@endif
              </div>
              <div class="cs-sub d-md-none">{{ collect([$customer->phone, $customer->email])->filter()->implode(' · ') ?: 'No contact details' }}</div>
            </div>
          </div>
        </td>
        <td class="d-none d-md-table-cell">
          @if($customer->email)<a class="cs-contact" href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@endif
          @if($customer->phone)<a class="cs-contact cs-sub" href="tel:{{ $tel($customer->phone) }}">{{ $customer->phone }}</a>@endif
          @if(!$customer->email && !$customer->phone)<span class="text-muted">—</span>@endif
        </td>
        <td class="text-end d-none d-lg-table-cell cs-num">{{ $customer->trips_count }}</td>
        @if($fin)
        <td class="text-end d-none d-lg-table-cell cs-num">{{ money($customer->total_paid) }}</td>
        <td class="text-end d-none d-sm-table-cell cs-num">@if($customer->outstanding > 0)<span class="badge bg-danger bg-opacity-10 text-danger">{{ money($customer->outstanding) }}</span>@else<span class="text-muted">{{ money($customer->outstanding) }}</span>@endif</td>
        @endif
        <td class="text-nowrap text-muted d-none d-xl-table-cell">{{ $customer->last_activity_at?->timezone($tz)->format('M j, Y') ?? '—' }}</td>
        <td class="text-end text-nowrap">
          <div class="cs-row-actions">
            @if($customer->phone)<a class="btn btn-sm btn-light cs-icon-btn d-none d-sm-inline-flex" href="tel:{{ $tel($customer->phone) }}" title="Call {{ $customer->phone }}" aria-label="Call {{ $customer->full_name }}"><i class="ri-phone-line"></i></a>@endif
            @if($customer->whatsapp)<a class="btn btn-sm btn-light cs-icon-btn d-none d-sm-inline-flex" href="https://wa.me/{{ $wa($customer->whatsapp) }}" target="_blank" rel="noopener" title="WhatsApp {{ $customer->whatsapp }}" aria-label="WhatsApp {{ $customer->full_name }}"><i class="ri-whatsapp-line"></i></a>@endif
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customer-details" data-cd="cd-{{ $customer->id }}">Details</button>
          </div>
          {{-- Details shown in the shared modal. --}}
          <template id="cd-{{ $customer->id }}">
            <div data-part="title"><span class="cs-avatar cs-avatar-lg" aria-hidden="true">{{ $initials($customer) }}</span><div><div class="cs-modal-name">{{ $customer->display_name ?: 'Customer #'.$customer->id }}</div><div class="text-muted fw-normal cs-modal-id">Customer #{{ $customer->id }}@if($customer->trashed()) · archived @endif</div></div></div>
            <div data-part="body">
              <div class="cs-badges mb-3">
                @if($customer->status)<span class="badge bg-{{ $customer->status->color }}">{{ $customer->status->name }}</span>@endif
                @foreach($customer->tags as $tag)<span class="badge cs-tag">{{ $tag->name }}</span>@endforeach
                @if($customer->isMarketingEligible())<span class="badge bg-success bg-opacity-10 text-success">Can receive promotions</span>@else<span class="badge bg-secondary bg-opacity-10 text-secondary">No promotions</span>@endif
              </div>
              @if($customer->phone || $customer->whatsapp || $customer->email)
              <div class="cs-quick mb-3">
                @if($customer->phone)<a class="btn btn-light" href="tel:{{ $tel($customer->phone) }}"><i class="ri-phone-line"></i> Call</a>@endif
                @if($customer->whatsapp)<a class="btn btn-light" href="https://wa.me/{{ $wa($customer->whatsapp) }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line"></i> WhatsApp</a>@endif
                @if($customer->email)<a class="btn btn-light" href="mailto:{{ $customer->email }}"><i class="ri-mail-line"></i> Email</a>@endif
              </div>
              @endif
              <div class="cs-stats mb-3">
                <div><span>Trips</span><strong>{{ $customer->trips_count }}</strong></div>
                @if($fin)<div><span>Paid</span><strong>{{ money($customer->total_paid) }}</strong></div>
                <div><span>Outstanding</span><strong class="{{ $customer->outstanding > 0 ? 'text-danger' : '' }}">{{ money($customer->outstanding) }}</strong></div>@endif
              </div>
              <dl class="cs-dl mb-0">
                <dt>Email</dt><dd class="text-break">{{ $customer->email ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $customer->phone ?: '—' }}</dd>
                <dt>WhatsApp</dt><dd>{{ $customer->whatsapp ?: '—' }}</dd>
                @can('view-customer-birthdays')<dt>Date of birth</dt><dd>{{ $customer->date_of_birth?->format('M j, Y') ?? '—' }}</dd>@endcan
                <dt>City</dt><dd>{{ $customer->city ?: '—' }}</dd>
                <dt>Language</dt><dd>{{ $customer->language ?: '—' }}</dd>
                <dt>Source</dt><dd>{{ \App\Models\Customer::SOURCES[$customer->source] ?? ($customer->source ?: '—') }}</dd>
                <dt>Assigned to</dt><dd>{{ $userNames[$customer->assigned_user_id] ?? '—' }}</dd>
                <dt>Customer since</dt><dd>{{ $customer->created_at?->timezone($tz)->format('M j, Y') ?? '—' }}</dd>
                <dt>Last activity</dt><dd>{{ $customer->last_activity_at?->timezone($tz)->format('M j, Y g:i A') ?? '—' }}</dd>
              </dl>
              @if($customer->notes)<div class="cs-note mt-3"><div class="cs-note-label">Notes</div>{{ Str::limit($customer->notes, 400) }}</div>@endif
            </div>
            <div data-part="footer">
              @can('edit-customers')@unless($customer->trashed())<a class="btn btn-light" href="{{ route('customers.edit', $customer->id) }}"><i class="ri-pencil-line"></i> Edit</a>@endunless @endcan
              <a class="btn btn-primary" href="{{ route('customers.show', $customer->id) }}">Open full profile <i class="ri-arrow-right-line"></i></a>
            </div>
          </template>
        </td>
      </tr>
    @empty
      <tr><td colspan="7" class="cs-empty">
        <i class="ri-user-search-line"></i>
        @if($chips->isNotEmpty())
          <h5>No customers match these filters</h5><p>Try a different search or remove a filter.</p>
          <a class="btn btn-light" href="{{ route('customers.index') }}">Clear all filters</a>
        @else
          <h5>No customers yet</h5><p>Customers are added automatically from bookings and enquiries. You can also add one by hand or import a list.</p>
          <div class="d-flex flex-wrap justify-content-center gap-2">@can('import-customers')<a class="btn btn-light" href="{{ route('customer-imports.create') }}"><i class="ri-upload-2-line"></i> Import</a>@endcan @can('create-customers')<a class="btn btn-primary" href="{{ route('customers.create') }}"><i class="ri-add-line"></i> New customer</a>@endcan</div>
        @endif
      </td></tr>
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $customers->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>

<div class="modal fade" id="customer-details" tabindex="-1" aria-labelledby="customer-details-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title cs-modal-title" id="customer-details-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body"></div>
      <div class="modal-footer"></div>
    </div>
  </div>
</div>
<script>
// Fill the shared Details modal from the clicked row's <template>.
document.getElementById('customer-details').addEventListener('show.bs.modal', function (e) {
  const tpl = e.relatedTarget && document.getElementById(e.relatedTarget.getAttribute('data-cd'));
  if (!tpl) return;
  const part = function (name) { const el = tpl.content.querySelector('[data-part="' + name + '"]'); return el ? el.innerHTML : ''; };
  this.querySelector('.modal-title').innerHTML = part('title');
  this.querySelector('.modal-body').innerHTML = part('body');
  this.querySelector('.modal-footer').innerHTML = part('footer');
});
</script>
</div></div></div>@include('admin.includes.footer')
