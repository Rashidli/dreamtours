{{-- One activity entry's details: field changes as "old → new", other events as label: value. --}}
@php
  $show = fn ($value) => match (true) {
      $value === null || $value === '' => '—',
      is_bool($value) => $value ? 'yes' : 'no',
      is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      default => (string) $value,
  };
@endphp
@if($log->changes)
  <ul class="list-unstyled small mb-0">
    @foreach($log->changes as $field => $value)
      <li class="text-break">
        <span class="text-muted">{{ str($field)->replace('_', ' ') }}:</span>
        @if(is_array($value) && array_is_list($value) && count($value) === 2)
          @if($value[0] !== null && $value[0] !== '')<span class="text-danger text-decoration-line-through">{{ str($show($value[0]))->limit(160) }}</span> → @endif
          <span class="text-success">{{ str($show($value[1]))->limit(160) }}</span>
        @else
          {{ str($show($value))->limit(240) }}
        @endif
      </li>
    @endforeach
  </ul>
@endif
@if($log->reason)<div class="small mt-1"><span class="text-muted">Reason:</span> {{ $log->reason }}</div>@endif
