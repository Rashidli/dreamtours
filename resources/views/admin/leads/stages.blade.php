@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.leads._styles')

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Lead stages</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                                <li class="breadcrumb-item active">Stages</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<div class="row justify-content-center"><div class="col-xl-9">
<div class="card"><div class="card-body">
  <p class="text-muted small">The columns of the lead board, in order. <b>Open</b> stages are still being worked on; <b>Won</b>, <b>Lost</b> and <b>Spam</b> close a lead. Built-in stages <i class="ri-lock-line"></i> are used by the automations (assigning, logging a call, converting, confirming a booking), so they can be renamed and recoloured but not removed.</p>
  <table class="table align-middle">
    <thead><tr><th style="width:70px">Order</th><th>Name & colour</th><th style="width:130px">Kind</th><th class="text-end" style="width:80px">Leads</th><th style="width:170px"></th></tr></thead>
    <tbody>
    @foreach($stages as $stage)
      <tr class="{{ $stage->archived_at ? 'text-muted' : '' }}">
        <td class="text-nowrap">@unless($stage->archived_at)@foreach(['up' => 'ri-arrow-up-s-line', 'down' => 'ri-arrow-down-s-line'] as $dir => $icon)<form class="d-inline" method="post" action="{{ route('lead-stages.move', $stage) }}">@csrf<input type="hidden" name="direction" value="{{ $dir }}"><button class="btn btn-sm btn-light px-1" title="Move {{ $dir }}"><i class="{{ $icon }}"></i></button></form>@endforeach @endunless</td>
        <td colspan="2">
          <form class="d-flex gap-2 align-items-center" method="post" action="{{ route('lead-stages.update', $stage) }}">@csrf @method('PUT')
            <span class="stage-dot" style="background:{{ \App\Models\LeadStage::HEX[$stage->color] ?? '#6c757d' }}"></span>
            <input class="form-control form-control-sm" name="name" value="{{ $stage->name }}" maxlength="60" required aria-label="Name">
            <select class="form-select form-select-sm" name="color" style="max-width:110px" aria-label="Colour">@foreach(\App\Models\CustomerStatus::COLORS as $color)<option @selected($stage->color === $color)>{{ $color }}</option>@endforeach</select>
            @if($stage->key)<span class="badge bg-light text-body" style="min-width:110px"><i class="ri-lock-line"></i> {{ \App\Models\LeadStage::KINDS[$stage->kind] }}</span>
            @else<select class="form-select form-select-sm" name="kind" style="max-width:110px" aria-label="Kind">@foreach(\App\Models\LeadStage::KINDS as $value => $label)<option value="{{ $value }}" @selected($stage->kind === $value)>{{ $label }}</option>@endforeach</select>@endif
            <button class="btn btn-sm btn-outline-primary" title="Save"><i class="ri-save-line"></i></button>
          </form>
        </td>
        <td class="text-end">{{ $stage->leads_count }}</td>
        <td class="text-end text-nowrap">
          @unless($stage->key)
            <form class="d-inline" method="post" action="{{ route('lead-stages.archive', $stage) }}">@csrf<button class="btn btn-sm btn-light">{{ $stage->archived_at ? 'Restore' : 'Archive' }}</button></form>
            @if($stage->leads_count === 0)<form class="d-inline" method="post" action="{{ route('lead-stages.destroy', $stage) }}" onsubmit="return confirm('Delete “{{ $stage->name }}”?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="ri-delete-bin-line"></i></button></form>@endif
          @endunless
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <form class="d-flex flex-wrap gap-2 align-items-center" method="post" action="{{ route('lead-stages.store') }}">@csrf
    <input class="form-control form-control-sm" style="max-width:240px" name="name" placeholder="New stage, e.g. Quote sent" maxlength="60" required>
    <select class="form-select form-select-sm" name="color" style="max-width:110px" aria-label="Colour">@foreach(\App\Models\CustomerStatus::COLORS as $color)<option>{{ $color }}</option>@endforeach</select>
    <select class="form-select form-select-sm" name="kind" style="max-width:110px" aria-label="Kind">@foreach(\App\Models\LeadStage::KINDS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
    <button class="btn btn-sm btn-primary"><i class="ri-add-line"></i> Add stage</button>
  </form>
</div></div>
</div></div>
</div></div></div>@include('admin.includes.footer')
