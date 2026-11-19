@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.customers.import._title', ['step' => 2])
<form method="post" action="{{ route('customer-imports.update', $import) }}">@csrf @method('PUT')
<div class="card tl-card"><div class="card-body p-0">
  <div class="cs-labels-head">
    <div class="tf-head mb-0"><span class="tf-icon"><i class="ri-file-excel-2-line"></i></span><div><h5>Match the columns of “{{ $import->filename }}” <span class="text-muted fw-normal">{{ number_format($rowCount) }} {{ Str::plural('row', $rowCount) }}</span></h5><p>We guessed from the column names — check each one. Columns set to “Don't import” are ignored.</p></div></div>
  </div>
  <div class="table-responsive"><table class="table align-middle tl-table mb-0">
    <thead><tr><th style="width:24%">Column in your file</th><th style="width:30%">Import as</th><th>First rows</th></tr></thead>
    <tbody>
    @foreach($import->columns as $i => $header)
      <tr>
        <td class="fw-semibold">{{ $header !== '' ? $header : 'Column '.($i + 1) }}</td>
        <td><select class="form-select" name="mapping[{{ $i }}]" aria-label="Import “{{ $header !== '' ? $header : 'Column '.($i + 1) }}” as"><option value="">Don't import</option>@foreach(\App\Support\CustomerImport::FIELDS as $field => [$label])<option value="{{ $field }}" @selected(old("mapping.$i", $import->mapping[$i] ?? null) === $field)>{{ $label }}</option>@endforeach</select></td>
        <td class="text-muted"><div class="cs-sample">{{ collect($samples)->map(fn ($row) => $row[$i] ?? '')->filter()->implode(' · ') ?: '—' }}</div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div></div>

<div class="card"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-settings-3-line"></i></span><div><h5>Options</h5><p>What happens to existing customers, and labels for the new ones.</p></div></div>
  <div class="row g-3">
    <div class="col-lg-6"><label class="fw-semibold d-block mb-2">When a customer already exists (same email, or same phone without email)</label>
      <label class="cs-radio-card"><input class="form-check-input" type="radio" name="duplicates" value="skip" @checked(old('duplicates', $import->options['duplicates'] ?? 'skip') === 'skip')><span>Skip that row — change nothing</span></label>
      <label class="cs-radio-card"><input class="form-check-input" type="radio" name="duplicates" value="update" @checked(old('duplicates', $import->options['duplicates'] ?? 'skip') === 'update')><span>Fill in only the existing customer's empty fields (and add tags)</span></label>
    </div>
    <div class="col-sm-6 col-lg-3"><label class="fw-semibold" for="ci-status">Status for new customers</label><select class="form-select" id="ci-status" name="status_id"><option value="">None</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(old('status_id', $import->options['status_id'] ?? null) == $status->id)>{{ $status->name }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-lg-3"><label class="fw-semibold" for="ci-tag">Tag every imported customer with</label><select class="form-select" id="ci-tag" name="tag_id"><option value="">No extra tag</option>@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(old('tag_id', $import->options['tag_id'] ?? null) == $tag->id)>{{ $tag->name }}</option>@endforeach</select><div class="form-text">Handy for finding this batch later. @can('manage-customer-labels')<a href="{{ route('customer-labels.index') }}" target="_blank">Create a tag</a>@endcan</div></div>
  </div>
  <div class="alert alert-warning d-flex gap-2 mt-3 mb-0"><i class="ri-error-warning-line"></i><div>Marketing consent: only map a consent column if these people really agreed to receive promotions. Consent is recorded with this file's name and your name, and is never switched off by an import.</div></div>
</div></div>
<div class="cs-savebar">
  <a class="btn btn-light" href="{{ route('customer-imports.create') }}">Start over</a>
  <button class="btn btn-primary">Check the rows <i class="ri-arrow-right-line"></i></button>
</div>
</form>
</div></div></div>@include('admin.includes.footer')
