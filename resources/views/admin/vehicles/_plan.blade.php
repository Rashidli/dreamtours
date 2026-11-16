{{-- Read-only drawing of a seating plan. $plan = layout array; $small for list thumbnails. --}}
@php($types = \App\Models\VehicleLayout::TYPES)
<div class="seat-plan {{ ($small ?? false) ? 'sm' : '' }}" style="grid-template-columns: repeat({{ $plan['cols'] }}, auto)" role="img" aria-label="{{ \App\Models\VehicleLayout::countSeats($plan) }}-seat plan">
  @foreach($plan['cells'] as $row)
    @foreach($row as $cell)
      <div class="cell t-{{ $cell['t'] }}" title="{{ $cell['t'] === 'seat' ? 'Seat '.$cell['l'] : $types[$cell['t']][0] }}">{{ $cell['t'] === 'seat' ? $cell['l'] : $types[$cell['t']][1] }}</div>
    @endforeach
  @endforeach
</div>
