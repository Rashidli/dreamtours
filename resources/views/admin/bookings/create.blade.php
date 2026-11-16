@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">New booking</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('bookings.index') }}">Bookings</a></li>
                                <li class="breadcrumb-item active">New</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.validation_errors')

<form method="post" action="{{ route('bookings.store') }}" id="new-booking">@csrf
<input type="hidden" name="customer_id" id="customer-id" value="{{ old('customer_id', $customer?->id) }}">
@if($waitlist)
  <input type="hidden" name="waitlist_entry_id" value="{{ $waitlist->id }}">
  <div class="alert alert-info"><i class="ri-list-ordered me-1"></i>Booking <b>{{ $waitlist->full_name }}</b> from the waitlist ({{ $waitlist->guests }} {{ str('seat')->plural($waitlist->guests) }}). Their waitlist entry closes when this booking is saved.</div>
@endif
<div class="row g-3">
  <div class="col-xl-8">
    <div class="card crm-filters"><div class="card-body">
      <h5 class="card-title mb-3"><span class="badge rounded-pill bg-primary me-1">1</span> Customer</h5>
      @can('list-customers')
      <div class="mb-3"><label class="form-label" for="customer-search">Find an existing customer</label><select id="customer-search" class="form-select" style="width:100%"></select><div class="form-text">Search by name, email or phone. Or just fill in the contact below — an existing customer with the same email (or phone) is linked automatically, and a new one is created otherwise.</div></div>
      <div class="alert alert-info py-2 {{ old('customer_id', $customer?->id) ? '' : 'd-none' }}" id="customer-linked"><i class="ri-links-line me-1"></i>Linked to customer <b id="customer-linked-name">{{ $customer?->full_name }}</b>. <a href="#" id="customer-unlink">Unlink</a></div>
      @endcan
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="b-first">First name</label><input class="form-control" id="b-first" name="first_name" value="{{ old('first_name', $customer?->first_name ?? $waitlist?->first_name) }}" required maxlength="100"></div>
        <div class="col-md-6"><label class="form-label" for="b-last">Last name</label><input class="form-control" id="b-last" name="last_name" value="{{ old('last_name', $customer?->last_name ?? $waitlist?->last_name) }}" required maxlength="100"></div>
        <div class="col-md-4"><label class="form-label" for="b-email">Email</label><input class="form-control" type="email" id="b-email" name="email" value="{{ old('email', $customer?->email ?? $waitlist?->email) }}" required></div>
        <div class="col-md-4"><label class="form-label" for="b-phone">Phone</label><input class="form-control" id="b-phone" name="phone" value="{{ old('phone', $customer?->phone ?? $waitlist?->phone) }}" required maxlength="30"></div>
        <div class="col-md-4"><label class="form-label" for="b-wa">WhatsApp <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="b-wa" name="whatsapp" value="{{ old('whatsapp', $customer?->whatsapp) }}" maxlength="30"></div>
      </div>
    </div></div>

    <div class="card crm-filters"><div class="card-body">
      <h5 class="card-title mb-3"><span class="badge rounded-pill bg-primary me-1">2</span> Trip</h5>
      <label class="form-label" for="b-dep">Departure</label>
      <select class="form-select" id="b-dep" name="tour_departure_id" required>
        <option value="">Choose a date…</option>
        @foreach(collect($departures)->groupBy('tour') as $tourName => $dates)
          <optgroup label="{{ $tourName }}">@foreach($dates as $o)<option value="{{ $o['id'] }}" @selected(old('tour_departure_id', $waitlist?->tour_departure_id ?? request('departure')) == $o['id'])>{{ $o['label'] }} · {{ $o['left'] }} {{ str('seat')->plural($o['left']) }} left{{ $o['left'] ? '' : ' (full)' }}</option>@endforeach</optgroup>
        @endforeach
      </select>
      <div class="form-text" id="dep-info"></div>
    </div></div>

    <div class="card crm-filters"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title mb-0"><span class="badge rounded-pill bg-primary me-1">3</span> Passengers</h5><button type="button" class="btn btn-sm btn-outline-primary" id="add-pax"><i class="ri-user-add-line me-1"></i>Add passenger</button></div>
      <div id="pax"></div>
      <p class="text-muted mb-0" id="pax-hint">Choose a departure first; its fares, pickup points and meals appear here.</p>
    </div></div>
  </div>

  <div class="col-xl-4">
    <div class="position-sticky" style="top:80px">
      <div class="card crm-filters"><div class="card-body">
        <h5 class="card-title mb-3">Summary</h5>
        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Passengers</span><b id="sum-pax">0</b></div>
        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Total</span><b class="fs-5" id="sum-total">—</b></div>
        <p class="text-muted">Prices come from the date's fares. Discounts and payments are added on the booking page afterwards.</p>
        <label class="form-label" for="b-status">Status</label>
        <select class="form-select mb-2" id="b-status" name="status">@foreach(\App\Http\Controllers\Admin\ManualBookingController::STATUSES as $s)<option value="{{ $s }}" @selected(old('status', 'payment_pending') === $s)>{{ \App\Models\Booking::STATUSES[$s] }}</option>@endforeach</select>
        <div class="form-check mb-3 {{ old('status', 'payment_pending') === 'confirmed' ? '' : 'd-none' }}" id="send-wrap"><input class="form-check-input" type="checkbox" name="send_confirmation" value="1" id="b-send" @checked(old('send_confirmation'))><label class="form-check-label" for="b-send">Email the booking confirmation to the customer</label></div>
        <label class="form-label" for="b-comments">Customer's request <span class="text-muted fw-normal">(optional)</span></label>
        <textarea class="form-control mb-2" id="b-comments" name="comments" rows="2" maxlength="2000">{{ old('comments') }}</textarea>
        <label class="form-label" for="b-notes">Internal notes <span class="text-muted fw-normal">(staff only)</span></label>
        <textarea class="form-control" id="b-notes" name="internal_notes" rows="2" maxlength="5000">{{ old('internal_notes') }}</textarea>
        @include('admin.includes.capacity_override', ['id' => 'cap-new'])
        <button class="btn btn-primary w-100 mt-3"><i class="ri-save-line me-1"></i>Create booking</button>
        <a class="btn btn-light w-100 mt-2" href="{{ route('bookings.index') }}">Cancel</a>
      </div></div>
    </div>
  </div>
</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const deps = @json($departures);
  const old = @json(old('passengers', $prefill));
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = (n, cur) => new Intl.NumberFormat('en-CA', { style: 'currency', currency: cur || 'CAD' }).format(n);
  let n = 0;

  function current() { return deps[$('b-dep').value] || null; }

  function row(p = {}) {
    const d = current(); if (!d) return;
    const i = n++;
    const el = document.createElement('div');
    el.className = 'border rounded p-3 mb-2'; el.dataset.row = i;
    el.innerHTML = `<div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">First name</label><input class="form-control" name="passengers[${i}][first_name]" value="${esc(p.first_name)}" required maxlength="100"></div>
      <div class="col-md-3"><label class="form-label">Last name</label><input class="form-control" name="passengers[${i}][last_name]" value="${esc(p.last_name)}" required maxlength="100"></div>
      <div class="col-md-3"><label class="form-label">Fare</label><select class="form-select" name="passengers[${i}][fare]" data-fare>${d.fares.map(f => `<option value="${esc(f.code)}" data-price="${f.price}" ${p.fare === f.code ? 'selected' : ''}>${esc(f.label)} · ${fmt(f.price, d.currency)}</option>`).join('')}</select></div>
      <div class="col-md-3"><label class="form-label">Pickup</label><select class="form-select" name="passengers[${i}][pickup_point_id]"><option value="">—</option>${d.pickups.map(s => `<option value="${s.id}" ${String(p.pickup_point_id) === String(s.id) ? 'selected' : ''}>${esc(s.name)}</option>`).join('')}</select></div>
      ${d.meals.length ? `<div class="col-md-3"><label class="form-label">Meal</label><select class="form-select" name="passengers[${i}][meal_selection]"><option value="">—</option>${d.meals.map(m => `<option ${p.meal_selection === m ? 'selected' : ''}>${esc(m)}</option>`).join('')}</select></div>` : ''}
      <div class="col-md-3"><label class="form-label">Phone <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" name="passengers[${i}][phone]" value="${esc(p.phone)}" maxlength="30"></div>
      <div class="col text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove title="Remove passenger"><i class="ri-delete-bin-line"></i></button></div>
    </div>`;
    el.querySelector('[data-remove]').addEventListener('click', () => { if (document.querySelectorAll('[data-row]').length > 1) { el.remove(); summary(); } });
    el.querySelector('[data-fare]').addEventListener('change', summary);
    $('pax').appendChild(el);
  }

  function summary() {
    const d = current(), rows = [...document.querySelectorAll('[data-fare]')];
    $('sum-pax').textContent = rows.length;
    $('sum-total').textContent = d && rows.length ? fmt(rows.reduce((t, s) => t + parseFloat(s.selectedOptions[0]?.dataset.price || 0), 0), d.currency) : '—';
    $('dep-info').textContent = d ? `${d.status} · ${d.left} seat${d.left === 1 ? '' : 's'} left` + (rows.length > d.left ? ` — this booking needs ${rows.length}.` : '') : '';
    $('dep-info').className = 'form-text ' + (d && rows.length > d.left ? 'text-danger' : '');
  }

  function rebuild(keep) {
    // Keep typed names when the date changes; fares and pickups follow the new date.
    const typed = keep ?? [...document.querySelectorAll('[data-row]')].map(el => ({ first_name: el.querySelector('[name$="[first_name]"]').value, last_name: el.querySelector('[name$="[last_name]"]').value, phone: el.querySelector('[name$="[phone]"]').value }));
    $('pax').innerHTML = ''; n = 0;
    const d = current();
    $('pax-hint').classList.toggle('d-none', !!d); $('add-pax').disabled = !d;
    if (d) (typed.length ? typed : [{ first_name: $('b-first').value, last_name: $('b-last').value }]).forEach(row);
    summary();
  }

  $('b-dep').addEventListener('change', () => rebuild());
  $('add-pax').addEventListener('click', () => { row({ last_name: $('b-last').value }); summary(); });
  $('b-status').addEventListener('change', e => $('send-wrap').classList.toggle('d-none', e.target.value !== 'confirmed'));

  // Existing customer search (select2), filling the contact fields.
  const search = window.jQuery && jQuery('#customer-search');
  if (search && search.length && jQuery.fn.select2) {
    search.select2({ theme: 'bootstrap-5', placeholder: 'Start typing a name, email or phone…', minimumInputLength: 2, allowClear: true,
      ajax: { url: @json(route('bookings.customer-lookup')), delay: 250, data: p => ({ q: p.term }), processResults: d => d } });
    search.on('select2:select', e => {
      const c = e.params.data;
      ['first_name', 'last_name', 'email', 'phone', 'whatsapp'].forEach(k => { const f = document.querySelector(`[name="${k}"]`); if (f) f.value = c[k] || ''; });
      $('customer-id').value = c.id; $('customer-linked-name').textContent = c.first_name + ' ' + (c.last_name || '');
      $('customer-linked').classList.remove('d-none');
      const first = document.querySelector('[name$="[0][first_name]"]'); if (first && !first.value) { first.value = c.first_name || ''; document.querySelector('[name$="[0][last_name]"]').value = c.last_name || ''; }
    });
  }
  const unlink = $('customer-unlink');
  if (unlink) unlink.addEventListener('click', e => { e.preventDefault(); $('customer-id').value = ''; $('customer-linked').classList.add('d-none'); if (search) search.val(null).trigger('change'); });

  rebuild(Object.values(old));
});
</script>
</div></div></div>@include('admin.includes.footer')
