{{-- List page header (tours list pattern): title + muted count on the left, ONE primary action on the right.
     $title, $count (int|null), $noun (singular, pluralised by count), $filtered (bool),
     $action = ['url' => ..., 'label' => ..., 'can' => permission|null] (optional), $extra (raw HTML after the count, optional),
     $intro (one muted line under the header, optional) --}}
<div class="row">
  <div class="col-12">
    <div class="page-title-box cms-list-head d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex flex-wrap align-items-baseline gap-2">
        <h4 class="mb-0">{{ $title }}</h4>
        @isset($count)<span class="text-muted">{{ $count }} {{ Str::plural($noun ?? 'item', $count) }}{{ !empty($filtered) ? ' match the filters' : '' }}</span>@endisset
        {!! $extra ?? '' !!}
      </div>
      @if(!empty($action))
        @if(empty($action['can']) || auth()->user()?->can($action['can']))
          <a class="btn btn-primary" href="{{ $action['url'] }}"><i class="ri-add-line"></i> {{ $action['label'] }}</a>
        @endif
      @endif
    </div>
    @if(!empty($intro))<p class="cms-intro">{!! $intro !!}</p>@endif
  </div>
</div>
