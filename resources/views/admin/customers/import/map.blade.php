@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 2])
<form method="post" action="{{ route('customer-imports.update', $import) }}">@csrf @method('PUT')
<div class="card"><div class="card-body">
  <h5 class="card-title">Match the columns of “{{ $import->filename }}” <small class="text-muted fw-normal">({{ number_format($rowCount) }} rows)</small></h5>
  <p class="text-muted">We guessed from the column names — check each one. Columns set to “Don't import” are ignored.</p>
  <div class="table-responsive"><table class="table crm-table align-middle">
    <thead><tr><th style="width:22%">Column in your file</th><th style="width:28%">Import as</th><th>First rows</th></tr></thead>
    <tbody>
    @foreach($import->columns as $i => $header)
      <tr>
        <td class="fw-semibold">{{ $header !== '' ? $header : 'Column '.($i + 1) }}</td>
        <td><select class="form-select" name="mapping[{{ $i }}]"><option value="">Don't import</option>@foreach(\App\Support\CustomerImport::FIELDS as $field => [$label])<option value="{{ $field }}" @selected(old("mapping.$i", $import->mapping[$i] ?? null) === $field)>{{ $label }}</option>@endforeach</select></td>
        <td class="text-muted text-truncate" style="max-width:360px">{{ collect($samples)->map(fn ($row) => $row[$i] ?? '')->filter()->implode(' · ') ?: '—' }}</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div></div>

<div class="card"><div class="card-body">
  <h5 class="card-title">Options</h5>
  <div class="row">
    <div class="col-md-5 mb-3"><label class="form-label">When a customer already exists (same email, or same phone without email)</label>
      <div><label class="d-block"><input type="radio" name="duplicates" value="skip" @checked(old('duplicates', $import->options['duplicates'] ?? 'skip') === 'skip')> Skip that row — change nothing</label>
      <label class="d-block"><input type="radio" name="duplicates" value="update" @checked(old('duplicates', $import->options['duplicates'] ?? 'skip') === 'update')> Fill in only the existing customer's empty fields (and add tags)</label></div>
    </div>
    <div class="col-md-3 mb-3"><label class="form-label">Status for new customers</label><select class="form-select" name="status_id"><option value="">None</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(old('status_id', $import->options['status_id'] ?? null) == $status->id)>{{ $status->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Tag every imported customer with</label><select class="form-select" name="tag_id"><option value="">No extra tag</option>@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(old('tag_id', $import->options['tag_id'] ?? null) == $tag->id)>{{ $tag->name }}</option>@endforeach</select><div class="form-text">Handy for finding this batch later. @can('manage-customer-labels')<a href="{{ route('customer-labels.index') }}" target="_blank">Create a tag</a>@endcan</div></div>
  </div>
  <div class="alert alert-warning small mb-3">Marketing consent: only map a consent column if these people really agreed to receive promotions. Consent is recorded with this file's name and your name, and is never switched off by an import.</div>
  <button class="btn btn-primary">Check the rows <i class="ri-arrow-right-line align-middle"></i></button>
  <a class="btn btn-light" href="{{ route('customer-imports.create') }}">Start over</a>
</div></div>
</form>
</div></div></div>@include('admin.includes.footer')
