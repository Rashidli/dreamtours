@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Possible duplicate customers</h4>
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
<div class="card"><div class="card-body">
  <p class="text-muted">Customers that share a phone number or a full name. Nothing is merged automatically — open a pair, compare, and choose. If two people are really different (for example a couple sharing a phone), mark them “Not duplicates” and they won't be suggested again.</p>
  <div class="table-responsive"><table class="table table-hover crm-table mb-0">
    <thead><tr><th>Customer A</th><th>Customer B</th><th>Why</th><th></th></tr></thead>
    <tbody>
    @forelse($pairs as $pair)
      @php([$a, $b] = $pair)
      <tr>
        @foreach([$a, $b] as $c)
          <td><a href="{{ route('customers.show', $c) }}" target="_blank" class="fw-semibold">{{ $c->full_name }}</a> <span class="text-muted">#{{ $c->id }}</span>@if($c->status) <span class="badge bg-{{ $c->status->color }}">{{ $c->status->name }}</span>@endif<br><span class="text-muted">{{ collect([$c->email, $c->phone])->filter()->implode(' · ') }} · {{ $c->bookings_count }} {{ str('booking')->plural($c->bookings_count) }}</span></td>
        @endforeach
        <td>@foreach($pair['reasons'] as $reason)<span class="badge bg-warning bg-opacity-10 text-warning border border-warning me-1">{{ $reason }}</span>@endforeach</td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('customer-merge.compare', ['keep' => $a->bookings_count >= $b->bookings_count ? $a->id : $b->id, 'other' => $a->bookings_count >= $b->bookings_count ? $b->id : $a->id]) }}">Review</a>
          <form class="d-inline" method="post" action="{{ route('customer-merge.dismiss') }}">@csrf<input type="hidden" name="a" value="{{ $a->id }}"><input type="hidden" name="b" value="{{ $b->id }}"><button class="btn btn-sm btn-light">Not duplicates</button></form>
        </td>
      </tr>
    @empty
      <tr><td colspan="4" class="text-muted">No possible duplicates found.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div></div>
</div></div></div>@include('admin.includes.footer')
