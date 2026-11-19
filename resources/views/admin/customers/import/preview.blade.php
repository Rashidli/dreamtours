@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 3])
@php($update = ($import->options['duplicates'] ?? 'skip') === 'update')
<div class="cs-kpis">
  @foreach([['new', 'New customers', 'success', 'will be created'], ['duplicate', 'Already exist', 'warning', $update ? 'empty fields filled in' : 'will be skipped'], ['error', 'Problems', 'danger', 'will be skipped']] as [$key, $label, $color, $note])
    <div class="cs-kpi cs-kpi-{{ $color }}"><p>{{ $label }}</p><strong>{{ number_format($counts[$key]) }}</strong><p>{{ $note }}</p></div>
  @endforeach
</div>

<div class="card tl-card"><div class="card-body p-0">
  <div class="cs-panel-head flex-wrap">
    <div><h5 class="cs-panel-title"><i class="ri-eye-line"></i> Check before importing — nothing has been saved yet</h5>
    <p class="text-muted mb-0 mt-1">New customers get status <b>{{ $status?->name ?? 'none' }}</b>{{ $tag ? ' and tag “'.$tag->name.'”' : '' }}. Showing the first 300 rows; the import covers all {{ number_format(count($results)) }}.</p></div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-light" href="{{ route('customer-imports.edit', $import) }}"><i class="ri-arrow-left-line"></i> Change columns</a>
      @if($counts['duplicate'] + $counts['error'] > 0)<a class="btn btn-light" href="{{ route('customer-imports.problems', $import) }}"><i class="ri-download-2-line"></i> Download skipped rows</a>@endif
    </div>
  </div>
  <div class="tl-tabs-bar">
    <div class="tl-tabs" role="radiogroup" aria-label="Show">
      @foreach(['all' => 'All', 'new' => 'New', 'duplicate' => 'Already exist', 'error' => 'Problems'] as $value => $label)<input type="radio" class="btn-check" name="show" id="show-{{ $value }}" value="{{ $value }}" @checked($value === 'all')><label class="tl-tab" for="show-{{ $value }}">{{ $label }}@if($value !== 'all') <span class="text-muted fw-normal ms-1">{{ number_format($counts[$value]) }}</span>@endif</label>@endforeach
    </div>
  </div>
  <div class="table-responsive cs-preview-scroll"><table class="table align-middle tl-table mb-0" id="import-rows">
    <thead><tr><th>Line</th><th>Result</th><th>Name</th><th>Email</th><th>Phone</th><th>Other</th><th>Note</th></tr></thead>
    <tbody>
    @foreach(array_slice($results, 0, 300) as $row)
      <tr data-outcome="{{ $row['outcome'] }}">
        <td class="text-muted cs-num">{{ $row['line'] }}</td>
        <td><span class="badge bg-{{ ['new' => 'success', 'duplicate' => 'warning', 'error' => 'danger'][$row['outcome']] }} bg-opacity-10 text-{{ ['new' => 'success', 'duplicate' => 'warning', 'error' => 'danger'][$row['outcome']] }}">{{ ['new' => 'New', 'duplicate' => 'Exists', 'error' => 'Problem'][$row['outcome']] }}</span></td>
        <td>{{ trim($row['contact']['first_name'].' '.$row['contact']['last_name']) ?: '—' }}</td>
        <td>{{ $row['contact']['email'] ?: '—' }}</td>
        <td class="text-nowrap">{{ $row['contact']['phone'] ?: ($row['contact']['whatsapp'] ?: '—') }}</td>
        <td class="text-muted">{{ collect([$row['contact']['city'], $row['contact']['date_of_birth'] && auth()->user()->can('view-customer-birthdays') ? 'born '.$row['contact']['date_of_birth'] : null, $row['tags'] ? 'tags: '.implode(', ', $row['tags']) : null, $row['consent'] ? 'marketing: yes' : null])->filter()->implode(' · ') }}</td>
        <td>{{ implode(' ', $row['messages']) }}@if($row['match']) <a href="{{ route('customers.show', $row['match']) }}" target="_blank">open</a>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  <form class="cs-commit" method="post" action="{{ route('customer-imports.commit', $import) }}" onsubmit="this.querySelector('button').disabled = true">@csrf
    <button class="btn btn-primary" @disabled($counts['new'] === 0 && !($update && $counts['duplicate'] > 0))><i class="ri-check-line"></i> Import {{ number_format($counts['new']) }} new {{ str('customer')->plural($counts['new']) }}{{ $update && $counts['duplicate'] ? ' and update '.number_format($counts['duplicate']) : '' }}</button>
  </form>
</div></div>
<script>
  document.querySelectorAll('input[name=show]').forEach(radio => radio.addEventListener('change', () => {
    document.querySelectorAll('#import-rows tbody tr').forEach(tr => tr.hidden = radio.value !== 'all' && tr.dataset.outcome !== radio.value);
  }));
</script>
</div></div></div>@include('admin.includes.footer')
