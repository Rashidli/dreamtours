{{-- Empty state row for a list table.
     $colspan, $icon, $title, $text (optional), $filtered (bool), $clearUrl, $action = ['url','label','can'] (optional) --}}
<tr class="cms-empty-row"><td colspan="{{ $colspan }}">
  <div class="cms-empty">
    <span class="cms-empty-icon"><i class="{{ !empty($filtered) ? 'ri-search-line' : $icon }}"></i></span>
    @if(!empty($filtered))
      <h6>Nothing matches these filters</h6>
      <p>Try a different search or <a href="{{ $clearUrl }}">clear the filters</a>.</p>
    @else
      <h6>{{ $title }}</h6>
      @if(!empty($text))<p>{{ $text }}</p>@endif
      @if(!empty($action) && (empty($action['can']) || auth()->user()?->can($action['can'])))<a class="btn btn-primary mt-1" href="{{ $action['url'] }}"><i class="ri-add-line"></i> {{ $action['label'] }}</a>@endif
    @endif
  </div>
</td></tr>
