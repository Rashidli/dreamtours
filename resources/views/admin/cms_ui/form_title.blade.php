{{-- Form page title with breadcrumb. $title, $parent = [label, url], $current (breadcrumb label), $badge = [label, tone] (optional) --}}
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ $parent[1] }}" class="cms-back" aria-label="Back to {{ $parent[0] }}" title="Back to {{ $parent[0] }}"><i class="ri-arrow-left-line"></i></a>
        <h4 class="mb-sm-0">{{ $title }}</h4>
        @if(!empty($badge))<span class="badge bg-{{ $badge[1] }}">{{ $badge[0] }}</span>@endif
      </div>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ $parent[1] }}">{{ $parent[0] }}</a></li>
          <li class="breadcrumb-item active">{{ $current }}</li>
        </ol>
      </div>
    </div>
  </div>
</div>
