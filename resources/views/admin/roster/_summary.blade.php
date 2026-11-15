{{-- Expected / arrived / boarded / missing / no-show per pickup stop (CRM §10–§11, AT-08). Recomputed on every load. --}}
@php
  $groups = $all->groupBy(fn ($row) => $row['passenger']->pickup_point_id ?? 0);
  $total = \App\Support\Attendance::counts($all->pluck('passenger'));
  $link = $link ?? fn (array $q) => request()->fullUrlWithQuery($q + ['page' => null]);
@endphp
@if($total['missing'] && \App\Support\Attendance::alertWindow($departure))
  <div class="alert alert-danger d-flex gap-2 align-items-center"><i class="ri-alarm-warning-line fs-4"></i><div><b>{{ $total['missing'] }} still missing</b> — departure {{ $departure->departs_at->isPast() ? 'was' : 'is' }} at {{ $departure->departs_at->format('g:i A') }}. <a class="alert-link" href="{{ $link(['attendance' => 'missing']) }}#roster">Show who</a></div></div>
@endif
<div class="table-responsive"><table class="table crm-table mb-0">
  <thead><tr><th>Pickup stop</th><th class="text-end">Expected</th><th class="text-end">Arrived</th><th class="text-end">Boarded</th><th class="text-end">Missing</th><th class="text-end">No-show</th></tr></thead>
  <tbody>
  @foreach($stops->pluck('id')->push(0)->filter(fn ($id) => $groups->has($id) || $id !== 0) as $stopId)
    @php($c = \App\Support\Attendance::counts(($groups[$stopId] ?? collect())->pluck('passenger')))
    <tr>
      <td><a class="text-body" href="{{ $link(['pickup' => $stopId]) }}#roster">{{ $stopId ? $stops->firstWhere('id', $stopId)?->name : 'No pickup stop' }}</a></td>
      <td class="amount fw-normal">{{ $c['expected'] }}</td>
      <td class="amount fw-normal text-info">{{ $c['arrived'] }}</td>
      <td class="amount fw-normal text-success">{{ $c['boarded'] }}</td>
      <td class="amount {{ $c['missing'] ? 'text-danger' : 'text-muted fw-normal' }}">@if($c['missing'])<a class="text-danger" href="{{ $link(['pickup' => $stopId, 'attendance' => 'missing']) }}#roster">{{ $c['missing'] }}</a>@else 0 @endif</td>
      <td class="amount fw-normal">{{ $c['no_show'] }}</td>
    </tr>
  @endforeach
  </tbody>
  <tfoot><tr><td>Total</td><td class="amount">{{ $total['expected'] }}</td><td class="amount text-info">{{ $total['arrived'] }}</td><td class="amount text-success">{{ $total['boarded'] }}</td><td class="amount {{ $total['missing'] ? 'text-danger' : '' }}">{{ $total['missing'] }}</td><td class="amount">{{ $total['no_show'] }}</td></tr></tfoot>
</table></div>
