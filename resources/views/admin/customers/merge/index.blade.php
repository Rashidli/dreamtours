@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4 class="mb-sm-0">Possible duplicate customers</h4><span class="text-muted">{{ count($pairs) }} {{ Str::plural('pair', count($pairs)) }}</span></div>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item active">Duplicates</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<div class="card tl-card"><div class="card-body p-0">
  <div class="cs-labels-head">
    <div class="tf-head mb-0"><span class="tf-icon"><i class="ri-git-merge-line"></i></span><div><h5>Customers that share a phone number or a full name</h5><p>Nothing is merged automatically — open a pair, compare, and choose. If two people are really different (for example a couple sharing a phone), mark them “Not duplicates” and they won't be suggested again.</p></div></div>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle tl-table cs-dupes-table mb-0">
    <thead><tr><th>Customer A</th><th>Customer B</th><th>Why</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
    @forelse($pairs as $pair)
      @php([$a, $b] = $pair)
      <tr>
        @foreach([$a, $b] as $c)
          <td>
            <div class="cs-person">
              <span class="cs-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($c->first_name ?? '', 0, 1).mb_substr($c->last_name ?? '', 0, 1)) ?: '?' }}</span>
              <div class="cs-person-main">
                <a href="{{ route('customers.show', $c) }}" target="_blank" class="fw-semibold text-body">{{ $c->full_name }}</a> <span class="text-muted">#{{ $c->id }}</span>@if($c->status) <span class="badge bg-{{ $c->status->color }} bg-opacity-10 text-{{ $c->status->color }}">{{ $c->status->name }}</span>@endif
                <span class="cs-sub">{{ collect([$c->email, $c->phone])->filter()->implode(' · ') }} · {{ $c->bookings_count }} {{ str('booking')->plural($c->bookings_count) }}</span>
              </div>
            </div>
          </td>
        @endforeach
        <td><div class="cs-badges">@foreach($pair['reasons'] as $reason)<span class="badge bg-warning bg-opacity-10 text-warning">{{ $reason }}</span>@endforeach</div></td>
        <td class="text-end text-nowrap">
          <div class="cs-row-actions">
            <form class="d-inline" method="post" action="{{ route('customer-merge.dismiss') }}" onsubmit="return confirm('Mark these two as different people? They won\'t be suggested again.')">@csrf<input type="hidden" name="a" value="{{ $a->id }}"><input type="hidden" name="b" value="{{ $b->id }}"><button class="btn btn-sm btn-light">Not duplicates</button></form>
            <a class="btn btn-sm btn-primary" href="{{ route('customer-merge.compare', ['keep' => $a->bookings_count >= $b->bookings_count ? $a->id : $b->id, 'other' => $a->bookings_count >= $b->bookings_count ? $b->id : $a->id]) }}">Review</a>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="4" class="cs-empty"><i class="ri-check-double-line"></i><h5>No possible duplicates found</h5><p>Customers that share a phone number or a full name will show up here.</p><a class="btn btn-light" href="{{ route('customers.index') }}">Back to customers</a></td></tr>
    @endforelse
    </tbody>
  </table></div>
</div></div>
</div></div></div>@include('admin.includes.footer')
