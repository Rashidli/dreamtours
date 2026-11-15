@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 3])
@php($update = ($import->options['duplicates'] ?? 'skip') === 'update')
<div class="row g-3 mb-3">
  @foreach([['new', 'New customers', 'success', 'will be created'], ['duplicate', 'Already exist', 'warning', $update ? 'empty fields filled in' : 'will be skipped'], ['error', 'Problems', 'danger', 'will be skipped']] as [$key, $label, $color, $note])
    <div class="col-md-4"><div class="card mb-0 border-{{ $color }}"><div class="card-body py-3"><p class="text-muted small mb-1">{{ $label }}</p><h4 class="mb-0 text-{{ $color }}">{{ number_format($counts[$key]) }}</h4><p class="small text-muted mb-0">{{ $note }}</p></div></div></div>
  @endforeach
</div>

<div class="card"><div class="card-body">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
    <h5 class="card-title mb-0">Check before importing — nothing has been saved yet</h5>
    <div class="d-flex gap-2">
      @if($counts['duplicate'] + $counts['error'] > 0)<a class="btn btn-sm btn-outline-secondary" href="{{ route('customer-imports.problems', $import) }}"><i class="ri-download-2-line"></i> Download skipped rows</a>@endif
      <a class="btn btn-sm btn-light" href="{{ route('customer-imports.edit', $import) }}"><i class="ri-arrow-left-line"></i> Change columns</a>
    </div>
  </div>
  <p class="small text-muted">New customers get status <b>{{ $status?->name ?? 'none' }}</b>{{ $tag ? ' and tag “'.$tag->name.'”' : '' }}. Showing the first 300 rows; the import covers all {{ number_format(count($results)) }}.</p>
  <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Show">
    @foreach(['all' => 'All', 'new' => 'New', 'duplicate' => 'Already exist', 'error' => 'Problems'] as $value => $label)<input type="radio" class="btn-check" name="show" id="show-{{ $value }}" value="{{ $value }}" @checked($value === 'all')><label class="btn btn-outline-secondary" for="show-{{ $value }}">{{ $label }}</label>@endforeach
  </div>
  <div class="table-responsive" style="max-height:55vh"><table class="table table-sm align-middle mb-0" id="import-rows">
    <thead class="table-light" style="position:sticky;top:0"><tr><th>Line</th><th>Result</th><th>Name</th><th>Email</th><th>Phone</th><th>Other</th><th>Note</th></tr></thead>
    <tbody>
    @foreach(array_slice($results, 0, 300) as $row)
      <tr data-outcome="{{ $row['outcome'] }}">
        <td class="text-muted">{{ $row['line'] }}</td>
        <td><span class="badge bg-{{ ['new' => 'success', 'duplicate' => 'warning', 'error' => 'danger'][$row['outcome']] }}">{{ ['new' => 'new', 'duplicate' => 'exists', 'error' => 'problem'][$row['outcome']] }}</span></td>
        <td>{{ trim($row['contact']['first_name'].' '.$row['contact']['last_name']) ?: '—' }}</td>
        <td class="small">{{ $row['contact']['email'] ?: '—' }}</td>
        <td class="small">{{ $row['contact']['phone'] ?: ($row['contact']['whatsapp'] ?: '—') }}</td>
        <td class="small text-muted">{{ collect([$row['contact']['city'], $row['contact']['date_of_birth'] && auth()->user()->can('view-customer-birthdays') ? 'born '.$row['contact']['date_of_birth'] : null, $row['tags'] ? 'tags: '.implode(', ', $row['tags']) : null, $row['consent'] ? 'marketing: yes' : null])->filter()->implode(' · ') }}</td>
        <td class="small">{{ implode(' ', $row['messages']) }}@if($row['match']) <a href="{{ route('customers.show', $row['match']) }}" target="_blank">open</a>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  <form class="mt-3" method="post" action="{{ route('customer-imports.commit', $import) }}" onsubmit="this.querySelector('button').disabled = true">@csrf
    <button class="btn btn-success" @disabled($counts['new'] === 0 && !($update && $counts['duplicate'] > 0))><i class="ri-check-line align-middle me-1"></i> Import {{ number_format($counts['new']) }} new {{ str('customer')->plural($counts['new']) }}{{ $update && $counts['duplicate'] ? ' and update '.number_format($counts['duplicate']) : '' }}</button>
  </form>
</div></div>
<script>
  document.querySelectorAll('input[name=show]').forEach(radio => radio.addEventListener('change', () => {
    document.querySelectorAll('#import-rows tbody tr').forEach(tr => tr.hidden = radio.value !== 'all' && tr.dataset.outcome !== radio.value);
  }));
</script>
</div></div></div>@include('admin.includes.footer')
