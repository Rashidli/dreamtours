@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Customers</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Customers</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@php
  $filtered = collect(request()->except(['page', 'sort']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
  $moreOpen = collect(request()->only(['source', 'assigned', 'marketing', 'birthday_month', 'signup_from', 'signup_to', 'trip_from', 'trip_to', 'min_trips', 'min_spent', 'destination', 'outstanding', 'archived']))->filter()->isNotEmpty();
@endphp
@include('admin.includes.flash')

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h5 class="mb-0">{{ request()->boolean('archived') ? 'Archived customers' : 'Customer directory' }} <span class="text-muted fw-normal">({{ $customers->total() }})</span></h5>
  <div class="d-flex flex-wrap gap-2">
    @can('manage-customer-labels')<a class="btn btn-light" href="{{ route('customer-labels.index') }}"><i class="ri-price-tag-3-line me-1"></i>Statuses & tags</a>@endcan
    @can('merge-customers')<a class="btn btn-light" href="{{ route('customer-merge.index') }}"><i class="ri-git-merge-line me-1"></i>Duplicates</a>@endcan
    @can('import-customers')<a class="btn btn-light" href="{{ route('customer-imports.create') }}"><i class="ri-upload-2-line me-1"></i>Import</a>@endcan
    @can('create-customers')<a class="btn btn-primary" href="{{ route('customers.create') }}"><i class="ri-add-line me-1"></i>New customer</a>@endcan
  </div>
</div>

<div class="card crm-filters"><div class="card-body">
  <form>
    <div class="row g-3 align-items-end">
      <div class="col-md-6 col-xl-4"><label class="form-label" for="c-q">Search</label><input class="form-control" id="c-q" name="q" value="{{ request('q') }}" placeholder="Name, email or phone"></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="c-status">Status</label><select class="form-select" id="c-status" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->name }}{{ $status->archived_at ? ' (archived)' : '' }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="c-tag">Tag</label><select class="form-select" id="c-tag" name="tag"><option value="">All tags</option>@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3 col-xl-2"><label class="form-label" for="c-sort">Sort by</label><select class="form-select" id="c-sort" name="sort">@foreach($sorts as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'recent') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col d-flex flex-wrap gap-2 justify-content-end">
        <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Apply</button>
        @if($filtered)<a class="btn btn-light" href="{{ route('customers.index') }}">Reset</a>@endif
        @can('export-customers')<a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="ri-download-2-line me-1"></i>Export CSV</a>@endcan
      </div>
    </div>
    <a class="d-inline-block mt-3" data-bs-toggle="collapse" href="#moreFilters" role="button" aria-expanded="{{ $moreOpen ? 'true' : 'false' }}"><i class="ri-equalizer-line me-1"></i>More filters</a>
    <div class="collapse {{ $moreOpen ? 'show' : '' }}" id="moreFilters"><div class="row g-3 pt-3">
      <div class="col-sm-6 col-md-3"><label class="form-label">Source</label><select class="form-select" name="source"><option value="">Any source</option>@foreach(\App\Models\Customer::SOURCES as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3"><label class="form-label">Assigned to</label><select class="form-select" name="assigned"><option value="">Anyone</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3"><label class="form-label">Marketing</label><select class="form-select" name="marketing"><option value="">Any</option><option value="eligible" @selected(request('marketing') === 'eligible')>Can receive promotions</option><option value="not_eligible" @selected(request('marketing') === 'not_eligible')>No promotions</option></select></div>
      <div class="col-sm-6 col-md-3"><label class="form-label">Trip destination</label><select class="form-select" name="destination"><option value="">Any destination</option>@foreach($destinations as $destination)<option value="{{ $destination->id }}" @selected(request('destination') == $destination->id)>{{ $destination->name }}</option>@endforeach</select></div>
      <div class="col-sm-6 col-md-3"><label class="form-label">Trips taken (at least)</label><input class="form-control" type="number" min="0" name="min_trips" value="{{ request('min_trips') }}"></div>
      @can('view-financial-info')<div class="col-sm-6 col-md-3"><label class="form-label">Total paid (at least, CAD)</label><input class="form-control" type="number" min="0" step="1" name="min_spent" value="{{ request('min_spent') }}"></div>@endcan
      <div class="col-md-6 col-xl-3"><label class="form-label">Signed up</label><div class="input-group"><input class="form-control" type="date" name="signup_from" value="{{ request('signup_from') }}" aria-label="Signed up from"><span class="input-group-text">–</span><input class="form-control" type="date" name="signup_to" value="{{ request('signup_to') }}" aria-label="Signed up to"></div></div>
      <div class="col-md-6 col-xl-3"><label class="form-label">Last trip</label><div class="input-group"><input class="form-control" type="date" name="trip_from" value="{{ request('trip_from') }}" aria-label="Last trip from"><span class="input-group-text">–</span><input class="form-control" type="date" name="trip_to" value="{{ request('trip_to') }}" aria-label="Last trip to"></div></div>
      @can('view-customer-birthdays')<div class="col-sm-6 col-md-3"><label class="form-label">Birthday month</label><select class="form-select" name="birthday_month"><option value="">Any month</option>@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected(request('birthday_month') == $m)>{{ now()->startOfYear()->month($m)->format('F') }}</option>@endforeach</select></div>@endcan
      <div class="col-sm-6 col-md-3 d-flex flex-column justify-content-end">
        @can('view-financial-info')<div class="form-check"><input class="form-check-input" type="checkbox" id="c-out" name="outstanding" value="1" @checked(request('outstanding'))><label class="form-check-label" for="c-out">Has outstanding balance</label></div>@endcan
        @can('delete-customers')<div class="form-check"><input class="form-check-input" type="checkbox" id="c-arch" name="archived" value="1" @checked(request('archived'))><label class="form-check-label" for="c-arch">Archived only</label></div>@endcan
      </div>
    </div></div>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <div class="table-responsive"><table class="table table-hover crm-table mb-0">
    <thead><tr><th>Customer</th><th>Contact</th><th class="text-end">Trips</th>@can('view-financial-info')<th class="text-end">Paid</th><th class="text-end">Outstanding</th>@endcan<th>Last activity</th><th></th></tr></thead>
    <tbody>
    @forelse($customers as $customer)
      <tr>
        <td>
          <a href="{{ route('customers.show', $customer->id) }}" class="fw-semibold text-body">{{ $customer->full_name }}</a>
          @if($customer->status)<span class="badge rounded-pill bg-{{ $customer->status->color }} ms-1">{{ $customer->status->name }}</span>@endif
          @if(!$customer->isMarketingEligible())<span class="badge rounded-pill bg-light text-muted ms-1" title="No promotional messages">no promos</span>@endif
          @if($customer->tags->isNotEmpty())<span class="sub">@foreach($customer->tags as $tag)<span class="badge bg-{{ $tag->color }} bg-opacity-10 text-{{ $tag->color }} me-1">{{ $tag->name }}</span>@endforeach</span>@endif
        </td>
        <td>{{ $customer->email ?: '—' }}@if($customer->phone)<span class="sub">{{ $customer->phone }}</span>@endif</td>
        <td class="amount fw-normal">{{ $customer->trips_count }}</td>
        @can('view-financial-info')
        <td class="amount fw-normal">{{ money($customer->total_paid) }}</td>
        <td class="amount {{ $customer->outstanding > 0 ? 'text-danger' : 'text-muted fw-normal' }}">{{ money($customer->outstanding) }}</td>
        @endcan
        <td class="text-nowrap">{{ $customer->last_activity_at?->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y') ?? '—' }}</td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('customers.show', $customer->id) }}">Details</a></td>
      </tr>
    @empty
      <tr><td colspan="{{ auth()->user()->can('view-financial-info') ? 7 : 5 }}" class="crm-empty"><i class="ri-user-search-line"></i>No customers match these filters.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <div class="mt-3">{{ $customers->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>@include('admin.includes.footer')
