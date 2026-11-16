<!doctype html>
<html><head><meta charset="utf-8"><title>Seating</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
  h1 { font-size: 15px; margin: 0 0 2px; } h2 { font-size: 12px; margin: 14px 0 4px; }
  .meta { color: #6b7280; margin-bottom: 6px; }
  table.plan { border-collapse: separate; border-spacing: 3px; border: 1.5px solid #9ca3af; border-radius: 10px; padding: 4px; }
  table.plan td { width: 74px; height: 34px; vertical-align: top; padding: 2px 4px; border-radius: 4px; }
  td.seat { border: 1px solid #9ca3af; } td.seat.taken { border-left: 4px solid #374151; }
  td.blocked { background: #e5e7eb; color: #6b7280; } td.driver { background: #111827; color: #fff; text-align: center; vertical-align: middle; }
  td.leader { border: 1px dashed #0f9cf3; color: #0b7bc0; text-align: center; vertical-align: middle; } td.door { background: #fde7b0; text-align: center; vertical-align: middle; }
  .no { color: #6b7280; font-size: 7.5px; } .who { font-weight: bold; }
  .page { page-break-after: always; } .page:last-child { page-break-after: auto; }
</style></head>
<body>
@foreach($departure->vehicles as $v)
  <div class="page">
    <h1>{{ $departure->tour?->name }} — {{ $v->label }}</h1>
    <div class="meta">{{ \App\Support\DepartureCalendar::range($departure) }} · departs {{ $departure->departs_at->format('g:i A') }}@if($v->note) · {{ $v->note }}@endif @if(($team = $departure->staff->filter(fn ($s) => !$s->departure_vehicle_id || $s->departure_vehicle_id === $v->id))->isNotEmpty()) · {{ $team->map(fn ($s) => $s->role_label.': '.$s->user?->name)->implode(', ') }}@endif · {{ $v->published_at ? 'Published '.$v->published_at->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, g:i A') : 'Draft' }}{{ $v->hasDraftChanges() && $v->published_at ? ' (changed since)' : '' }}</div>
    <table class="plan">
      @foreach($v->layout['cells'] as $row)
        <tr>
          @foreach($row as $c)
            @if($c['t'] === 'seat')
              @php($p = $seated[$v->id.'|'.$c['l']] ?? null)
              <td class="seat {{ $p ? 'taken' : '' }} {{ in_array($c['l'], $v->blockedSeats(), true) ? 'blocked' : '' }}"><div class="no">{{ $c['l'] }}</div>@if($p)<div class="who">{{ $p->first_name }} {{ mb_substr((string) $p->last_name, 0, 1) }}.</div><div class="no">{{ $p->pickupPoint?->name }}</div>@elseif(in_array($c['l'], $v->blockedSeats(), true))<div>blocked</div>@endif</td>
            @else
              <td class="{{ $c['t'] }}">{{ \App\Models\VehicleLayout::TYPES[$c['t']][1] }}</td>
            @endif
          @endforeach
        </tr>
      @endforeach
    </table>
  </div>
@endforeach
@if($unseated->isNotEmpty())
  <h2>Without a seat ({{ $unseated->count() }})</h2>
  <div>{{ $unseated->map(fn ($p) => trim("{$p->first_name} {$p->last_name}").' ('.$p->booking->reference.')')->implode(', ') }}</div>
@endif
<div class="meta" style="margin-top:8px">DreamTour · printed {{ now(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }} by {{ auth()->user()?->name }} · confidential passenger information</div>
</body></html>
