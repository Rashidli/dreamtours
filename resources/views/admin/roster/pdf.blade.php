<!doctype html>
<html><head><meta charset="utf-8"><title>Passengers</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1f2937; margin: 0; }
  h1 { font-size: 15px; margin: 0 0 2px; }
  .meta { color: #6b7280; margin-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; font-size: 8.5px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #9ca3af; padding: 4px 5px; }
  td { border-bottom: 1px solid #e5e7eb; padding: 4px 5px; vertical-align: top; }
  tr:nth-child(even) td { background: #f9fafb; }
  .footer { margin-top: 8px; color: #6b7280; font-size: 8px; }
</style></head>
<body>
  <h1>{{ $departure->tour?->name }}</h1>
  <div class="meta">{{ \App\Support\DepartureCalendar::range($departure) }} · departs {{ $departure->departs_at->format('g:i A') }} · {{ count($table) }} {{ str('passenger')->plural(count($table)) }}@if($departure->staff->isNotEmpty()) · {{ $departure->staff->map(fn ($s) => $s->role_label.': '.$s->user?->name)->implode(', ') }}@endif</div>
  <table>
    <thead><tr>@foreach($header as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>@foreach($table as $line)<tr>@foreach($line as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
  </table>
  <div class="footer">DreamTour · printed {{ now(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y g:i A') }} by {{ auth()->user()?->name }} · confidential passenger information</div>
</body></html>
