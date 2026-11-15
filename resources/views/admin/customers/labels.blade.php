@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Customer statuses & tags</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item active">Statuses & tags</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
@php($colors = \App\Models\CustomerStatus::COLORS)
<div class="row">
@foreach(['status' => ['Statuses', 'Each customer has one status. A status that blocks promotions (like “Do Not Contact”) stops all marketing messages to its customers.', $statuses], 'tag' => ['Tags', 'Customers can have several tags.', $tags]] as $kind => [$title, $help, $labels])
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-1">{{ $title }}</h5>
      <p class="text-muted small">{{ $help }} Archived labels stay on the customers that have them but can no longer be chosen.</p>
      <table class="table table-sm align-middle">
        <thead><tr><th>Name</th><th>Colour</th>@if($kind === 'status')<th title="Blocks promotional messages">No promos</th>@endif<th class="text-end">Customers</th><th></th></tr></thead>
        <tbody>
        @foreach($labels as $label)
          <tr class="{{ $label->archived_at ? 'text-muted' : '' }}">
            <td colspan="{{ $kind === 'status' ? 3 : 2 }}">
              <form class="d-flex gap-2 align-items-center" method="post" action="{{ route('customer-labels.update', [$kind, $label->id]) }}">@csrf @method('PUT')
                <input class="form-control form-control-sm" name="name" value="{{ $label->name }}" maxlength="60" required aria-label="Name">
                <select class="form-select form-select-sm" name="color" style="max-width:120px" aria-label="Colour">@foreach($colors as $color)<option @selected($label->color === $color)>{{ $color }}</option>@endforeach</select>
                @if($kind === 'status')<input type="checkbox" name="blocks_marketing" value="1" @checked($label->blocks_marketing) title="Blocks promotional messages" aria-label="Blocks promotions">@endif
                <button class="btn btn-sm btn-outline-primary" title="Save"><i class="ri-save-line"></i></button>
              </form>
            </td>
            <td class="text-end"><span class="badge bg-{{ $label->color }}">{{ $label->customers_count }}</span></td>
            <td class="text-end text-nowrap">
              <form class="d-inline" method="post" action="{{ route('customer-labels.archive', [$kind, $label->id]) }}">@csrf<button class="btn btn-sm btn-light" title="{{ $label->archived_at ? 'Restore' : 'Archive' }}">{{ $label->archived_at ? 'Restore' : 'Archive' }}</button></form>
              @if($label->customers_count === 0)<form class="d-inline" method="post" action="{{ route('customer-labels.destroy', [$kind, $label->id]) }}" onsubmit="return confirm('Delete “{{ $label->name }}”?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="ri-delete-bin-line"></i></button></form>@endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <form class="d-flex gap-2 align-items-center" method="post" action="{{ route('customer-labels.store', $kind) }}">@csrf
        <input class="form-control form-control-sm" name="name" placeholder="New {{ $kind }}" maxlength="60" required>
        <select class="form-select form-select-sm" name="color" style="max-width:120px" aria-label="Colour">@foreach($colors as $color)<option>{{ $color }}</option>@endforeach</select>
        @if($kind === 'status')<label class="small text-nowrap mb-0"><input type="checkbox" name="blocks_marketing" value="1"> No promos</label>@endif
        <button class="btn btn-sm btn-primary text-nowrap"><i class="ri-add-line"></i> Add</button>
      </form>
    </div></div>
  </div>
@endforeach
</div>
</div></div></div>@include('admin.includes.footer')
