@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

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
<div class="row gx-lg-4">
@foreach(['status' => ['Statuses', 'Each customer has one status. A status that blocks promotions (like “Do Not Contact”) stops all marketing messages to its customers.', $statuses, 'ri-shield-user-line'], 'tag' => ['Tags', 'Customers can have several tags.', $tags, 'ri-price-tag-3-line']] as $kind => [$title, $help, $labels, $icon])
  <div class="col-12">
    <div class="card tl-card cs-labels"><div class="card-body p-0">
      <div class="cs-labels-head">
        <div class="tf-head mb-0"><span class="tf-icon"><i class="{{ $icon }}"></i></span><div><h5>{{ $title }} <span class="text-muted fw-normal">{{ $labels->count() }}</span></h5><p>{{ $help }} Archived labels stay on the customers that have them but can no longer be chosen.</p></div></div>
      </div>
      <ul class="cs-label-list">
        @forelse($labels as $label)
          <li class="cs-label-row {{ $label->archived_at ? 'is-archived' : '' }}">
            <form class="cs-label-edit" method="post" action="{{ route('customer-labels.update', [$kind, $label->id]) }}">@csrf @method('PUT')
              <span class="cs-swatch bg-{{ $label->color }}" aria-hidden="true"></span>
              <input class="form-control cs-label-name" name="name" value="{{ $label->name }}" maxlength="60" required aria-label="Name">
              <select class="form-select cs-label-color" name="color" aria-label="Colour" onchange="this.form.querySelector('.cs-swatch').className = 'cs-swatch bg-' + this.value">@foreach($colors as $color)<option value="{{ $color }}" @selected($label->color === $color)>{{ ucfirst($color) }}</option>@endforeach</select>
              @if($kind === 'status')<div class="form-check mb-0 cs-label-check" title="Blocks promotional messages"><input class="form-check-input" type="checkbox" id="bm-{{ $label->id }}" name="blocks_marketing" value="1" @checked($label->blocks_marketing)><label class="form-check-label" for="bm-{{ $label->id }}">No promos</label></div>@endif
              <button class="btn btn-light" title="Save changes to “{{ $label->name }}”"><i class="ri-save-line"></i> Save</button>
            </form>
            <div class="cs-label-meta">
              <span class="cs-label-count" title="Customers with this {{ $kind }}"><i class="ri-user-3-line"></i>{{ $label->customers_count }}</span>
              @if($label->archived_at)<span class="badge bg-secondary bg-opacity-10 text-secondary">Archived</span>@endif
              <form class="d-inline" method="post" action="{{ route('customer-labels.archive', [$kind, $label->id]) }}">@csrf<button class="btn btn-light" title="{{ $label->archived_at ? 'Restore' : 'Archive' }}">@if($label->archived_at)<i class="ri-arrow-go-back-line"></i> Restore @else<i class="ri-inbox-archive-line"></i> Archive @endif</button></form>
              @if($label->customers_count === 0)<form class="d-inline" method="post" action="{{ route('customer-labels.destroy', [$kind, $label->id]) }}" onsubmit="return confirm('Delete “{{ $label->name }}”? This cannot be undone.')">@csrf @method('DELETE')<button class="btn btn-outline-danger cs-icon-btn" title="Delete" aria-label="Delete {{ $label->name }}"><i class="ri-delete-bin-line"></i></button></form>@else<span class="cs-icon-spacer" aria-hidden="true"></span>@endif
            </div>
          </li>
        @empty
          <li class="cs-empty cs-empty-sm"><i class="{{ $icon }}"></i>No {{ Str::plural($kind) }} yet. Add the first one below.</li>
        @endforelse
      </ul>
      <form class="cs-label-add" method="post" action="{{ route('customer-labels.store', $kind) }}">@csrf
        <input class="form-control cs-label-name" name="name" placeholder="New {{ $kind }} name" maxlength="60" required aria-label="New {{ $kind }} name">
        <select class="form-select cs-label-color" name="color" aria-label="Colour">@foreach($colors as $color)<option value="{{ $color }}">{{ ucfirst($color) }}</option>@endforeach</select>
        @if($kind === 'status')<div class="form-check mb-0 cs-label-check"><input class="form-check-input" type="checkbox" id="bm-new" name="blocks_marketing" value="1"><label class="form-check-label text-nowrap" for="bm-new">No promos</label></div>@endif
        <button class="btn btn-primary text-nowrap"><i class="ri-add-line"></i> Add {{ $kind }}</button>
      </form>
    </div></div>
  </div>
@endforeach
</div>
</div></div></div>@include('admin.includes.footer')
