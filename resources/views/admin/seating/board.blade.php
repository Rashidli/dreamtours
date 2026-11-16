@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tour = $departure->tour;
  $canEdit = $state['canEdit'];
  $office = auth()->user()->can('list-bookings');
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Seating — {{ $tour->name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                @if($office)<li class="breadcrumb-item"><a href="{{ route('tours.edit', $tour) }}#departure-{{ $departure->id }}">{{ $tour->name }}</a></li>@else<li class="breadcrumb-item"><a href="{{ route('my-trips.show', $departure) }}">My trip</a></li>@endif
                                <li class="breadcrumb-item active">Seating</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <span class="fs-5 fw-semibold">{{ \App\Support\DepartureCalendar::range($departure) }}</span> <span class="text-muted">· departs {{ $departure->departs_at->format('g:i A') }}</span>
    <span class="d-block text-muted" id="seat-summary"></span>
  </div>
  <div class="d-flex flex-wrap gap-2 d-print-none">
    @if($office)<a class="btn btn-light" href="{{ route('tour-departures.passengers', $departure) }}"><i class="ri-group-line me-1"></i>Passenger list</a>@endif
    <a class="btn btn-light" href="{{ route('my-trips.show', $departure) }}"><i class="ri-map-2-line me-1"></i>Trip page</a>
    <div class="btn-group"><a class="btn btn-light" href="{{ route('seating.export', [$departure, 'pdf']) }}"><i class="ri-file-pdf-2-line me-1"></i>PDF</a><a class="btn btn-light" href="{{ route('seating.export', [$departure, 'xlsx']) }}">Excel</a></div>
    <button class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
  </div>
</div>

<div id="publish-bar" class="d-print-none"></div>

@if($canEdit)
<div class="card crm-filters d-print-none"><div class="card-body py-3">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <button type="button" class="btn btn-light" id="undo" disabled title="Undo your last seat change"><i class="ri-arrow-go-back-line me-1"></i>Undo</button>
    <div class="btn-group" role="group" aria-label="Mode">
      <input type="radio" class="btn-check" name="mode" id="mode-move" value="move" checked><label class="btn btn-outline-primary" for="mode-move"><i class="ri-drag-move-2-line me-1"></i>Seat passengers</label>
      <input type="radio" class="btn-check" name="mode" id="mode-block" value="block"><label class="btn btn-outline-secondary" for="mode-block"><i class="ri-forbid-line me-1"></i>Block seats</label>
    </div>
    <div class="vr d-none d-md-block"></div>
    <div class="input-group w-auto">
      <select class="form-select" id="fill-stop" aria-label="Who to seat"><option value="">Everyone without a seat</option></select>
      <select class="form-select" id="fill-bus" aria-label="Which bus"><option value="">Any bus</option></select>
      <button type="button" class="btn btn-outline-primary" id="autofill" title="Seats people in plan order, stop by stop and booking by booking, so families sit together"><i class="ri-magic-line me-1"></i>Auto-seat</button>
    </div>
  </div>
  <p class="text-muted mb-0 mt-2" id="hint">Drag a name onto a seat, or tap a name and then a seat. Dropping on a taken seat swaps the two. Drag a seated passenger back to the list to unseat them.</p>
</div></div>
@endif

<div class="row g-3">
  <div class="col-xl-8">
    <div id="buses"></div>
    @if($canEdit)
    <div class="card crm-filters d-print-none"><div class="card-body">
      <h5 class="card-title mb-3"><i class="ri-bus-2-line me-1"></i>Add a bus to this date</h5>
      @if($templates->isEmpty())
        <p class="text-muted mb-0">No seating templates yet. @can('create-vehicle-layouts')<a href="{{ route('vehicle-layouts.create') }}">Create one</a>.@endcan</p>
      @else
      <form method="post" action="{{ route('departure-vehicles.store', $departure) }}" class="row g-3 align-items-end">@csrf
        <div class="col-md-4"><label class="form-label" for="bus-template">Template</label><select class="form-select" id="bus-template" name="vehicle_layout_id" required>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="bus-label">Name</label><input class="form-control" id="bus-label" name="label" maxlength="50" placeholder="Bus {{ count($state['vehicles']) + 1 }}"></div>
        <div class="col-md-3"><label class="form-label" for="bus-note">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="bus-note" name="note" maxlength="255" placeholder="Operator, plate…"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-add-line me-1"></i>Add</button></div>
        <div class="col-12"><span class="text-muted">The date gets its own copy of the plan; @can('list-vehicle-layouts')<a href="{{ route('vehicle-layouts.index') }}">templates</a>@else templates @endcan can change later without moving anyone.</span></div>
      </form>
      @endif
    </div></div>
    @endif
  </div>

  <div class="col-xl-4 d-print-none">
    <div class="position-sticky" style="top:80px">
      <div class="card d-none" id="selected-card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start"><h5 class="card-title mb-1" id="sel-name"></h5><button type="button" class="btn-close" id="sel-close" aria-label="Clear selection"></button></div>
        <dl class="row mb-2" id="sel-details"></dl>
        <div class="d-flex flex-wrap gap-2" id="sel-actions"></div>
      </div></div>
      <div class="card" id="pool"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="card-title mb-0">Without a seat <span class="text-muted fw-normal" id="pool-count"></span></h5></div>
        <input class="form-control mb-2" id="pool-q" placeholder="Search name or booking" aria-label="Search passengers">
        <div class="d-flex flex-wrap gap-1 mb-2" id="stop-chips"></div>
        <div id="pool-list" style="max-height:55vh;overflow:auto"></div>
      </div></div>
    </div>
  </div>
</div>

<div class="seat-toast" id="toast" aria-live="polite"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let state = @json($state);
  const urls = { place: @json(route('seating.place', $departure)), block: @json(route('seating.block', $departure)), autofill: @json(route('seating.autofill', $departure)) };
  const vehicleUrl = id => @json(route('departure-vehicles.update', [$departure, '__V__'])).replace('__V__', id);
  const csrf = @json(csrf_token());
  const publishUrl = @json(route('seating.publish', $departure));
  const MARK = @json(collect(\App\Models\VehicleLayout::TYPES)->map(fn ($t) => $t[1]));
  const edit = state.canEdit;
  let selected = null, stopFilter = null, history = [], busy = false;
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const pax = id => state.passengers.find(p => p.id === id);
  const at = (v, s) => state.passengers.find(p => p.vehicle === v && p.seat === s);
  const mode = () => (document.querySelector('input[name=mode]:checked') || {}).value || 'move';

  function toast(msg, ok = true) {
    if (!msg) return;
    const el = document.createElement('div');
    el.className = 'alert alert-' + (ok ? 'success' : 'danger') + ' shadow';
    el.textContent = msg;
    $('toast').appendChild(el);
    setTimeout(() => el.remove(), ok ? 2500 : 6000);
  }

  async function api(url, body, undoable = null) {
    if (busy) return; busy = true;
    try {
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
      const data = await res.json().catch(() => ({}));
      if (data.state) state = data.state;
      if (res.ok) { if (undoable) history.push(undoable); toast(data.message); }
      else toast(data.message || 'That did not save. Reload the page and try again.', false);
    } catch (e) { toast('No connection — nothing was saved. Try again.', false); }
    busy = false; render();
  }

  // Move a passenger to a seat (or off the bus); a taken seat swaps the two. Undo restores both.
  function move(id, v, s) {
    const p = pax(id); if (!p) return;
    if ((p.vehicle ?? null) === v && (p.seat ?? null) === s) return;
    const placements = [{ p: id, v, s }], undo = [{ p: id, v: p.vehicle ?? null, s: p.seat ?? null }];
    const other = v !== null ? at(v, s) : null;
    if (other && other.id !== id) { placements.push({ p: other.id, v: p.vehicle ?? null, s: p.seat ?? null }); undo.push({ p: other.id, v, s }); }
    selected = null;
    api(urls.place, { placements }, { url: urls.place, body: { placements: undo } });
  }

  function seatClick(v, s, blocked) {
    if (!edit) { const o = at(v, s); selected = o ? o.id : null; return render(); }
    if (mode() === 'block') { if (at(v, s)) return toast('Move the passenger off this seat before blocking it.', false); return api(urls.block, { vehicle: v, seat: s, blocked: !blocked }, { url: urls.block, body: { vehicle: v, seat: s, blocked } }); }
    if (blocked) return toast('This seat is blocked. Switch to “Block seats” to free it.', false);
    const o = at(v, s);
    if (selected !== null && (!o || o.id !== selected)) return move(selected, v, s);
    selected = o && selected !== o.id ? o.id : null;
    render();
  }

  function render() {
    const seated = state.passengers.filter(p => p.seat), total = state.passengers.length;
    const open = state.vehicles.reduce((n, v) => n + v.seats - v.blocked.length, 0);
    $('seat-summary').textContent = state.vehicles.length
      ? `${seated.length} of ${total} passengers seated · ${state.vehicles.length} bus${state.vehicles.length > 1 ? 'es' : ''}, ${open} open seats` + (open < total ? ` — ${total - open} more seats needed` : '')
      : `${total} passengers · no bus added yet`;
    if ($('undo')) $('undo').disabled = !history.length;

    // Draft / published: guests are only emailed when someone publishes and ticks the box.
    const pub = state.publish, bar = $('publish-bar');
    const last = state.vehicles.map(v => v.published).filter(Boolean)[0];
    bar.innerHTML = !state.vehicles.length ? '' : `<div class="alert ${pub.draft ? 'alert-warning' : 'alert-success'} d-flex flex-wrap gap-2 align-items-center justify-content-between">
      <div><i class="${pub.draft ? 'ri-draft-line' : 'ri-checkbox-circle-line'} me-1"></i><b>${pub.draft ? 'Draft' : 'Published'}</b> — ${pub.draft ? (last ? 'changed since it was published ' + esc(last) + '. ' : 'not published yet. ') + 'Guests are not told about draft changes.' : 'published ' + esc(last) + '.'}
        ${pub.changed ? `<span class="d-block">${pub.changed} booking${pub.changed > 1 ? 's have' : ' has'} seats the guest has not been told about${pub.ready < pub.changed ? ` (${pub.ready} fully paid and ready to email)` : ''}.</span>` : ''}</div>
      ${edit ? `<form method="post" action="${publishUrl}" class="d-flex flex-wrap gap-2 align-items-center" onsubmit="return confirm(this.notify.checked ? 'Publish and email seat numbers to ${pub.ready} booking(s)?' : 'Publish without emailing anyone?')"><input type="hidden" name="_token" value="${csrf}">
        <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="notify" value="1" id="pub-notify" ${pub.ready ? '' : 'disabled'}><label class="form-check-label" for="pub-notify">Email seat numbers to ${pub.ready} fully paid booking${pub.ready === 1 ? '' : 's'}</label></div>
        <button class="btn ${pub.draft ? 'btn-warning' : 'btn-outline-success'}"><i class="ri-send-plane-line me-1"></i>Publish seating</button></form>` : ''}
    </div>`;

    // Buses
    const buses = $('buses'); buses.innerHTML = '';
    if (!state.vehicles.length) buses.innerHTML = '<div class="card"><div class="card-body crm-empty"><i class="ri-bus-2-line"></i>No bus on this date yet.' + (edit ? ' Add one below to start seating.' : '') + '</div></div>';
    state.vehicles.forEach(v => {
      const card = document.createElement('div'); card.className = 'card';
      const taken = state.passengers.filter(p => p.vehicle === v.id).length;
      card.innerHTML = `<div class="card-body"><div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
          <div><h5 class="mb-0">${esc(v.label)} <span class="text-muted fw-normal">· ${taken} / ${v.seats - v.blocked.length} seats</span></h5>${v.note ? `<span class="text-muted">${esc(v.note)}</span>` : ''}</div>
          ${edit ? `<div class="d-flex gap-1 d-print-none"><button type="button" class="btn btn-sm btn-light" data-edit-bus="${v.id}"><i class="ri-pencil-line"></i> Rename</button><form method="post" action="${vehicleUrl(v.id)}" onsubmit="return confirm('Remove ${esc(v.label).replace(/'/g, '')}? Only an empty bus can be removed.')"><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-outline-danger" title="Remove bus"><i class="ri-delete-bin-line"></i></button></form></div>` : ''}
        </div><div class="overflow-auto pb-1"><div class="seat-plan board ${edit ? 'can-edit' : ''}" style="grid-template-columns:repeat(${v.layout.cols},auto)"></div></div></div>`;
      const grid = card.querySelector('.seat-plan');
      v.layout.cells.forEach(row => row.forEach(c => {
        const d = document.createElement('div');
        if (c.t !== 'seat') { d.className = 'cell t-' + c.t; d.textContent = MARK[c.t] || ''; grid.appendChild(d); return; }
        const o = at(v.id, c.l), blocked = v.blocked.includes(c.l);
        d.className = 'cell t-seat ' + (o ? 'taken' : blocked ? 'blocked' : 'free') + (o && o.id === selected ? ' selected' : '') + (stopFilter !== null && (!o || (o.stop ?? 0) !== stopFilter) ? ' dim' : '');
        if (o) d.style.setProperty('--stop', o.colour);
        d.innerHTML = `<span class="no">${esc(c.l)}</span><span class="who">${o ? esc(o.short) : blocked ? 'Blocked' : ''}</span>`;
        d.title = o ? `Seat ${c.l}: ${o.name} · ${o.stopName || 'no stop'} · ${o.booking}` : blocked ? `Seat ${c.l} is blocked` : `Seat ${c.l} is free`;
        d.addEventListener('click', () => seatClick(v.id, c.l, blocked));
        if (edit && o) { d.draggable = true; d.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', o.id)); }
        if (edit && !blocked) {
          d.addEventListener('dragover', e => { e.preventDefault(); d.classList.add('drop'); });
          d.addEventListener('dragleave', () => d.classList.remove('drop'));
          d.addEventListener('drop', e => { e.preventDefault(); d.classList.remove('drop'); const id = +e.dataTransfer.getData('text/plain'); if (id) move(id, v.id, c.l); });
        }
        grid.appendChild(d);
      }));
      buses.appendChild(card);
    });
    buses.querySelectorAll('[data-edit-bus]').forEach(b => b.addEventListener('click', () => {
      const v = state.vehicles.find(x => x.id === +b.dataset.editBus);
      const label = prompt('Bus name', v.label); if (label === null || !label.trim()) return;
      const note = prompt('Note (operator, plate…) — leave empty for none', v.note || ''); if (note === null) return;
      const f = document.createElement('form'); f.method = 'post'; f.action = vehicleUrl(v.id);
      f.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="PUT"><input type="hidden" name="label"><input type="hidden" name="note">`;
      f.label.value = label.trim(); f.note.value = note.trim(); document.body.appendChild(f); f.submit();
    }));

    // Stop chips and the unseated pool
    const chips = $('stop-chips');
    const stops = [...state.stops, ...(state.passengers.some(p => !p.stop) ? [{ id: 0, name: 'No stop', colour: '#adb5bd' }] : [])];
    chips.innerHTML = stops.map(s => `<button type="button" class="btn btn-sm ${stopFilter === s.id ? 'btn-dark' : 'btn-light'}" data-stop="${s.id}" style="--stop:${s.colour}"><span class="stop-dot me-1"></span>${esc(s.name)} <span class="text-muted">${state.passengers.filter(p => (p.stop ?? 0) === s.id).length}</span></button>`).join('');
    chips.querySelectorAll('[data-stop]').forEach(b => b.addEventListener('click', () => { const id = +b.dataset.stop; stopFilter = stopFilter === id ? null : id; render(); }));
    const q = $('pool-q').value.trim().toLowerCase();
    const pool = state.passengers.filter(p => !p.seat && (stopFilter === null || (p.stop ?? 0) === stopFilter) && (!q || (p.name + ' ' + p.booking).toLowerCase().includes(q)));
    $('pool-count').textContent = `(${state.passengers.filter(p => !p.seat).length})`;
    $('pool-list').innerHTML = pool.length ? pool.map(p => `<div class="pax-chip ${p.id === selected ? 'selected' : ''}" data-pax="${p.id}" ${edit ? 'draggable="true"' : ''} style="--stop:${p.colour}">
        <div class="flex-grow-1"><div class="fw-semibold">${esc(p.name)}${p.category ? ` <span class="badge rounded-pill bg-light text-body">${esc(p.category)}</span>` : ''}</div><div class="meta">${esc(p.stopName || 'No stop')} · ${esc(p.booking)}</div></div></div>`).join('')
      : `<div class="crm-empty py-3"><i class="ri-check-double-line"></i>${state.passengers.some(p => !p.seat) ? 'Nobody matches.' : 'Everyone has a seat.'}</div>`;
    $('pool-list').querySelectorAll('[data-pax]').forEach(el => {
      el.addEventListener('click', () => { const id = +el.dataset.pax; selected = selected === id ? null : id; render(); });
      el.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', el.dataset.pax));
    });

    // Selected passenger: details and actions
    const s = selected !== null ? pax(selected) : null;
    $('selected-card').classList.toggle('d-none', !s);
    if (s) {
      const bus = state.vehicles.find(v => v.id === s.vehicle);
      $('sel-name').textContent = s.name;
      const rows = [['Seat', s.seat ? `${bus ? esc(bus.label) + ' · ' : ''}${esc(s.seat)}` : '<span class="text-muted">None yet</span>'], ['Pickup', `<span class="stop-dot me-1" style="--stop:${s.colour}"></span>${esc(s.stopName || '—')}`], ['Booking', s.bookingUrl ? `<a href="${s.bookingUrl}" target="_blank">${esc(s.booking)}</a>` : esc(s.booking)], ['Phone', s.phone ? `<a href="tel:${esc(s.phone.replace(/[^\d+]/g, ''))}">${esc(s.phone)}</a>` : '—'], ['Meal', esc(s.meal || '—')]];
      if (s.payment) rows.push(['Payment', esc(s.payment)]);
      $('sel-details').innerHTML = rows.map(([k, v]) => `<dt class="col-4 text-muted fw-normal">${k}</dt><dd class="col-8 mb-1">${v}</dd>`).join('');
      $('sel-actions').innerHTML = edit ? (s.seat ? '<button type="button" class="btn btn-sm btn-outline-danger" id="unseat"><i class="ri-user-unfollow-line me-1"></i>Unseat</button>' : '') + '<span class="text-muted align-self-center">' + (s.seat ? 'or tap another seat to move them' : 'Tap a free seat to seat them') + '</span>' : '';
      if ($('unseat')) $('unseat').addEventListener('click', () => move(s.id, null, null));
    }
  }

  $('sel-close').addEventListener('click', () => { selected = null; render(); });
  $('pool-q').addEventListener('input', render);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && selected !== null) { selected = null; render(); } });
  if (edit) {
    const pool = $('pool');
    pool.addEventListener('dragover', e => e.preventDefault());
    pool.addEventListener('drop', e => { e.preventDefault(); const id = +e.dataTransfer.getData('text/plain'); if (id && pax(id)?.seat) move(id, null, null); });
    $('undo').addEventListener('click', () => { const last = history.pop(); if (last) api(last.url, last.body); });
    document.querySelectorAll('input[name=mode]').forEach(r => r.addEventListener('change', () => { selected = null; $('hint').textContent = mode() === 'block' ? 'Tap a free seat to block it for this date (for example a broken seat or a guide’s bag); tap a blocked seat to free it.' : 'Drag a name onto a seat, or tap a name and then a seat. Dropping on a taken seat swaps the two. Drag a seated passenger back to the list to unseat them.'; render(); }));
    const fillStop = $('fill-stop'), fillBus = $('fill-bus');
    state.stops.forEach(s => fillStop.insertAdjacentHTML('beforeend', `<option value="${s.id}">Only ${esc(s.name)}</option>`));
    if (state.passengers.some(p => !p.stop)) fillStop.insertAdjacentHTML('beforeend', '<option value="0">Only passengers without a stop</option>');
    state.vehicles.forEach(v => fillBus.insertAdjacentHTML('beforeend', `<option value="${v.id}">${esc(v.label)}</option>`));
    $('autofill').addEventListener('click', () => {
      const before = state.passengers.filter(p => !p.seat).map(p => ({ p: p.id, v: null, s: null }));
      api(urls.autofill, { stop: fillStop.value === '' ? null : +fillStop.value, vehicle: fillBus.value ? +fillBus.value : null }, before.length ? { url: urls.place, body: { placements: before } } : null);
    });
  }
  render();
});
</script>
</div></div></div>@include('admin.includes.footer')
