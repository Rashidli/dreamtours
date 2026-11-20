{{-- Read-only drawing of a seating plan. $plan = layout array; $small for list thumbnails, drawn lying on
     its side (front at the left, driver's side at the bottom) so a long coach fits in one table row. --}}
@php
  $types = \App\Models\VehicleLayout::TYPES;
  $small = $small ?? false;
@endphp
<div class="seat-plan {{ $small ? 'sm' : '' }}" style="{{ $small ? 'grid-template-rows: repeat('.$plan['cols'].', auto); grid-auto-flow: column' : 'grid-template-columns: repeat('.$plan['cols'].', auto)' }}" role="img" aria-label="{{ \App\Models\VehicleLayout::countSeats($plan) }}-seat plan">
  @foreach($plan['cells'] as $row)
    @foreach($small ? array_reverse($row) : $row as $cell)
      <div class="cell t-{{ $cell['t'] }}" title="{{ $cell['t'] === 'seat' ? 'Seat '.$cell['l'] : $types[$cell['t']][0] }}">{{ $cell['t'] === 'seat' ? $cell['l'] : $types[$cell['t']][1] }}</div>
    @endforeach
  @endforeach
</div>
