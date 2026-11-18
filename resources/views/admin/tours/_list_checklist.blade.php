{{-- Excel-style value list for a tours-list header menu.
     $name (query key, sent as name[]), $options: [[value, label, count|null], …], $picked: selected values, $searchable --}}
@php
  $listId = 'cl-'.$name;
  $pickedValues = collect($picked)->map(fn ($v) => (string) $v)->all();
@endphp
<div class="tl-checklist">
  @if($searchable ?? true)<input type="search" class="form-control mb-2" placeholder="Search…" aria-label="Search the list" data-checklist-search>@endif
  <div class="form-check border-bottom pb-2 mb-1"><input class="form-check-input" type="checkbox" id="{{ $listId }}-all" data-checklist-all><label class="form-check-label fw-semibold" for="{{ $listId }}-all">(Select all)</label></div>
  <div class="tl-checklist-items">
    @foreach($options as [$value, $label, $count])
      <div class="form-check" data-option="{{ Str::lower($label) }}"><input class="form-check-input" type="checkbox" form="tour-list-filters" name="{{ $name }}[]" value="{{ $value }}" id="{{ $listId }}-{{ $value }}" @checked(in_array((string) $value, $pickedValues, true))><label class="form-check-label d-flex justify-content-between gap-2" for="{{ $listId }}-{{ $value }}"><span>{{ $label }}</span>@if($count !== null)<span class="text-muted">{{ $count }}</span>@endif</label></div>
    @endforeach
  </div>
  <div class="tl-menu-actions"><a class="btn btn-light" href="{{ route('tours.index', collect(request()->query())->except([$name, 'page'])->all()) }}">Clear</a><button class="btn btn-primary" form="tour-list-filters">Apply</button></div>
</div>
