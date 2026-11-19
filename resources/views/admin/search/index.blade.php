@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="page-title-box d-flex flex-wrap align-items-baseline gap-2"><h4 class="mb-0">Search</h4>@if($q !== '')<span class="text-muted">results for “{{ $q }}”</span>@endif</div>

  <form method="get" action="{{ route('admin.search') }}" class="mb-3" style="max-width:640px">
    <label class="tl-search-field" style="max-width:none;height:40px"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ $q }}" placeholder="Tours, customers, bookings (DT-…), leads (L-…), email or phone" aria-label="Search" autofocus></label>
  </form>

  @if(mb_strlen($q) < 2)
    <div class="card"><div class="card-body text-center text-muted py-5"><i class="ri-search-line fs-2 d-block mb-1"></i>Type at least 2 characters — a name, email, phone number or a reference like DT-… or L-….</div></div>
  @elseif($groups->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5"><i class="ri-search-eye-line fs-2 d-block mb-1"></i>Nothing found for “{{ $q }}”.</div></div>
  @else
    <div class="row g-3">
      @foreach($groups as $title => $items)
        <div class="col-lg-6">
          <div class="card h-100 mb-0"><div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"><span class="fw-semibold">{{ $title }}</span><span class="badge bg-secondary">{{ $items->count() }}{{ $items->count() >= 8 ? '+' : '' }}</span></div>
            <div class="list-group list-group-flush">
              @foreach($items as $item)
                <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2">
                  <i class="{{ $item['icon'] }} text-muted fs-5"></i>
                  <span class="d-flex flex-column min-w-0"><span class="fw-semibold text-body text-truncate">{{ $item['title'] }}</span>@if($item['meta'])<span class="text-muted small text-truncate">{{ $item['meta'] }}</span>@endif</span>
                </a>
              @endforeach
            </div>
          </div></div>
        </div>
      @endforeach
    </div>
  @endif
</div></div></div>
@include('admin.includes.footer')
