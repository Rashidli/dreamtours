@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-leads.css') }}?v={{ @filemtime(public_path('assets/css/admin-leads.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <a class="btn btn-light ld-back" href="{{ route('leads.index') }}" title="Back to the lead board" aria-label="Back to the lead board"><i class="ri-arrow-left-line"></i></a>
                            <div class="d-flex align-items-baseline gap-2"><h4 class="mb-0">Lead stages</h4><span class="text-muted">{{ $stages->count() }} {{ Str::plural('stage', $stages->count()) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<div class="row justify-content-center"><div class="col-xxl-10">
<div class="card tl-card"><div class="card-body p-0">
  <p class="ld-intro mb-0">The columns of the lead board, in order. <b>Open</b> stages are still being worked on; <b>Won</b>, <b>Lost</b> and <b>Spam</b> close a lead. Built-in stages <i class="ri-lock-line"></i> are used by the automations (assigning, logging a call, converting, confirming a booking), so they can be renamed and recoloured but not removed.</p>
  <div class="table-responsive"><table class="table align-middle tl-table crm-table ld-stage-table mb-0">
    <thead><tr><th style="width:90px">Order</th><th>Name, colour and kind</th><th class="text-end" style="width:80px">Leads</th><th class="text-end" style="width:170px">Actions</th></tr></thead>
    <tbody>
    @foreach($stages as $stage)
      <tr class="{{ $stage->archived_at ? 'text-muted is-archived' : '' }}">
        <td class="text-nowrap">@unless($stage->archived_at)<div class="ld-move">@foreach(['up' => 'ri-arrow-up-s-line', 'down' => 'ri-arrow-down-s-line'] as $dir => $icon)<form class="d-inline" method="post" action="{{ route('lead-stages.move', $stage) }}">@csrf<input type="hidden" name="direction" value="{{ $dir }}"><button class="btn btn-light" title="Move {{ $dir }}" aria-label="Move {{ $stage->name }} {{ $dir }}"><i class="{{ $icon }}"></i></button></form>@endforeach</div>@else<span class="badge bg-light">Archived</span>@endunless</td>
        <td>
          <form class="ld-stage-row-form" method="post" action="{{ route('lead-stages.update', $stage) }}">@csrf @method('PUT')
            <span class="stage-dot" style="background:{{ \App\Models\LeadStage::HEX[$stage->color] ?? '#6c757d' }}"></span>
            <input class="form-control" name="name" value="{{ $stage->name }}" maxlength="60" required aria-label="Name">
            <select class="form-select" name="color" aria-label="Colour">@foreach(\App\Models\CustomerStatus::COLORS as $color)<option @selected($stage->color === $color)>{{ $color }}</option>@endforeach</select>
            @if($stage->key)<span class="badge bg-light ld-locked" title="Built-in stage: the kind can't be changed"><i class="ri-lock-line"></i> {{ \App\Models\LeadStage::KINDS[$stage->kind] }}</span>
            @else<select class="form-select" name="kind" aria-label="Kind">@foreach(\App\Models\LeadStage::KINDS as $value => $label)<option value="{{ $value }}" @selected($stage->kind === $value)>{{ $label }}</option>@endforeach</select>@endif
            <button class="btn btn-light" title="Save {{ $stage->name }}"><i class="ri-save-line"></i> Save</button>
          </form>
        </td>
        <td class="text-end">{{ $stage->leads_count }}</td>
        <td class="text-end text-nowrap">
          @unless($stage->key)
            <form class="d-inline" method="post" action="{{ route('lead-stages.archive', $stage) }}">@csrf<button class="btn btn-light">{{ $stage->archived_at ? 'Restore' : 'Archive' }}</button></form>
            @if($stage->leads_count === 0)<form class="d-inline" method="post" action="{{ route('lead-stages.destroy', $stage) }}" onsubmit="return confirm('Delete “{{ $stage->name }}”? This can’t be undone.')">@csrf @method('DELETE')<button class="btn btn-outline-danger" title="Delete" aria-label="Delete {{ $stage->name }}"><i class="ri-delete-bin-line"></i></button></form>@endif
          @else
            <span class="text-muted">Built-in</span>
          @endunless
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  <form class="ld-add-bar" id="new-stage" method="post" action="{{ route('lead-stages.store') }}">@csrf
    <span class="fw-semibold me-1">New stage</span>
    <input class="form-control" id="new-stage-name" name="name" placeholder="e.g. Quote sent" maxlength="60" required aria-label="New stage name">
    <select class="form-select" name="color" aria-label="Colour">@foreach(\App\Models\CustomerStatus::COLORS as $color)<option>{{ $color }}</option>@endforeach</select>
    <select class="form-select" name="kind" aria-label="Kind">@foreach(\App\Models\LeadStage::KINDS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
    <button class="btn btn-primary"><i class="ri-add-line"></i> Add stage</button>
  </form>
</div></div>
</div></div>
</div></div></div>@include('admin.includes.footer')
