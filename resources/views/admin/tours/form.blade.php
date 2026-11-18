@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php($tt = $tour->translate('en'))
@php($statusLabels = ['draft' => ['Draft', 'Hidden from the website', 'secondary'], 'published' => ['Published', 'Visible on the website', 'success'], 'archived' => ['Archived', 'Hidden, kept for history', 'dark']])
@php($badgeLabels = ['new' => 'New tour', 'popular' => 'Popular', 'limited_seats' => 'Limited seats', 'last_seats' => 'Last seats', 'sold_out' => 'Sold out', 'coming_soon' => 'Coming soon', 'special_price' => 'Special price'])
@php($currentStatus = $tour->status ?: 'draft')
@php($publicUrl = ($tour->exists && $tt?->slug && $tour->status === 'published') ? route('front.tours.show', $tt->slug) : null)
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="mb-sm-0">{{ $tour->exists ? ($tt?->name ?: 'Edit tour') : 'Add tour' }}</h4>
                            @if($tour->exists)<span class="badge bg-{{ $statusLabels[$currentStatus][2] ?? 'secondary' }} bg-opacity-10 text-{{ $statusLabels[$currentStatus][2] ?? 'secondary' }} fw-semibold">{{ $statusLabels[$currentStatus][0] ?? ucfirst($currentStatus) }}</span>@endif
                        </div>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('tours.index')}}">Tours</a></li>
                                <li class="breadcrumb-item active">{{ $tour->exists ? 'Edit' : 'Add' }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form id="tour-main-form" class="tf-form" method="post" enctype="multipart/form-data" action="{{$tour->exists?route('tours.update',$tour):route('tours.store')}}">@csrf @if($tour->exists)@method('PUT')@endif
<div class="row g-4">
<div class="col-xl-8">

{{-- 1. Basics --}}
<div class="card tf-section" id="basics"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-file-text-line"></i></span><div><h5>Basics</h5><p>The name and texts visitors read on the tour card and tour page.</p></div></div>
  <div class="mb-3"><label class="fw-semibold tf-req" for="tf-name">Tour name</label><input id="tf-name" class="form-control @error('en.name') is-invalid @enderror" name="en[name]" value="{{old('en.name',$tt?->name)}}" required maxlength="255" placeholder="e.g. Niagara Falls Day Trip">@error('en.name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  <div class="mb-3"><label class="fw-semibold" for="tf-short">Short description</label><textarea id="tf-short" class="form-control" rows="3" name="en[short_description]" placeholder="One or two sentences: where it goes and the highlights.">{{old('en.short_description',$tt?->short_description)}}</textarea><div class="form-text">Shown on tour cards and under the title. Also the default Google description.</div></div>
  <div class="mb-0"><label class="fw-semibold" for="tf-description">Full description</label><textarea id="tf-description" class="form-control" rows="8" name="en[description]">{{old('en.description',$tt?->description)}}</textarea></div>
</div></div>

{{-- 2. SEO --}}
<div class="card tf-section" id="seo-card"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-search-eye-line"></i></span><div><h5>Search engines (SEO)</h5><p>The page address and how the tour looks on Google. Empty fields use the name and short description.</p></div></div>
  @include('admin.tours._seo')
</div></div>

{{-- 3. Where it appears --}}
<div class="card tf-section" id="placement"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-map-pin-line"></i></span><div><h5>Where it appears</h5><p>Destination, listing pages, transport and pick-up points.</p></div></div>
  <div class="row"><div class="col-md-4 mb-3"><label class="fw-semibold">Destination</label><select class="form-select" name="destination_id"><option value="">None</option>@foreach($destinations as $d)<option value="{{$d->id}}" @selected(old('destination_id',$tour->destination_id)==$d->id)>{{$d->name}}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="fw-semibold">Categories</label><select class="form-select js-select2-multi" multiple name="category_ids[]" data-placeholder="Select…">@foreach($categories as $c)<option value="{{$c->id}}" @selected(in_array($c->id,old('category_ids',$tour->categories->pluck('id')->all())))>{{$c->name}}</option>@endforeach</select><div class="form-text">Decides the listing page (One day, Multi-day…).</div></div><div class="col-md-4 mb-3"><label class="fw-semibold">Transportation</label><select class="form-select js-select2-multi" multiple name="transportation_ids[]" data-placeholder="Select…">@foreach($transportations as $t)<option value="{{$t->id}}" @selected(in_array($t->id,old('transportation_ids',$tour->transportations->pluck('id')->all())))>{{$t->name}}</option>@endforeach</select></div></div>
  <div class="mb-3"><label class="fw-semibold">Departure / pick-up points</label><select class="form-select js-select2-multi" multiple name="pickup_point_ids[]" data-placeholder="Select…">@foreach($pickupPoints as $point)<option value="{{$point->id}}" @selected(in_array($point->id,old('pickup_point_ids',$tour->pickupPoints->pluck('id')->all())))>{{$point->name}} — {{$point->address}}</option>@endforeach</select><div class="form-text">Select every pick-up point offered; the first one is the main departure location.</div></div>
  @if($tourFilters->isNotEmpty())<div class="tf-subtle"><div class="d-flex justify-content-between flex-wrap gap-2 mb-2"><div><div class="tf-label">Listing filters</div><div class="text-muted">Tick what matches this tour so visitors can filter the listing pages by it.</div></div><a href="{{route('tour-filters.index')}}" class="btn btn-outline-secondary btn-sm align-self-start"><i class="ri-settings-3-line"></i> Manage filters</a></div><input type="hidden" name="filter_options_present" value="1"><div class="row">@foreach($tourFilters as $filter)<div class="col-md-4 mb-2"><label class="fw-semibold">{{$filter->name}}@unless($filter->is_active) <span class="text-muted fw-normal">(inactive)</span>@endunless</label><select class="form-select js-select2-multi" multiple name="filter_option_ids[]" data-placeholder="—">@foreach($filter->options as $option)<option value="{{$option->id}}" @selected(in_array($option->id,old('filter_option_ids',$tour->exists?$tour->filterOptions->pluck('id')->all():[])))>{{$option->name}}{{$option->is_active?'':' (inactive)'}}</option>@endforeach</select></div>@endforeach</div></div>@endif
</div></div>

{{-- 4. Trip details --}}
<div class="card tf-section" id="details"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-time-line"></i></span><div><h5>Trip details</h5><p>Length and who the trip suits.</p></div></div>
  <div class="row"><div class="col-sm-6 col-md-3 mb-3"><label class="fw-semibold tf-req">Days</label><input type="number" min="1" class="form-control" name="duration_days" value="{{old('duration_days',$tour->duration_days??1)}}" required></div><div class="col-sm-6 col-md-3 mb-3"><label class="fw-semibold">Nights</label><input type="number" min="0" class="form-control" name="duration_nights" value="{{old('duration_nights',$tour->duration_nights??0)}}"></div><div class="col-sm-6 col-md-3 mb-3"><label class="fw-semibold">Age restriction</label><input class="form-control" name="age_restriction" value="{{old('age_restriction',$tour->age_restriction)}}" placeholder="e.g. 5+"></div><div class="col-sm-6 col-md-3 mb-3"><label class="fw-semibold">Activity level</label><input class="form-control" name="activity_level" value="{{old('activity_level',$tour->activity_level)}}" placeholder="e.g. Easy walking"></div></div>
</div></div>

{{-- 5. Included / not included --}}
<div class="card tf-section" id="inclusions"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-checkbox-multiple-line"></i></span><div class="flex-grow-1"><h5>What’s included / not included</h5><p>Tick the shared lines for this tour. To add a line or change its wording everywhere, use <a href="{{route('inclusions.index')}}" target="_blank">Tours & CRM → Inclusions</a>.</p></div></div>
  <input type="search" class="form-control mb-3" style="max-width:320px" placeholder="Find a line…" aria-label="Find an inclusion line" oninput="const q=this.value.toLowerCase();document.querySelectorAll('#inclusions [data-inclusion]').forEach(el=>el.hidden=q&&!el.dataset.inclusion.includes(q))">
  <input type="hidden" name="inclusions_present" value="1">@php($pickedInclusions=array_map('intval',old('inclusion_ids',$tour->exists?$tour->inclusions->pluck('id')->all():[])))
  <div class="row g-3">@foreach(\App\Models\Inclusion::TYPES as $type=>$typeLabel)<div class="col-md-6"><div class="tf-label">{{$typeLabel}} <span class="badge bg-primary bg-opacity-10 text-primary" data-inclusion-count="{{$type}}"></span></div><div class="tf-inclusions border rounded p-2" data-inclusion-type="{{$type}}">@forelse($inclusionLibrary->get($type,collect()) as $inc)<div class="form-check" data-inclusion="{{Str::lower($inc->title.' '.$inc->description)}}"><input type="checkbox" class="form-check-input" id="inc-{{$inc->id}}" name="inclusion_ids[]" value="{{$inc->id}}" @checked(in_array($inc->id,$pickedInclusions,true))><label class="form-check-label" for="inc-{{$inc->id}}">{{$inc->icon}} {{$inc->title}} @unless($inc->is_active)<span class="badge bg-secondary">inactive</span>@endunless @if($inc->description)<span class="text-muted d-block">{{Str::limit($inc->description,90)}}</span>@endif</label></div>@empty<p class="text-muted mb-0 p-2">No lines yet — add them in Inclusions.</p>@endforelse</div></div>@endforeach</div>
</div></div>

{{-- 6. Fares & payment --}}
<div class="card tf-section" id="fares"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-money-dollar-circle-line"></i></span><div><h5>Fares & payment</h5><p>Age groups, deposit and taxes. Prices are set on each date below.</p></div></div>
  <div class="tf-subtle mb-3"><div class="tf-label mb-2">Adults, children and infants</div><div class="row g-3 align-items-end"><div class="col-sm-6 col-md-3"><label class="fw-semibold">Infants: age 0 to</label><input type="number" min="0" max="5" class="form-control" name="infant_max_age" value="{{old('infant_max_age',$tour->infant_max_age??1)}}"></div><div class="col-sm-6 col-md-3"><label class="fw-semibold">Children: up to age</label><input type="number" min="1" max="20" class="form-control" name="child_max_age" value="{{old('child_max_age',$tour->child_max_age??11)}}"></div><div class="col-md-6"><label class="fw-semibold d-block">Infant seat</label><select class="form-select" name="infant_needs_seat"><option value="0" @selected(!old('infant_needs_seat',$tour->infant_needs_seat))>Sits on a parent’s lap — no seat counted</option><option value="1" @selected(old('infant_needs_seat',$tour->infant_needs_seat))>Needs its own seat — counts toward capacity</option></select></div></div><div class="text-muted mt-2"><i class="ri-information-line"></i> Currently: {{ $tour->priceLabel('infant') }} · {{ $tour->priceLabel('child') }} · {{ $tour->priceLabel('adult') }}. The booking form checks dates of birth against these ages; an infant price of 0 means free.</div></div>
  <div class="row"><div class="col-md-4 mb-3"><label class="fw-semibold">Deposit per person (CAD)</label><input type="number" step="0.01" min="0" class="form-control" name="deposit_per_person" value="{{old('deposit_per_person',$tour->deposit_per_person)}}" placeholder="Default: {{ (int) setting('default_deposit_percent', 100) }}% of the price"><div class="form-text">Paid when booking. Empty = the default.</div></div><div class="col-md-4 mb-3"><label class="fw-semibold">Balance due (days before)</label><input type="number" min="0" max="365" class="form-control" name="balance_due_days" value="{{old('balance_due_days',$tour->balance_due_days)}}" placeholder="Default: {{ (int) setting('balance_due_days', 7) }} days"><div class="form-text">When the rest of the price is due.</div></div><div class="col-md-4 mb-3"><label class="fw-semibold">Taxes</label><select class="form-select" name="tax_included"><option value="0" @selected(!old('tax_included',$tour->tax_included))>Added on top (“{{ setting('price_tax_note') ?: '+ tax' }}”)</option><option value="1" @selected(old('tax_included',$tour->tax_included))>Included in the price</option></select><div class="form-text">Included = prices show without the tax note everywhere.</div></div></div>
</div></div>

{{-- 7. Policies --}}
<div class="card tf-section" id="policies"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-file-shield-2-line"></i></span><div><h5>Policies & rules</h5><p>Shown on the tour page. Leave a field empty to hide it.</p></div></div>
  <div class="row">@foreach(['deposit_policy'=>'Deposit policy','cancellation_policy'=>'Cancellation policy','refund_policy'=>'Refund policy','boarding_rules'=>'Boarding rules','baggage_rules'=>'Baggage rules','important_notes'=>'Important notes'] as $field=>$fieldLabel)<div class="col-md-6 mb-3"><label class="fw-semibold">{{$fieldLabel}}</label><textarea class="form-control" rows="4" name="en[{{$field}}]">{{old('en.'.$field,$tt?->{$field})}}</textarea></div>@endforeach</div>
</div></div>

{{-- 8. Booking form --}}
<div class="card tf-section" id="booking-form"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-survey-line"></i></span><div><h5>Booking form</h5><p>Extra questions on this tour’s booking form.</p></div></div>
  <div class="mb-3"><label class="fw-semibold">Meal choices</label><textarea class="form-control" rows="3" name="en[meal_options]" placeholder="Tuna sandwich&#10;Chicken sandwich&#10;Vegetarian sandwich">{{old('en.meal_options',$tt?->meal_options)}}</textarea><div class="form-text">One per line; each passenger must pick one. Leave empty to skip.</div></div>
  <div class="mb-3"><label class="fw-semibold">Waiver title</label><input class="form-control" name="en[waiver_title]" value="{{old('en.waiver_title',$tt?->waiver_title)}}" placeholder="e.g. Release of Liability Waiver"></div>
  <div class="mb-0"><label class="fw-semibold">Waiver text</label><textarea class="form-control" rows="6" name="en[waiver]">{{old('en.waiver',$tt?->waiver)}}</textarea><div class="form-text">When filled, customers must read and accept it before booking this trip.</div></div>
</div></div>

{{-- 9. Cruise (only when “Cruise” is switched on) --}}
<div class="card tf-section @unless(old('is_cruise',$tour->is_cruise)) d-none @endunless" id="cruise"><div class="card-body">
  <div class="tf-head"><span class="tf-icon"><i class="ri-ship-line"></i></span><div><h5>Cruise details</h5><p>Shown because “Cruise” is on in the Publish panel.</p></div></div>
  <div class="row"><div class="col-md-4 mb-3"><label class="fw-semibold">Company / cruise line</label><input class="form-control" form="tour-main-form" name="cruise[company]" value="{{old('cruise.company',$tour->cruiseDetail?->company)}}"></div><div class="col-md-4 mb-3"><label class="fw-semibold">Departure port</label><input class="form-control" form="tour-main-form" name="cruise[departure_port]" value="{{old('cruise.departure_port',$tour->cruiseDetail?->departure_port)}}"></div><div class="col-md-4 mb-3"><label class="fw-semibold">Cabin types</label><input class="form-control" form="tour-main-form" name="cruise[cabin_type]" value="{{old('cruise.cabin_type',$tour->cruiseDetail?->cabin_type)}}" placeholder="Interior, Balcony, Suite"></div></div><div class="mb-0"><label class="fw-semibold">Required documents</label><textarea class="form-control" rows="3" form="tour-main-form" name="cruise[documents]">{{old('cruise.documents',implode("\n",$tour->cruiseDetail?->documents??[]))}}</textarea><div class="form-text">One per line.</div></div>
</div></div>

</div>{{-- /main column --}}

<div class="col-xl-4"><div class="tf-sidebar">
  {{-- Publish --}}
  <div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0 fs-6 fw-semibold">Publish</h5><span class="tf-dirty badge bg-warning bg-opacity-10 text-warning align-items-center gap-1"><i class="ri-edit-2-line"></i> Unsaved changes</span></div>
    <div class="mb-3"><label class="fw-semibold">Status</label><select class="form-select" name="status" id="tf-status">@foreach($statusLabels as $s=>[$sLabel,$sHint])<option value="{{$s}}" @selected(old('status',$currentStatus)===$s)>{{$sLabel}} — {{$sHint}}</option>@endforeach</select></div>
    <div class="row g-2 mb-2"><div class="col-7"><label class="fw-semibold">Badge</label><select class="form-select" name="badge"><option value="">None</option>@foreach($badgeLabels as $b=>$bLabel)<option value="{{$b}}" @selected(old('badge',$tour->badge)===$b)>{{$bLabel}}</option>@endforeach</select></div><div class="col-5"><label class="fw-semibold">Order</label><input type="number" min="0" class="form-control" name="sort_order" value="{{old('sort_order',$tour->sort_order??0)}}" title="Lower numbers are shown first"></div></div>
    <div class="border-top pt-2 mt-2">
      <div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="tf-show-on-home" name="show_on_home" value="1" @checked(old('show_on_home',$tour->show_on_home))><label class="form-check-label" for="tf-show-on-home">Show on home<small>In the tours section of the home page.</small></label></div>
      <div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="tf-show-in-carousel" name="show_in_carousel" value="1" @checked(old('show_in_carousel',$tour->show_in_carousel))><label class="form-check-label" for="tf-show-in-carousel">Homepage carousel<small>As a slide at the top of the home page.</small></label></div>
      <div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="tf-is-private" name="is_private" value="1" @checked(old('is_private',$tour->is_private))><label class="form-check-label" for="tf-is-private">Private tour<small>Quote on request — no online booking.</small></label></div>
      <div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="tf-is-cruise" name="is_cruise" value="1" @checked(old('is_cruise',$tour->is_cruise))><label class="form-check-label" for="tf-is-cruise">Cruise<small>Adds the cruise details section.</small></label></div>
    </div>
    <div class="d-grid gap-2 mt-3"><button class="btn btn-primary btn-lg"><i class="ri-save-line align-middle me-1"></i> {{ $tour->exists ? 'Save changes' : 'Create tour' }}</button>
      <div class="d-flex gap-2">@if($publicUrl)<a class="btn btn-outline-secondary flex-fill" href="{{ $publicUrl }}" target="_blank" rel="noopener"><i class="ri-external-link-line align-middle me-1"></i> View on site</a>@endif<a class="btn btn-light flex-fill" href="{{route('tours.index')}}"><i class="ri-arrow-left-line align-middle me-1"></i> All tours</a></div></div>
  </div></div>

  {{-- Main image --}}
  <div class="card"><div class="card-body">
    <h5 class="fs-6 fw-semibold mb-3">Main image</h5>
    @if($tour->main_image)<a href="{{ $tour->main_image }}" target="_blank" rel="noopener"><img src="{{ $tour->main_image }}" alt="" class="tf-image mb-3"></a>@else<div class="tf-image-empty mb-3"><span><i class="ri-image-line fs-3 d-block text-center"></i>No image yet</span></div>@endif
    <label class="fw-semibold" for="tf-main-image">Upload a new image</label>
    <input id="tf-main-image" type="file" class="form-control @error('main_image') is-invalid @enderror" name="main_image" accept="image/*,.heic,.heif">@error('main_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <input class="form-control mt-2" name="main_image_path" value="{{ old('main_image_path', $tour->getRawOriginal('main_image')) }}" placeholder="or paste an image URL / stored file name" aria-label="Image URL or stored file name">
    <div class="form-text">Wide photo (16:9) works best. Used on cards, the tour page and link previews.</div>
  </div></div>

  {{-- Jump to a section --}}
  <div class="card d-none d-xl-block"><div class="card-body py-2">
    <nav class="tf-nav nav flex-column" aria-label="Sections of this form">
      <a href="#basics"><i class="ri-file-text-line"></i> Basics</a>
      <a href="#seo-card"><i class="ri-search-eye-line"></i> Search engines</a>
      <a href="#placement"><i class="ri-map-pin-line"></i> Where it appears</a>
      <a href="#details"><i class="ri-time-line"></i> Trip details</a>
      <a href="#inclusions"><i class="ri-checkbox-multiple-line"></i> Included / not included</a>
      <a href="#fares"><i class="ri-money-dollar-circle-line"></i> Fares & payment</a>
      <a href="#policies"><i class="ri-file-shield-2-line"></i> Policies & rules</a>
      <a href="#booking-form"><i class="ri-survey-line"></i> Booking form</a>
      <a href="#cruise" data-tf-cruise-link @unless(old('is_cruise',$tour->is_cruise)) hidden @endunless><i class="ri-ship-line"></i> Cruise details</a>
      <a href="#departures"><i class="ri-calendar-event-line"></i> Dates & prices</a>
      @if($tour->exists)<a href="#itinerary"><i class="ri-route-line"></i> Itinerary</a><a href="#additional"><i class="ri-information-line"></i> Additional information</a>@endif
    </nav>
  </div></div>
</div></div>{{-- /sidebar --}}
</div>

<div class="tf-savebar"><span class="tf-dirty text-warning align-items-center gap-1"><i class="ri-edit-2-line"></i> Unsaved changes</span><span></span><button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ $tour->exists ? 'Save changes' : 'Create tour' }}</button></div>
</form>
<script>
(function () {
  const form = document.getElementById('tour-main-form'); if (!form) return;
  // Cruise section follows the “Cruise” switch.
  const cruise = document.getElementById('tf-is-cruise');
  const toggleCruise = () => { document.getElementById('cruise').classList.toggle('d-none', !cruise.checked); document.querySelectorAll('[data-tf-cruise-link]').forEach((a) => a.hidden = !cruise.checked); };
  cruise?.addEventListener('change', toggleCruise);
  // “x selected” next to each inclusions list.
  const countInclusions = () => document.querySelectorAll('[data-inclusion-type]').forEach((box) => {
    const n = box.querySelectorAll('input:checked').length;
    const badge = document.querySelector('[data-inclusion-count="' + box.dataset.inclusionType + '"]');
    if (badge) badge.textContent = n ? n + ' selected' : '';
  });
  document.getElementById('inclusions')?.addEventListener('change', countInclusions); countInclusions();
  // Warn before leaving with unsaved changes (fields of the main form only).
  let dirty = false, submitting = false;
  const markDirty = (e) => { if (e.target.form === form || e.target.getAttribute?.('form') === 'tour-main-form') { dirty = true; document.body.classList.add('tf-is-dirty'); } };
  document.addEventListener('input', markDirty); document.addEventListener('change', markDirty);
  if (window.jQuery) jQuery(document).on('change', '#tour-main-form .js-select2-multi', () => { dirty = true; document.body.classList.add('tf-is-dirty'); });
  form.addEventListener('submit', () => { submitting = true; });
  window.addEventListener('beforeunload', (e) => { if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; } });
  // Highlight the section in view in the side menu.
  const links = [...document.querySelectorAll('.tf-nav a')];
  if ('IntersectionObserver' in window && links.length) {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => { if (en.isIntersecting) links.forEach((a) => a.classList.toggle('active', a.getAttribute('href') === '#' + en.target.id)); }), { rootMargin: '-90px 0px -60% 0px' });
    links.forEach((a) => { const el = document.querySelector(a.getAttribute('href')); if (el) io.observe(el); });
  }
})();
</script>
@include('admin.tours.departures')
@if($tour->exists)
<div class="card" id="itinerary" style="scroll-margin-top:90px"><div class="card-body"><h5>Itinerary</h5>@can('create-tour-itineraries')<form class="row g-2 mb-4" method="post" action="{{route('tour-itinerary-days.store',$tour)}}">@csrf<div class="col-md-2"><input type="number" min="1" class="form-control" name="day_number" placeholder="Day" required></div><div class="col-md-4"><input class="form-control" name="en[title]" placeholder="Day title"></div><div class="col-md-4"><input class="form-control" name="en[description]" placeholder="Description"></div><div class="col-md-2"><button class="btn btn-primary">Add day</button></div></form>@endcan
<p class="text-muted small">Click <b>Edit</b> on any stop to change its time, title, description or other details. Saving brings you back to the same stop.</p>
@foreach($tour->itineraryDays as $day)<div class="border rounded p-3 mb-3" id="day-{{$day->id}}" style="scroll-margin-top:90px">
<div class="d-flex justify-content-between align-items-start gap-2">
@can('edit-tour-itineraries')<form class="row g-2 flex-grow-1" method="post" action="{{route('tour-itinerary-days.update',[$tour,$day])}}">@csrf @method('PUT')<div class="col-md-1"><input type="number" min="1" class="form-control fw-semibold" name="day_number" value="{{$day->day_number}}" title="Day number" required></div><div class="col-md-4"><input class="form-control" name="en[title]" value="{{$day->title}}" placeholder="Day title"></div><div class="col-md-5"><input class="form-control" name="en[description]" value="{{$day->description}}" placeholder="Day description"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Save day</button></div></form>@else<h6>Day {{$day->day_number}} — {{$day->title}}</h6>@endcan
@can('delete-tour-itineraries')<form method="post" action="{{route('tour-itinerary-days.destroy',[$tour,$day])}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this day and all its stops?')">Delete day</button></form>@endcan
</div>
<ul class="list-group my-2">@foreach($day->items as $stop)<li class="list-group-item" id="stop-{{$stop->id}}" style="scroll-margin-top:90px"><details><summary class="d-flex justify-content-between align-items-center gap-2" style="list-style:none;cursor:pointer"><span><b>{{substr((string)$stop->arrival_time,0,5) ?: '—'}}</b> {{$stop->location_name}}@if($stop->is_optional) <span class="badge bg-info">Optional</span>@endif @if($stop->own_expense) <span class="badge bg-secondary">Own account</span>@endif @if($stop->description)<span class="text-muted small d-block">{{Str::limit($stop->description,110)}}</span>@endif</span><span class="d-flex gap-1 flex-shrink-0">@can('edit-tour-itineraries')<span class="btn btn-sm btn-outline-primary"><i class="ri-pencil-line"></i> Edit</span>@endcan</span></summary>
@can('edit-tour-itineraries')<form class="row g-2 mt-2 pt-2 border-top" method="post" enctype="multipart/form-data" action="{{route('tour-itinerary-items.update',[$day,$stop])}}">@csrf @method('PUT')@include('admin.tours.itinerary_stop_fields',['stop'=>$stop])<div class="col-12 d-flex gap-2"><button class="btn btn-primary btn-sm"><i class="ri-save-line"></i> Save stop</button></div></form>@endcan
</details>@can('delete-tour-itineraries')<form method="post" action="{{route('tour-itinerary-items.destroy',[$day,$stop])}}" class="mt-2 text-end">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Delete this stop?')"><i class="ri-delete-bin-line"></i> Delete stop</button></form>@endcan</li>@endforeach</ul>
@can('create-tour-itineraries')<details class="mt-2"><summary class="btn btn-sm btn-light"><i class="ri-add-line align-middle"></i> Add a stop to day {{$day->day_number}}</summary><form class="row g-2 mt-2" method="post" enctype="multipart/form-data" action="{{route('tour-itinerary-items.store',$day)}}">@csrf @include('admin.tours.itinerary_stop_fields',['stop'=>null])<div class="col-12"><button class="btn btn-sm btn-primary"><i class="ri-add-line align-middle me-1"></i> Add stop</button></div></form></details>@endcan
</div>@endforeach</div></div>
<div class="card tf-section" id="additional"><div class="card-body"><h5>Additional information</h5><p class="text-muted small">What to bring, documents, children, seniors and FAQ for this tour. Included / not included lines are ticked above under “What’s included”.</p>@can('create-tour-content')<form class="row g-2 mb-3" method="post" action="{{route('tour-content-items.store',$tour)}}">@csrf<div class="col-md-2"><select class="form-select" name="type">@foreach(['what_to_bring','document_requirement','children','seniors','faq'] as $type)<option>{{$type}}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="icon"><option value="">No icon</option>@foreach(\App\Models\TourContentItem::ICONS as $icon=>$label)<option value="{{$icon}}">{{$icon}} {{$label}}</option>@endforeach</select></div><div class="col-md-3"><input class="form-control" name="en[title]" placeholder="Title" required></div><div class="col-md-3"><input class="form-control" name="en[description]" placeholder="Description"></div><div class="col-md-2"><button class="btn btn-primary">Add item</button></div></form>@endcan<div class="table-responsive"><table class="table"><thead><tr><th>Type</th><th>Title</th><th>Description</th><th></th></tr></thead><tbody>@foreach($tour->contentItems as $content)<tr><td>{{$content->type}}</td><td>{{$content->icon}} {{$content->title}}</td><td>{{$content->description}}</td><td>@can('delete-tour-content')<form method="post" action="{{route('tour-content-items.destroy',[$tour,$content])}}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="ri-delete-bin-line"></i></button></form>@endcan</td></tr>@endforeach</tbody></table></div></div></div>
@endif</div></div></div>@include('admin.includes.footer')
