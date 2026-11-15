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
@php($filtered = collect(request()->except(['page', 'sort']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty())
<div class="card"><div class="card-body">
@include('admin.includes.flash')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h4 class="card-title mb-0">{{ request()->boolean('archived') ? 'Archived customers' : 'Customer directory' }} <small class="text-muted fw-normal">({{ $customers->total() }})</small></h4>
    <div class="d-flex gap-2">
        @can('manage-customer-labels')<a class="btn btn-light" href="{{ route('customer-labels.index') }}"><i class="ri-price-tag-3-line align-middle me-1"></i> Statuses & tags</a>@endcan
        @can('export-customers')<a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="ri-download-2-line align-middle me-1"></i> Export CSV</a>@endcan
        @can('create-customers')<a class="btn btn-primary" href="{{ route('customers.create') }}"><i class="ri-add-line align-middle me-1"></i> New customer</a>@endcan
    </div>
</div>

<form class="row g-2 mb-2">
    <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Name, email or phone"></div>
    <div class="col-md-2"><select class="form-select" name="status" aria-label="Status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->name }}{{ $status->archived_at ? ' (archived)' : '' }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="tag" aria-label="Tag"><option value="">All tags</option>@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="sort" aria-label="Sort">@foreach($sorts as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'recent') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary flex-grow-1">Filter</button>@if($filtered)<a class="btn btn-light" href="{{ route('customers.index') }}">Reset</a>@endif</div>

    <div class="col-12"><a class="small" data-bs-toggle="collapse" href="#moreFilters" role="button" aria-expanded="{{ $filtered ? 'true' : 'false' }}">More filters</a></div>
    <div class="collapse {{ request()->hasAny(['source', 'assigned', 'marketing', 'birthday_month', 'signup_from', 'signup_to', 'trip_from', 'trip_to', 'min_trips', 'min_spent', 'destination', 'outstanding', 'archived']) ? 'show' : '' }}" id="moreFilters"><div class="row g-2">
        <div class="col-md-2"><label class="form-label small mb-0">Source</label><select class="form-select" name="source"><option value="">Any</option>@foreach(\App\Models\Customer::SOURCES as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Assigned to</label><select class="form-select" name="assigned"><option value="">Anyone</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Marketing</label><select class="form-select" name="marketing"><option value="">Any</option><option value="eligible" @selected(request('marketing') === 'eligible')>Can receive promotions</option><option value="not_eligible" @selected(request('marketing') === 'not_eligible')>No promotions</option></select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Trip destination</label><select class="form-select" name="destination"><option value="">Any</option>@foreach($destinations as $destination)<option value="{{ $destination->id }}" @selected(request('destination') == $destination->id)>{{ $destination->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Trips taken (at least)</label><input class="form-control" type="number" min="0" name="min_trips" value="{{ request('min_trips') }}"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Total paid (at least, CAD)</label><input class="form-control" type="number" min="0" step="1" name="min_spent" value="{{ request('min_spent') }}"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Signed up from</label><input class="form-control" type="date" name="signup_from" value="{{ request('signup_from') }}"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Signed up to</label><input class="form-control" type="date" name="signup_to" value="{{ request('signup_to') }}"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Last trip from</label><input class="form-control" type="date" name="trip_from" value="{{ request('trip_from') }}"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Last trip to</label><input class="form-control" type="date" name="trip_to" value="{{ request('trip_to') }}"></div>
        @can('view-customer-birthdays')<div class="col-md-2"><label class="form-label small mb-0">Birthday month</label><select class="form-select" name="birthday_month"><option value="">Any</option>@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected(request('birthday_month') == $m)>{{ now()->startOfYear()->month($m)->format('F') }}</option>@endforeach</select></div>@endcan
        <div class="col-md-2 d-flex flex-column justify-content-end gap-1">
            <label class="small mb-0"><input type="checkbox" name="outstanding" value="1" @checked(request('outstanding'))> Has outstanding balance</label>
            @can('delete-customers')<label class="small mb-0"><input type="checkbox" name="archived" value="1" @checked(request('archived'))> Archived only</label>@endcan
        </div>
    </div></div>
</form>

<div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Customer</th><th>Contact</th><th class="text-end">Trips</th><th class="text-end">Paid</th><th class="text-end">Outstanding</th><th>Last activity</th><th></th></tr></thead>
    <tbody>
    @forelse($customers as $customer)
        <tr>
            <td>
                <span class="fw-semibold">{{ $customer->full_name }}</span>
                @if($customer->status)<span class="badge bg-{{ $customer->status->color }}">{{ $customer->status->name }}</span>@endif
                @if(!$customer->isMarketingEligible())<span class="badge bg-light text-muted" title="No promotional messages">no promos</span>@endif
                @if($customer->tags->isNotEmpty())<div>@foreach($customer->tags as $tag)<span class="badge bg-{{ $tag->color }} bg-opacity-10 text-{{ $tag->color }} border border-{{ $tag->color }} me-1">{{ $tag->name }}</span>@endforeach</div>@endif
            </td>
            <td class="small">{{ $customer->email }}@if($customer->phone)<br>{{ $customer->phone }}@endif</td>
            <td class="text-end">{{ $customer->trips_count }}</td>
            <td class="text-end">{{ money($customer->total_paid) }}</td>
            <td class="text-end {{ $customer->outstanding > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ money($customer->outstanding) }}</td>
            <td class="small text-muted">{{ $customer->last_activity_at?->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y') }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('customers.show', $customer->id) }}">Details</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted">No customers match these filters.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $customers->links('admin.vendor.pagination.bootstrap-5') }}
</div></div>
</div></div></div>@include('admin.includes.footer')
