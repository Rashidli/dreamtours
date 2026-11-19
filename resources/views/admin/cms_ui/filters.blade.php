{{-- Index filters in the tours-list style: optional view tabs, then a search bar with optional selects.
     $action (url), $search = placeholder (null = no search box),
     $tabs = ['name' => ..., 'label' => aria label, 'options' => [value => label]] (optional; '' = All),
     $selects = [['name' => ..., 'label' => ..., 'all' => 'All …', 'options' => [value => label]], ...] (optional),
     $keep = [name => value] hidden fields to keep (optional), $clearUrl (url without filters),
     $ignore = [names] that do not count as a filter for the "Clear filters" link (optional). --}}
@php
  $tabs = $tabs ?? null;
  $selects = $selects ?? [];
  $filterNames = collect($selects)->pluck('name')->push('q')->when($tabs, fn ($c) => $c->push($tabs['name']));
  $anyFilter = $filterNames->diff($ignore ?? [])->contains(fn ($n) => request()->filled($n));
@endphp
<form method="get" action="{{ $action }}" class="cms-filters">
  @foreach($keep ?? [] as $keepName => $keepValue)@if($keepValue !== null && $keepValue !== '')<input type="hidden" name="{{ $keepName }}" value="{{ $keepValue }}">@endif @endforeach
  @if($tabs)
    <div class="tl-tabs-bar">
      <div class="tl-tabs" role="radiogroup" aria-label="{{ $tabs['label'] ?? 'View' }}">
        @foreach($tabs['options'] as $tabValue => $tabLabel)
          <input type="radio" class="btn-check" name="{{ $tabs['name'] }}" id="tab-{{ $tabs['name'] }}-{{ $tabValue === '' ? 'all' : Str::slug((string) $tabValue) }}" value="{{ $tabValue }}" @checked((string) request($tabs['name'], '') === (string) $tabValue) onchange="this.form.submit()"><label class="tl-tab" for="tab-{{ $tabs['name'] }}-{{ $tabValue === '' ? 'all' : Str::slug((string) $tabValue) }}">{{ $tabLabel }}</label>
        @endforeach
      </div>
    </div>
  @endif
  @if($search !== null || $selects)
    <div class="tl-search-bar">
      @if($search !== null)
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $search }}" aria-label="{{ $search }}">@if(request()->filled('q'))<a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <button type="submit" class="visually-hidden">Search</button>
      @endif
      @foreach($selects as $select)
        <select class="form-select cms-filter-select" name="{{ $select['name'] }}" aria-label="{{ $select['label'] }}" onchange="this.form.submit()">
          <option value="">{{ $select['all'] }}</option>
          @foreach($select['options'] as $optValue => $optLabel)<option value="{{ $optValue }}" @selected((string) request($select['name']) === (string) $optValue)>{{ $optLabel }}</option>@endforeach
        </select>
      @endforeach
      @if($anyFilter)<a href="{{ $clearUrl }}" class="btn btn-link text-danger cms-clear">Clear filters</a>@endif
    </div>
  @endif
</form>
