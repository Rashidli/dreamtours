{{-- One activity entry's details: field changes as "Field: old → new" (old struck through), other events as
     "Field: value". Long values are cut with a "Show more" button (inline handler, so it also works inside the
     <template> copied into the activity Details modal). Receives $log. --}}
@php
  $show = fn ($value) => match (true) {
      $value === null || $value === '' => '—',
      is_bool($value) => $value ? 'yes' : 'no',
      is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      default => (string) $value,
  };
  $cut = 140;
@endphp
@if($log->changes)
  <ul class="list-unstyled mb-0 mt-1" style="font-size:13.5px;line-height:1.5">
    @foreach($log->changes as $field => $value)
      @php
        $isChange = is_array($value) && array_is_list($value) && count($value) === 2;
        $parts = $isChange ? [$value[0] === null || $value[0] === '' ? null : $show($value[0]), $show($value[1])] : [null, $show($value)];
      @endphp
      <li class="text-break" style="padding:2px 0">
        <span class="fw-semibold" style="color:#616161">{{ str($field)->replace('_', ' ')->ucfirst() }}:</span>
        @foreach($parts as $i => $text)
          @continue($text === null)
          @if($i === 1 && $parts[0] !== null)<span aria-label="changed to" style="color:#8a8a8a">→</span>@endif
          <span @if($i === 0) class="text-decoration-line-through" style="color:#8e0b21" title="Before" @elseif($isChange) style="color:#0c5132" title="After" @endif><span>{{ mb_strlen($text) > $cut ? str($text)->limit($cut) : $text }}</span>@if(mb_strlen($text) > $cut)<button type="button" class="btn btn-link p-0 ms-1 align-baseline" style="font-size:13px;text-decoration:none" data-full="{{ $text }}" onclick="this.previousElementSibling.textContent = this.dataset.full; this.remove();">Show more</button>@endif</span>
        @endforeach
      </li>
    @endforeach
  </ul>
@endif
@if($log->reason)<div class="mt-1" style="font-size:13.5px"><span class="fw-semibold" style="color:#616161">Reason:</span> {{ $log->reason }}</div>@endif
