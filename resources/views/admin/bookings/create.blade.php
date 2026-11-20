@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-newbooking.css') }}?v={{ @filemtime(public_path('assets/css/admin-newbooking.css')) }}">
@php($statusHints = ['new' => 'Not contacted about payment yet.', 'payment_pending' => 'Waiting for the deposit. Most phone bookings.', 'confirmed' => 'Seats confirmed. You can email the confirmation.'])
@php($oldStatus = old('status', 'payment_pending'))
@php($selectedDep = old('tour_departure_id', $waitlist?->tour_departure_id ?? request('departure')))
<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="nb-header">
  <a class="btn btn-light nb-back" href="{{ route('bookings.index') }}" title="Back to bookings" aria-label="Back to bookings"><i class="ri-arrow-left-line"></i></a>
  <div class="min-w-0">
    <h4>New booking</h4>
    <div class="nb-meta">Work top to bottom while the customer is on the line. Prices come from the date's fares; payments are recorded on the booking page afterwards.</div>
  </div>
</div>

@if ($errors->any())
  <div class="alert alert-danger nb-errors" role="alert">
    <div class="d-flex gap-2"><i class="ri-error-warning-line fs-5 lh-1"></i><div><b>The booking was not created.</b> Fix {{ $errors->count() === 1 ? 'the field' : 'the '.$errors->count().' fields' }} marked in red below.
      @if($errors->has('capacity') || $errors->has('passengers'))<ul class="mb-0 mt-1 ps-3">@foreach(['capacity', 'passengers'] as $k)@foreach($errors->get($k) as $m)<li>{{ $m }}</li>@endforeach @endforeach</ul>@endif
    </div></div>
  </div>
@endif

<form method="post" action="{{ route('bookings.store') }}" id="new-booking" novalidate>@csrf
<input type="hidden" name="customer_id" id="customer-id" value="{{ old('customer_id', $customer?->id) }}">
@if($waitlist)
  <input type="hidden" name="waitlist_entry_id" value="{{ $waitlist->id }}">
  <div class="alert alert-info"><i class="ri-list-ordered me-1"></i>Booking <b>{{ $waitlist->full_name }}</b> from the waitlist ({{ $waitlist->guests }} {{ str('seat')->plural($waitlist->guests) }}). Their waitlist entry closes when this booking is saved.</div>
@endif

{{-- Step bar: jump to a step, ticks turn green when the step is complete --}}
<nav class="nb-steps" aria-label="Booking steps">
  <a href="#step-customer" data-step="customer"><span class="nb-step-num">1</span><span class="nb-step-label">Customer</span></a>
  <a href="#step-trip" data-step="trip"><span class="nb-step-num">2</span><span class="nb-step-label">Trip & date</span></a>
  <a href="#step-pax" data-step="pax"><span class="nb-step-num">3</span><span class="nb-step-label">Passengers & fares</span></a>
  <a href="#step-payment" data-step="payment"><span class="nb-step-num">4</span><span class="nb-step-label">Payment & notes</span></a>
  <a href="#step-review" data-step="review"><span class="nb-step-num">5</span><span class="nb-step-label">Review</span></a>
</nav>

<div class="row g-3">
  <div class="col-xl-8 tf-form">

    {{-- 1. Customer --}}
    <div class="card nb-card" id="step-customer"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-user-3-line"></i></span><div><h5>1. Customer</h5><p>Who is booking and how to reach them.</p></div></div>
      @can('list-customers')
      <div class="mb-3 nb-lookup">
        <label class="fw-semibold form-label" for="customer-search">Find an existing customer</label>
        <select id="customer-search" class="form-select" style="width:100%"></select>
        <div class="form-text">Type 2+ letters of a name, email or phone. New caller? Just fill in the contact below: a customer with the same email (or phone) is linked automatically, otherwise a new one is created.</div>
      </div>
      <div class="nb-linked {{ old('customer_id', $customer?->id) ? '' : 'd-none' }}" id="customer-linked">
        <i class="ri-links-line"></i>
        <div class="min-w-0 flex-grow-1">Linked to existing customer <b id="customer-linked-name">{{ $customer?->full_name }}</b><div class="nb-sub">Their details are filled in below. Edits update this booking only.</div></div>
        <a href="#" class="btn btn-light" id="customer-unlink"><i class="ri-close-line me-1"></i>Unlink</a>
      </div>
      @endcan
      <div class="row g-3">
        <div class="col-md-6"><label class="fw-semibold form-label tf-req" for="b-first">First name</label><input class="form-control @error('first_name') is-invalid @enderror" id="b-first" name="first_name" value="{{ old('first_name', $customer?->first_name ?? $waitlist?->first_name) }}" required maxlength="100" autocomplete="off">@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="fw-semibold form-label tf-req" for="b-last">Last name</label><input class="form-control @error('last_name') is-invalid @enderror" id="b-last" name="last_name" value="{{ old('last_name', $customer?->last_name ?? $waitlist?->last_name) }}" required maxlength="100" autocomplete="off">@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="fw-semibold form-label tf-req" for="b-email">Email</label><input class="form-control @error('email') is-invalid @enderror" type="email" inputmode="email" id="b-email" name="email" value="{{ old('email', $customer?->email ?? $waitlist?->email) }}" required autocomplete="off">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="fw-semibold form-label tf-req" for="b-phone">Phone</label><input class="form-control @error('phone') is-invalid @enderror" type="tel" id="b-phone" name="phone" value="{{ old('phone', $customer?->phone ?? $waitlist?->phone) }}" required maxlength="30" autocomplete="off">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="fw-semibold form-label" for="b-wa">WhatsApp <span class="text-muted fw-normal">(optional)</span></label><input class="form-control @error('whatsapp') is-invalid @enderror" type="tel" id="b-wa" name="whatsapp" value="{{ old('whatsapp', $customer?->whatsapp) }}" maxlength="30" autocomplete="off">@error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <button type="button" class="btn btn-link nb-link-btn" id="wa-same"><i class="ri-whatsapp-line me-1"></i>Same as phone</button></div>
      </div>
    </div></div>

    {{-- 2. Trip & date --}}
    <div class="card nb-card" id="step-trip"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-route-line"></i></span><div><h5>2. Trip & date</h5><p>Pick the departure. Seats left and fares are shown for each date.</p></div></div>

      {{-- The real field (submitted). The list below is a faster way to set it. --}}
      <label class="visually-hidden" for="b-dep">Departure</label>
      <select class="form-select visually-hidden" id="b-dep" name="tour_departure_id" required tabindex="-1">
        <option value="">Choose a date…</option>
        @foreach(collect($departures)->groupBy('tour') as $tourName => $dates)
          <optgroup label="{{ $tourName }}">@foreach($dates as $o)<option value="{{ $o['id'] }}" @selected($selectedDep == $o['id'])>{{ $o['label'] }} · {{ $o['left'] }} {{ str('seat')->plural($o['left']) }} left{{ $o['left'] ? '' : ' (full)' }}</option>@endforeach</optgroup>
        @endforeach
      </select>

      <div class="nb-dep-chosen d-none" id="dep-chosen">
        <div class="min-w-0 flex-grow-1">
          <div class="nb-dep-tour" id="dep-chosen-tour"></div>
          <div class="nb-dep-date" id="dep-chosen-date"></div>
          <div class="nb-fare-chips" id="dep-chosen-fares"></div>
        </div>
        <div class="text-end flex-shrink-0">
          <div id="dep-chosen-seats"></div>
          <button type="button" class="btn btn-light mt-2" id="dep-change"><i class="ri-edit-line me-1"></i>Change</button>
        </div>
      </div>
      <div class="form-text" id="dep-info"></div>
      @error('tour_departure_id')<div class="nb-field-error">{{ $message }}</div>@enderror
      @error('capacity')<div class="nb-field-error">{{ $message }}</div>@enderror
      <div class="nb-field-error d-none" id="dep-error">Choose a departure date.</div>

      <div id="dep-picker">
        @if(count($departures))
          <label class="tl-search-field nb-dep-search"><i class="ri-search-line"></i><input type="search" class="form-control" id="dep-filter" placeholder="Filter by trip name or date, e.g. “Niagara” or “Dec 6”" autocomplete="off" aria-label="Filter departures"></label>
          <div class="nb-dep-list" id="dep-list" role="listbox" aria-label="Departures">
            @foreach(collect($departures)->groupBy('tour') as $tourName => $dates)
              <div class="nb-dep-group" data-group>
                <div class="nb-dep-group-title">{{ $tourName }}</div>
                @foreach($dates as $o)
                  @php($from = collect($o['fares'])->map(fn ($f) => (float) $f['price'])->filter(fn ($p) => $p > 0)->min())
                  <button type="button" class="nb-dep-opt" role="option" data-dep="{{ $o['id'] }}" data-search="{{ mb_strtolower($tourName.' '.$o['label']) }}">
                    <span class="nb-dep-opt-date"><i class="ri-calendar-event-line"></i>{{ $o['label'] }}</span>
                    <span class="nb-dep-opt-price">{{ $from ? 'from '.money($from, $o['currency']) : 'No fares' }}</span>
                    <span class="badge {{ $o['left'] === 0 ? 'bg-danger' : ($o['left'] <= 5 ? 'bg-warning' : 'bg-success') }}">{{ $o['left'] ? $o['left'].' '.str('seat')->plural($o['left']).' left' : 'Full' }}</span>
                  </button>
                @endforeach
              </div>
            @endforeach
            <div class="nb-empty d-none" id="dep-none"><i class="ri-search-line"></i><b>No dates match</b>Try the trip name or a month, e.g. “Dec”.</div>
          </div>
        @else
          <div class="nb-empty"><i class="ri-calendar-line"></i><b>No upcoming departures</b>Add dates to a tour first, then come back to book.</div>
        @endif
      </div>
      <div class="nb-override">@include('admin.includes.capacity_override', ['id' => 'cap-new'])</div>
    </div></div>

    {{-- 3. Passengers & fares --}}
    <div class="card nb-card" id="step-pax"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-group-line"></i></span><div><h5>3. Passengers & fares</h5><p>One row per traveller. Passenger 1 is filled from the customer.</p></div></div>
      <div id="pax"></div>
      @error('passengers')<div class="nb-field-error mb-2">{{ $message }}</div>@enderror
      <div class="nb-empty" id="pax-hint"><i class="ri-team-line"></i><b>Choose a departure first</b>Its fares, pickup points and meals appear here.</div>
      <div class="nb-pax-actions" id="pax-actions">
        <button type="button" class="btn btn-light" id="add-pax"><i class="ri-user-add-line me-1"></i>Add passenger</button>
        <span id="pax-quick" class="nb-pax-quick"></span>
        <button type="button" class="btn btn-link nb-link-btn d-none" id="pax-same-pickup"><i class="ri-map-pin-line me-1"></i>Use passenger 1's pickup for everyone</button>
      </div>
    </div></div>

    {{-- 4. Payment & notes --}}
    <div class="card nb-card" id="step-payment"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-wallet-3-line"></i></span><div><h5>4. Payment & notes</h5><p>Booking status and anything the team should know. No money is taken on this page.</p></div></div>
      <div class="fw-semibold mb-2" id="b-status-label">Status</div>
      <div class="nb-status" role="radiogroup" aria-labelledby="b-status-label" id="b-status">
        @foreach(\App\Http\Controllers\Admin\ManualBookingController::STATUSES as $s)
          <input type="radio" class="btn-check" name="status" value="{{ $s }}" id="b-status-{{ $s }}" @checked($oldStatus === $s)>
          <label class="nb-status-opt" for="b-status-{{ $s }}"><b>{{ \App\Models\Booking::STATUSES[$s] }}</b><span>{{ $statusHints[$s] ?? '' }}</span></label>
        @endforeach
      </div>
      @error('status')<div class="nb-field-error">{{ $message }}</div>@enderror
      <div class="form-check tf-subtle nb-send mt-2 {{ $oldStatus === 'confirmed' ? '' : 'd-none' }}" id="send-wrap"><input class="form-check-input" type="checkbox" name="send_confirmation" value="1" id="b-send" @checked(old('send_confirmation'))><label class="form-check-label" for="b-send">Email the booking confirmation to the customer now</label></div>
      <div class="nb-pay-note"><i class="ri-information-line"></i><span>The deposit due is shown in the summary. After saving, use <b>Record payment</b> on the booking page if the customer pays now.</span></div>
      <div class="row g-3 mt-1">
        <div class="col-md-6"><label class="fw-semibold form-label" for="b-comments">Customer's request <span class="text-muted fw-normal">(optional)</span></label>
          <textarea class="form-control @error('comments') is-invalid @enderror" id="b-comments" name="comments" rows="3" maxlength="2000" placeholder="e.g. window seats, celebrating a birthday">{{ old('comments') }}</textarea>@error('comments')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="fw-semibold form-label" for="b-notes">Internal notes <span class="text-muted fw-normal">(staff only)</span></label>
          <textarea class="form-control @error('internal_notes') is-invalid @enderror" id="b-notes" name="internal_notes" rows="3" maxlength="5000" placeholder="e.g. will pay by e-transfer on Friday">{{ old('internal_notes') }}</textarea>@error('internal_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      </div>
    </div></div>
  </div>

  {{-- 5. Review: live summary + the one primary action --}}
  <div class="col-xl-4">
    <div class="nb-side" id="step-review">
      <div class="card nb-card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-file-list-3-line"></i></span><div><h5>5. Review</h5><p>Read it back to the customer before you save.</p></div></div>

        <div class="nb-rv-block">
          <div class="nb-rv-label">Customer</div>
          <div class="nb-rv-value" id="rv-customer"><span class="text-muted">Not filled in yet</span></div>
        </div>
        <div class="nb-rv-block">
          <div class="nb-rv-label">Trip</div>
          <div class="nb-rv-value" id="rv-trip"><span class="text-muted">No date chosen</span></div>
        </div>
        <div class="nb-rv-block">
          <div class="nb-rv-label d-flex justify-content-between"><span>Passengers</span><b id="sum-pax">0</b></div>
          <div id="rv-lines" class="nb-rv-lines"></div>
        </div>

        <div class="nb-totals">
          <div class="nb-total-row nb-grand"><span>Total</span><b id="sum-total">—</b></div>
          <div class="nb-total-row"><span>Deposit due</span><b id="sum-deposit">—</b></div>
          <div class="nb-total-row nb-muted"><span>Balance later</span><span id="sum-balance">—</span></div>
        </div>

        <div class="nb-checklist" id="rv-missing"></div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" id="nb-submit"><i class="ri-check-line me-1"></i>Create booking</button>
        <a class="btn btn-light w-100 mt-2" href="{{ route('bookings.index') }}">Cancel</a>
        <p class="nb-fineprint">Prices and the deposit are recalculated from the date's fares when you save. Discounts are added on the booking page.</p>
      </div></div>
    </div>
  </div>
</div>

{{-- Phone: the total and the action stay in reach at the bottom of the screen --}}
<div class="nb-mobilebar">
  <div class="min-w-0"><div class="nb-mobilebar-total" id="mb-total">—</div><div class="nb-sub" id="mb-sub">0 passengers</div></div>
  <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>Create booking</button>
</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const deps = @json($departures);
  const old = @json(old('passengers', $prefill));
  const serverErrors = @json($errors->getMessages());
  const $ = id => document.getElementById(id);
  const form = $('new-booking');
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = (n, cur) => new Intl.NumberFormat('en-CA', { style: 'currency', currency: cur || 'CAD' }).format(n);
  const fmtShort = (n, cur) => fmt(n, cur).replace(/\.00$/, '');
  const plural = (k, w) => `${k} ${w}${k === 1 ? '' : 's'}`;
  let n = 0;

  function current() { return deps[$('b-dep').value] || null; }

  // ---------- Field-level validation messages ----------
  function feedbackFor(el) {
    let fb = el.parentElement.querySelector(':scope > .invalid-feedback');
    if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; el.insertAdjacentElement('afterend', fb); }
    return fb;
  }
  function setError(el, msg) { el.classList.add('is-invalid'); feedbackFor(el).textContent = msg; }
  function clearError(el) { el.classList.remove('is-invalid'); }
  function messageFor(el) {
    const label = (el.closest('[class*="col"]')?.querySelector('label')?.textContent || 'This field').replace(/\(.*\)/, '').trim();
    if (el.validity.valueMissing) return `Enter the ${label.toLowerCase()}.`;
    if (el.validity.typeMismatch && el.type === 'email') return 'Enter a valid email, e.g. name@example.com.';
    if (el.validity.tooLong) return `${label} is too long.`;
    return el.validationMessage;
  }
  form.addEventListener('input', e => { if (e.target.classList?.contains('is-invalid') && e.target.checkValidity()) clearError(e.target); });
  form.addEventListener('change', e => { if (e.target.classList?.contains('is-invalid') && e.target.checkValidity()) clearError(e.target); });

  // ---------- Passenger rows ----------
  function row(p = {}) {
    const d = current(); if (!d) return;
    const i = n++;
    const el = document.createElement('div');
    el.className = 'nb-pax'; el.dataset.row = i;
    el.innerHTML = `<div class="nb-pax-head"><span class="nb-pax-title" data-title></span>
        <button type="button" class="btn btn-light nb-pax-remove" data-remove title="Remove passenger" aria-label="Remove passenger"><i class="ri-delete-bin-line"></i></button></div>
      <div class="row g-2">
      <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold tf-req">First name</label><input class="form-control" name="passengers[${i}][first_name]" value="${esc(p.first_name)}" required maxlength="100" autocomplete="off"></div>
      <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold tf-req">Last name</label><input class="form-control" name="passengers[${i}][last_name]" value="${esc(p.last_name)}" required maxlength="100" autocomplete="off"></div>
      <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold tf-req">Fare</label><select class="form-select" name="passengers[${i}][fare]" data-fare>${d.fares.map(f => `<option value="${esc(f.code)}" data-price="${f.price}" ${p.fare === f.code ? 'selected' : ''}>${esc(f.label)} · ${fmtShort(f.price, d.currency)}</option>`).join('')}</select></div>
      <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Pickup</label><select class="form-select" name="passengers[${i}][pickup_point_id]" data-pickup><option value="">${d.pickups.length ? 'Choose later' : 'No pickups on this date'}</option>${d.pickups.map(s => `<option value="${s.id}" ${String(p.pickup_point_id) === String(s.id) ? 'selected' : ''}>${esc(s.name)}</option>`).join('')}</select></div>
      ${d.meals.length ? `<div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Meal</label><select class="form-select" name="passengers[${i}][meal_selection]"><option value="">Choose later</option>${d.meals.map(m => `<option ${p.meal_selection === m ? 'selected' : ''}>${esc(m)}</option>`).join('')}</select></div>` : ''}
      <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Phone <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" type="tel" name="passengers[${i}][phone]" value="${esc(p.phone)}" maxlength="30" autocomplete="off"></div>
    </div>`;
    el.querySelector('[data-remove]').addEventListener('click', () => { if (document.querySelectorAll('[data-row]').length > 1) { el.remove(); summary(); } });
    el.querySelector('[data-fare]').addEventListener('change', summary);
    el.querySelector('[data-pickup]').addEventListener('change', summary);
    el.addEventListener('input', summary);
    $('pax').appendChild(el);
    return el;
  }

  function seatsNeeded(d, rows) {
    const lap = !d.infant_needs_seat ? rows.filter(s => s.value === 'infant').length : 0;
    return rows.length - lap;
  }

  // ---------- Live summary / review ----------
  function summary() {
    const d = current(), rows = [...document.querySelectorAll('[data-fare]')];
    const cur = d?.currency;
    // Row titles and remove buttons
    document.querySelectorAll('[data-row]').forEach((el, k, all) => {
      el.querySelector('[data-title]').innerHTML = `Passenger ${k + 1}` + (k === 0 ? ' <span class="badge bg-secondary">Lead</span>' : '');
      el.querySelector('[data-remove]').disabled = all.length < 2;
    });
    $('pax-same-pickup').classList.toggle('d-none', !(d && d.pickups.length && rows.length > 1));

    const total = rows.reduce((t, s) => t + parseFloat(s.selectedOptions[0]?.dataset.price || 0), 0);
    const paying = rows.filter(s => parseFloat(s.selectedOptions[0]?.dataset.price || 0) > 0).length;
    // Same rule as the server (ManualBookingController::depositRule): per paying passenger, else the default percentage.
    const dpp = d?.deposit_per_person ? parseFloat(d.deposit_per_person) : null;
    const deposit = dpp ? Math.min(total, dpp * paying) : Math.round(total * (d?.deposit_percent ?? 0)) / 100;
    const ready = d && rows.length;
    $('sum-pax').textContent = rows.length;
    $('sum-total').textContent = ready ? fmt(total, cur) : '—';
    $('sum-deposit').textContent = ready ? fmt(deposit, cur) : '—';
    $('sum-balance').textContent = ready ? fmt(Math.max(0, total - deposit), cur) : '—';
    $('mb-total').textContent = ready ? fmt(total, cur) : '—';
    $('mb-sub').textContent = plural(rows.length, 'passenger') + (ready ? ` · deposit ${fmt(deposit, cur)}` : '');

    // Fare lines (2 × Adult)
    const lines = {};
    rows.forEach(s => { const o = s.selectedOptions[0]; if (!o) return; const k = s.value; lines[k] ??= { label: o.textContent.split(' · ')[0], price: parseFloat(o.dataset.price || 0), qty: 0 }; lines[k].qty++; });
    $('rv-lines').innerHTML = Object.values(lines).map(l => `<div class="nb-rv-line"><span>${l.qty} × ${esc(l.label)}</span><span>${fmt(l.qty * l.price, cur)}</span></div>`).join('') || '<span class="text-muted">None yet</span>';

    // Seats
    const need = d ? seatsNeeded(d, rows) : 0, over = d && need > d.left;
    $('dep-info').textContent = d ? `${d.status} · ${plural(d.left, 'seat')} left` + (rows.length ? ` · this booking needs ${need}` : '') + (over ? ' — not enough seats.' : '') : '';
    $('dep-info').className = 'form-text ' + (over ? 'text-danger fw-semibold' : '');
    // The over-capacity switch only matters when seats run short (or it is already in use).
    const ov = document.querySelector('.nb-override'), ovBox = ov?.querySelector('input[type=checkbox]');
    if (ov) ov.classList.toggle('d-none', !(over || ovBox?.checked || d?.left === 0));

    // Customer + trip
    const name = `${$('b-first').value} ${$('b-last').value}`.trim();
    const contact = [$('b-email').value, $('b-phone').value].filter(Boolean).map(esc).join(' · ');
    const linked = $('customer-id').value;
    $('rv-customer').innerHTML = name || contact ? `<b>${esc(name) || '—'}</b>${linked ? ' <span class="badge bg-info">Existing</span>' : ''}<div class="nb-sub">${contact || 'No email or phone yet'}</div>` : '<span class="text-muted">Not filled in yet</span>';
    $('rv-trip').innerHTML = d ? `<b>${esc(d.tour)}</b><div class="nb-sub">${esc(d.label)}</div><div class="nb-sub ${over ? 'text-danger fw-semibold' : ''}">${need ? `${plural(need, 'seat')} of ${d.left} left` : plural(d.left, 'seat') + ' left'}</div>` : '<span class="text-muted">No date chosen</span>';

    // Steps + checklist
    const custOk = ['b-first', 'b-last', 'b-email', 'b-phone'].every(id => $(id).checkValidity());
    const paxOk = rows.length > 0 && [...$('pax').querySelectorAll('[required]')].every(f => f.checkValidity());
    const state = { customer: custOk, trip: !!d, pax: paxOk, payment: !!form.querySelector('[name="status"]:checked') && custOk && !!d && paxOk, review: custOk && !!d && paxOk };
    Object.entries(state).forEach(([k, ok]) => form.querySelector(`[data-step="${k}"]`).classList.toggle('done', ok));
    const missing = [];
    if (!custOk) missing.push(['#step-customer', 'Customer name, email and phone']);
    if (!d) missing.push(['#step-trip', 'A departure date']);
    else if (!paxOk) missing.push(['#step-pax', 'Every passenger\'s first and last name']);
    if (over) missing.push(['#step-trip', `Not enough seats (${need} needed, ${d.left} left)` + (document.querySelector('[name="override_reason"]') ? ': book over capacity with a reason' : '')]);
    $('rv-missing').innerHTML = missing.length ? `<div class="nb-checklist-title"><i class="ri-alert-line"></i>Still needed</div>` + missing.map(([h, t]) => `<a href="${h}">${esc(t)}</a>`).join('')
      : '<div class="nb-checklist-ok"><i class="ri-checkbox-circle-fill"></i>Ready to create</div>';
  }

  // ---------- Departure picker ----------
  function showDeparture() {
    const d = current();
    document.querySelectorAll('[data-dep]').forEach(b => { const on = d && b.dataset.dep === String(d.id); b.classList.toggle('active', !!on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
    $('dep-chosen').classList.toggle('d-none', !d);
    $('dep-picker').classList.toggle('d-none', !!d);
    if (d) {
      $('dep-error').classList.add('d-none');
      $('dep-chosen-tour').textContent = d.tour;
      $('dep-chosen-date').textContent = d.label;
      $('dep-chosen-fares').innerHTML = d.fares.map(f => `<span>${esc(f.label)} <b>${fmt(f.price, d.currency)}</b></span>`).join('') || '<span>No fares on this date</span>';
      const pct = d.capacity ? Math.min(100, Math.round(d.taken / d.capacity * 100)) : 0;
      $('dep-chosen-seats').innerHTML = `<span class="badge ${d.left === 0 ? 'bg-danger' : d.left <= 5 ? 'bg-warning' : 'bg-success'}">${d.left ? plural(d.left, 'seat') + ' left' : 'Full'}</span>`
        + (d.capacity ? `<div class="nb-seatbar" title="${d.taken} of ${d.capacity} seats taken"><span style="width:${pct}%" class="${pct >= 90 ? 'hot' : ''}"></span></div><div class="nb-sub">${d.taken} of ${d.capacity} taken</div>` : '');
    }
  }
  document.querySelectorAll('[data-dep]').forEach(b => b.addEventListener('click', () => {
    $('b-dep').value = b.dataset.dep; $('b-dep').dispatchEvent(new Event('change'));
    if (!$('pax').querySelector('input')?.value) $('pax').querySelector('input')?.focus();
  }));
  $('dep-change')?.addEventListener('click', () => { $('dep-picker').classList.remove('d-none'); $('dep-chosen').classList.add('d-none'); $('dep-filter')?.focus(); });
  $('dep-filter')?.addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase(); let any = false;
    document.querySelectorAll('[data-group]').forEach(g => {
      let shown = 0;
      g.querySelectorAll('[data-dep]').forEach(b => { const ok = !q || b.dataset.search.includes(q); b.classList.toggle('d-none', !ok); shown += ok; });
      g.classList.toggle('d-none', !shown); any ||= shown > 0;
    });
    $('dep-none').classList.toggle('d-none', any);
  });

  function rebuild(keep) {
    // Keep typed names when the date changes; fares and pickups follow the new date.
    const typed = keep ?? [...document.querySelectorAll('[data-row]')].map(el => ({ first_name: el.querySelector('[name$="[first_name]"]').value, last_name: el.querySelector('[name$="[last_name]"]').value, phone: el.querySelector('[name$="[phone]"]').value, fare: el.querySelector('[data-fare]').value }));
    $('pax').innerHTML = ''; n = 0;
    const d = current();
    $('pax-hint').classList.toggle('d-none', !!d); $('add-pax').disabled = !d;
    $('pax-actions').classList.toggle('d-none', !d);
    $('pax-quick').innerHTML = d ? d.fares.map(f => `<button type="button" class="btn btn-light" data-add-fare="${esc(f.code)}"><i class="ri-add-line me-1"></i>${esc(f.label.replace(/\s*\(.*\)$/, ''))}</button>`).join('') : '';
    if (d) (typed.length ? typed : [{ first_name: $('b-first').value, last_name: $('b-last').value }]).forEach(p => row(p));
    showDeparture();
    summary();
  }

  $('b-dep').addEventListener('change', () => rebuild());
  $('add-pax').addEventListener('click', () => { row({ last_name: $('b-last').value })?.querySelector('input').focus(); summary(); });
  $('pax-quick').addEventListener('click', e => { const b = e.target.closest('[data-add-fare]'); if (!b) return; row({ last_name: $('b-last').value, fare: b.dataset.addFare })?.querySelector('input').focus(); summary(); });
  $('pax-same-pickup').addEventListener('click', () => { const all = [...document.querySelectorAll('[data-pickup]')]; all.forEach(s => { s.value = all[0].value; }); summary(); });
  form.querySelectorAll('[name="status"]').forEach(r => r.addEventListener('change', () => $('send-wrap').classList.toggle('d-none', form.querySelector('[name="status"]:checked')?.value !== 'confirmed')));
  $('wa-same').addEventListener('click', () => { $('b-wa').value = $('b-phone').value; });

  // Passenger 1 follows the contact name while it still matches (or is empty).
  ['first_name', 'last_name'].forEach(k => {
    const src = document.querySelector(`[name="${k}"]`); let prev = src.value;
    src.addEventListener('input', () => {
      const f = document.querySelector(`[name$="[0][${k}]"]`) || document.querySelector(`#pax [data-row]:first-child [name$="[${k}]"]`);
      if (f && (f.value === '' || f.value === prev)) f.value = src.value;
      prev = src.value; summary();
    });
  });
  ['b-email', 'b-phone'].forEach(id => $(id).addEventListener('input', summary));

  // Existing customer search (select2), filling the contact fields.
  const search = window.jQuery && jQuery('#customer-search');
  if (search && search.length && jQuery.fn.select2) {
    search.select2({ theme: 'bootstrap-5', placeholder: 'Start typing a name, email or phone…', minimumInputLength: 2, allowClear: true,
      language: { inputTooShort: () => 'Keep typing: 2 letters or digits are enough', searching: () => 'Searching…', noResults: () => 'No customer found. Fill in the contact below to create one.' },
      templateResult: c => c.loading || !c.first_name ? c.text : jQuery(`<div class="nb-cust-opt"><b>${esc(c.first_name)} ${esc(c.last_name || '')}</b><span>${esc([c.email, c.phone].filter(Boolean).join(' · '))}</span></div>`),
      ajax: { url: @json(route('bookings.customer-lookup')), delay: 200, data: p => ({ q: p.term }), processResults: d => d } });
    search.on('select2:select', e => {
      const c = e.params.data;
      ['first_name', 'last_name', 'email', 'phone', 'whatsapp'].forEach(k => { const f = document.querySelector(`[name="${k}"]`); if (f) { f.value = c[k] || ''; clearError(f); } });
      $('customer-id').value = c.id; $('customer-linked-name').textContent = c.first_name + ' ' + (c.last_name || '');
      $('customer-linked').classList.remove('d-none');
      const first = document.querySelector('#pax [data-row]:first-child [name$="[first_name]"]'); if (first && !first.value) { first.value = c.first_name || ''; first.closest('[data-row]').querySelector('[name$="[last_name]"]').value = c.last_name || ''; }
      summary();
    });
  }
  const unlink = $('customer-unlink');
  if (unlink) unlink.addEventListener('click', e => { e.preventDefault(); $('customer-id').value = ''; $('customer-linked').classList.add('d-none'); if (search) search.val(null).trigger('change'); summary(); });

  // Enter in a text field moves on instead of saving half a booking.
  form.addEventListener('keydown', e => {
    if (e.key !== 'Enter' || e.target.tagName !== 'INPUT' || e.target.type === 'submit') return;
    e.preventDefault();
    const fields = [...form.querySelectorAll('input:not([type=hidden]):not(.btn-check):not([tabindex="-1"]), select:not([tabindex="-1"]), textarea')].filter(f => f.offsetParent);
    fields[fields.indexOf(e.target) + 1]?.focus();
  });

  // Check everything before sending; errors appear next to their fields.
  let sending = false;
  form.addEventListener('submit', e => {
    if (sending) { e.preventDefault(); return; }
    let first = null;
    if (!$('b-dep').value) { $('dep-error').classList.remove('d-none'); first ??= $('step-trip'); }
    form.querySelectorAll('input, select, textarea').forEach(f => {
      if (f.id === 'b-dep' || f.type === 'hidden' || !f.willValidate) return;
      if (!f.checkValidity()) { setError(f, messageFor(f)); first ??= f; } else clearError(f);
    });
    if (first) {
      e.preventDefault();
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (first.focus) setTimeout(() => first.focus({ preventScroll: true }), 300);
      return;
    }
    sending = true;
    form.querySelectorAll('[type=submit]').forEach(b => { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating…'; });
  });

  rebuild(Object.values(old));

  // Server-side passenger errors: map the old row keys onto the rebuilt rows.
  const oldKeys = Object.keys(old || {});
  Object.entries(serverErrors).forEach(([key, msgs]) => {
    const m = key.match(/^passengers\.([^.]+)\.(\w+)$/); if (!m) return;
    const pos = oldKeys.indexOf(m[1]); const el = document.querySelectorAll('#pax [data-row]')[pos];
    const f = el?.querySelector(`[name$="[${m[2]}]"]`); if (f) setError(f, msgs[0]);
  });
});
</script>
</div></div></div>@include('admin.includes.footer')
