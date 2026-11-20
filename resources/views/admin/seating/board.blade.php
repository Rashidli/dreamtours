@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-vehicles.css') }}?v={{ @filemtime(public_path('assets/css/admin-vehicles.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $tour = $departure->tour;
  $canEdit = $state['canEdit'];
  $office = auth()->user()->can('list-bookings');
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-end justify-content-between gap-2">
                        <div class="min-w-0">
                          @if($office)<a class="vb-back" href="{{ route('tours.edit', $tour) }}#departure-{{ $departure->id }}"><i class="ri-arrow-left-line"></i><span>{{ $tour->name }}</span></a>@else<a class="vb-back" href="{{ route('my-trips.show', $departure) }}"><i class="ri-arrow-left-line"></i><span>My trip · {{ $tour->name }}</span></a>@endif
                          <div class="vb-title"><h4>Seating · {{ \App\Support\DepartureCalendar::range($departure) }}</h4><span class="text-muted">departs {{ $departure->departs_at->format('g:i A') }}</span></div>
                        </div>
                        <div class="vb-head-actions d-print-none">
                          @if($office)<a class="btn btn-light" href="{{ route('tour-departures.passengers', $departure) }}"><i class="ri-group-line me-1"></i>Passenger list</a>@endif
                          <a class="btn btn-light" href="{{ route('my-trips.show', $departure) }}"><i class="ri-map-2-line me-1"></i>Trip page</a>
                          <div class="btn-group" role="group" aria-label="Export"><a class="btn btn-light" href="{{ route('seating.export', [$departure, 'pdf']) }}" title="Download the seating plan as PDF"><i class="ri-file-pdf-line me-1"></i>PDF</a><a class="btn btn-light" href="{{ route('seating.export', [$departure, 'xlsx']) }}" title="Download the seat list as Excel"><i class="ri-file-excel-2-line me-1"></i>Excel</a></div>
                          <button type="button" class="btn btn-light" onclick="window.print()" title="Print"><i class="ri-printer-line"></i><span class="d-none d-lg-inline ms-1">Print</span></button>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')

{{-- Progress (seated x of y) and the draft / published state with the Publish action; both drawn by the script. --}}
<div class="card vb-status d-print-none">
  <div id="seat-summary"></div>
  <div id="publish-bar"></div>
</div>

@if($canEdit)
<div class="card d-print-none" id="vb-tools"><div class="card-body py-3">
  <div class="vb-toolbar">
    <div class="btn-group" role="group" aria-label="Mode">
      <input type="radio" class="btn-check" name="mode" id="mode-move" value="move" checked><label class="btn btn-outline-primary" for="mode-move"><i class="ri-drag-move-2-line me-1"></i>Seat passengers</label>
      <input type="radio" class="btn-check" name="mode" id="mode-block" value="block"><label class="btn btn-outline-primary" for="mode-block"><i class="ri-forbid-line me-1"></i>Block seats</label>
    </div>
    <button type="button" class="btn btn-light" id="undo" disabled title="Undo your last seat change"><i class="ri-arrow-go-back-line me-1"></i>Undo</button>
    <div class="vr d-none d-md-block"></div>
    <div class="vb-autofill">
      <select class="form-select" id="fill-stop" aria-label="Who to seat"><option value="">Everyone without a seat</option></select>
      <select class="form-select" id="fill-bus" aria-label="Which bus"><option value="">Any bus</option></select>
      <button type="button" class="btn btn-light" id="autofill" title="Seats people in plan order, stop by stop and booking by booking, so families sit together"><i class="ri-magic-line me-1"></i>Auto-seat</button>
    </div>
  </div>
  <p class="vb-hint"><i class="ri-information-line"></i><span id="hint">Drag a name onto a seat, or tap a name and then a seat. Dropping on a taken seat swaps the two. Drag a seated passenger back to the list to unseat them.</span></p>
</div></div>
@endif

<div class="row g-3">
  <div class="col-xl-8">
    <div class="vb-legend d-none" id="vb-legend" aria-label="Legend">
      <span><i class="vb-sw"></i>Free</span>
      <span><i class="vb-sw taken"></i>Taken (colour = pickup stop)</span>
      <span><i class="vb-sw selected"></i>Selected</span>
      <span><i class="vb-sw blocked"></i>Blocked</span>
      <span><i class="vb-sw leader"></i>Leader</span>
      <span><i class="vb-sw driver"></i>Driver</span>
      <span><i class="vb-sw door"></i>Door</span>
    </div>
    <div id="buses"></div>
    @if($canEdit)
    <div class="card vb-add-bus d-print-none" id="add-bus"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-bus-line"></i></div><div><h5>Add a bus to this date</h5><p>The date gets its own copy of the plan; @can('list-vehicle-layouts')<a href="{{ route('vehicle-layouts.index') }}">templates</a>@else templates @endcan can change later without moving anyone.</p></div></div>
      @if($templates->isEmpty())
        <p class="text-muted mb-0">No seating templates yet. @can('create-vehicle-layouts')<a href="{{ route('vehicle-layouts.create') }}">Create one</a>.@endcan</p>
      @else
      <form method="post" action="{{ route('departure-vehicles.store', $departure) }}" class="row g-3 align-items-end">@csrf
        <div class="col-md-4"><label class="form-label" for="bus-template">Template</label><select class="form-select" id="bus-template" name="vehicle_layout_id" required>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }} · {{ $t->seat_count }} seats</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="bus-label">Name</label><input class="form-control" id="bus-label" name="label" maxlength="50" placeholder="Bus {{ count($state['vehicles']) + 1 }}"></div>
        <div class="col-md-3"><label class="form-label" for="bus-note">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="bus-note" name="note" maxlength="255" placeholder="Operator, plate…"></div>
        <div class="col-md-2"><button class="btn {{ count($state['vehicles']) ? 'btn-light' : 'btn-primary' }} w-100"><i class="ri-add-line me-1"></i>Add bus</button></div>
      </form>
      @endif
    </div></div>
    @endif
  </div>

  <div class="col-xl-4 d-print-none order-first order-xl-last">
    <div class="vb-side">
      <div class="card vb-selected d-none" id="selected-card"><div class="card-body">
        <div class="vb-sel-head"><span class="ini" id="sel-ini"></span><div class="flex-grow-1 min-w-0"><h5 class="card-title mb-0" id="sel-name"></h5><span class="vb-sub" id="sel-seat"></span></div><button type="button" class="btn-close" id="sel-close" aria-label="Clear selection"></button></div>
        <dl class="row" id="sel-details"></dl>
        <div class="d-flex flex-wrap gap-2 align-items-center" id="sel-actions"></div>
      </div></div>
      <div class="card vb-pool" id="pool"><div class="card-body">
        <div class="vb-pool-head">
          <h5>Without a seat <span class="badge bg-secondary" id="pool-count"></span></h5>
          <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" id="pool-q" placeholder="Find a passenger or booking" aria-label="Search passengers" autocomplete="off"></label>
        </div>
        <div class="vb-stops" id="stop-chips"></div>
        <div class="vb-pool-list" id="pool-list"></div>
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
  const NAMES = @json(collect(\App\Models\VehicleLayout::TYPES)->map(fn ($t) => $t[0]));
  const edit = state.canEdit;
  let selected = null, stopFilter = null, history = [], busy = false, revealSeat = false;
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const pax = id => state.passengers.find(p => p.id === id);
  const at = (v, s) => state.passengers.find(p => p.vehicle === v && p.seat === s);
  const mode = () => (document.querySelector('input[name=mode]:checked') || {}).value || 'move';
  const initials = name => { const w = String(name || '').trim().split(/\s+/).filter(Boolean); return ((w[0] || '?')[0] + (w.length > 1 ? w[w.length - 1][0] : '')).toUpperCase(); };
  const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;
  const busName = id => (state.vehicles.find(v => v.id === id) || {}).label || '';

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
    document.body.classList.add('vb-busy');
    try {
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
      const data = await res.json().catch(() => ({}));
      if (data.state) state = data.state;
      if (res.ok) { if (undoable) history.push(undoable); toast(data.message); }
      else toast(data.message || 'That did not save. Reload the page and try again.', false);
    } catch (e) { toast('No connection — nothing was saved. Try again.', false); }
    busy = false; document.body.classList.remove('vb-busy'); render();
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

  function renderSummary() {
    const seated = state.passengers.filter(p => p.seat).length, total = state.passengers.length;
    const open = state.vehicles.reduce((n, v) => n + v.seats - v.blocked.length, 0);
    const pct = total ? Math.round(seated / total * 100) : 0, left = total - seated;
    let html;
    if (!state.vehicles.length) {
      html = `<div class="vb-progress-head"><span class="vb-progress-big">${total} <span>${total === 1 ? 'passenger' : 'passengers'}</span></span><span class="badge bg-danger"><i class="ri-error-warning-line me-1"></i>No bus yet</span></div>
        <div class="vb-progress-note"><span>${edit ? 'Add a bus below to start seating.' : 'The office has not added a bus to this date yet.'}</span></div>`;
    } else {
      html = `<div class="vb-progress-head"><span class="vb-progress-big">${seated} <span>of ${plural(total, 'passenger', 'passengers')} seated</span></span>
          ${total ? (left ? `<span class="badge bg-warning">${left} to seat</span>` : '<span class="badge bg-success"><i class="ri-check-line me-1"></i>Everyone seated</span>') : ''}</div>
        <div class="progress vb-progress" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" aria-label="Seated"><div class="progress-bar ${left ? 'bg-warning' : 'bg-success'}" style="width:${pct}%"></div></div>
        <div class="vb-progress-note"><span><i class="ri-bus-line me-1"></i>${plural(state.vehicles.length, 'bus', 'buses')}</span><span>${plural(open, 'open seat', 'open seats')}</span>${open < total ? `<span class="text-danger"><i class="ri-error-warning-line me-1"></i>${total - open} more ${total - open === 1 ? 'seat' : 'seats'} needed</span>` : ''}</div>`;
    }
    $('seat-summary').innerHTML = html;
  }

  // Draft / published: guests are only emailed when someone publishes and ticks the box.
  function renderPublish() {
    const pub = state.publish, bar = $('publish-bar');
    const last = state.vehicles.map(v => v.published).filter(Boolean)[0];
    bar.classList.toggle('d-none', !state.vehicles.length);
    if (!state.vehicles.length) { bar.innerHTML = ''; return; }
    const title = pub.draft ? (last ? 'Draft — changed since publishing' : 'Draft — not published yet') : 'Published';
    const note = pub.draft ? (last ? 'Last published ' + esc(last) + '. ' : '') + 'Guests are not told about draft changes.' : 'Published ' + esc(last) + '. Guests see these seats.';
    bar.innerHTML = `<div class="vb-pub">
      <div class="vb-pub-state"><span class="vb-pub-icon ${pub.draft ? 'is-draft' : 'is-live'}"><i class="${pub.draft ? 'ri-file-edit-line' : 'ri-checkbox-circle-line'}"></i></span><div><b>${title}</b><span>${note}</span>
        ${pub.changed ? `<span>${pub.changed} booking${pub.changed > 1 ? 's have' : ' has'} seats the guest has not been told about${pub.ready < pub.changed ? ` (${pub.ready} fully paid and ready to email)` : ''}.</span>` : ''}</div></div>
      ${edit ? `<form method="post" action="${publishUrl}" onsubmit="return confirm(this.notify.checked ? 'Publish and email seat numbers to ${pub.ready} booking(s)?' : 'Publish without emailing anyone?')"><input type="hidden" name="_token" value="${csrf}">
        <button class="btn ${pub.draft ? 'btn-primary' : 'btn-light'}"><i class="ri-send-plane-line me-1"></i>${pub.draft ? 'Publish seating' : 'Publish again'}</button>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="notify" value="1" id="pub-notify" ${pub.ready ? '' : 'disabled'}><label class="form-check-label" for="pub-notify">Email seat numbers to ${pub.ready} fully paid booking${pub.ready === 1 ? '' : 's'}</label></div></form>` : ''}
    </div>`;
  }

  function renderBuses() {
    const buses = $('buses'); buses.innerHTML = '';
    $('vb-legend').classList.toggle('d-none', !state.vehicles.length);
    if (!state.vehicles.length) buses.innerHTML = '<div class="card"><div class="card-body crm-empty"><i class="ri-bus-line"></i><b class="d-block text-body mb-1">No bus on this date yet</b>' + (edit ? 'Pick a seating template below and add it — then drag passengers onto seats or press Auto-seat.' : 'The seating plan appears here once the office adds a bus.') + '</div></div>';
    const picking = edit && mode() === 'move' && selected !== null;
    const selSeated = picking && !!(pax(selected) || {}).seat;
    state.vehicles.forEach(v => {
      const card = document.createElement('div'); card.className = 'card vb-bus-card';
      const taken = state.passengers.filter(p => p.vehicle === v.id).length, open = v.seats - v.blocked.length;
      const pct = open ? Math.min(100, Math.round(taken / open * 100)) : 0;
      card.innerHTML = `<div class="card-body"><div class="vb-bus-head">
          <div class="min-w-0"><h5><i class="ri-bus-line"></i>${esc(v.label)}</h5>${v.note ? `<span class="vb-sub">${esc(v.note)}</span>` : ''}</div>
          <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="vb-bus-meter" title="${taken} of ${open} open seats taken${v.blocked.length ? ' · ' + v.blocked.length + ' blocked' : ''}"><div class="progress"><div class="progress-bar ${taken >= open ? 'bg-success' : 'bg-primary'}" style="width:${pct}%"></div></div><b>${taken} / ${open}</b></div>
            ${edit ? `<div class="d-flex gap-1 d-print-none"><button type="button" class="btn btn-light" data-edit-bus="${v.id}"><i class="ri-pencil-line me-1"></i>Rename</button><form method="post" action="${vehicleUrl(v.id)}" onsubmit="return confirm('Remove ${esc(v.label).replace(/'/g, '')}? Only an empty bus can be removed.')"><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-light text-danger" title="Remove bus" aria-label="Remove bus"><i class="ri-delete-bin-line"></i></button></form></div>` : ''}
          </div>
        </div><div class="vb-canvas"><div class="vb-bus"><span class="vb-front"><i class="ri-arrow-up-line"></i>Front</span><div class="seat-plan board ${edit ? 'can-edit' : ''} ${picking ? 'picking' : ''} ${edit && mode() === 'block' ? 'blocking' : ''}" style="grid-template-columns:repeat(${v.layout.cols},auto)"></div></div></div></div>`;
      const grid = card.querySelector('.seat-plan');
      v.layout.cells.forEach(row => row.forEach(c => {
        const d = document.createElement('div');
        if (c.t !== 'seat') { d.className = 'cell t-' + c.t; d.textContent = MARK[c.t] || ''; if (NAMES[c.t] && !['aisle', 'empty'].includes(c.t)) d.title = NAMES[c.t]; grid.appendChild(d); return; }
        const o = at(v.id, c.l), blocked = v.blocked.includes(c.l);
        d.className = 'cell t-seat ' + (o ? 'taken' : blocked ? 'blocked' : 'free') + (o && o.id === selected ? ' selected' : '') + (stopFilter !== null && (!o || (o.stop ?? 0) !== stopFilter) ? ' dim' : '');
        if (o) d.style.setProperty('--stop', o.colour);
        const mark = o ? `<span class="ini">${esc(initials(o.name))}</span>` : blocked ? '<i class="ri-lock-line"></i>' : '';
        const who = o ? esc(o.short) : blocked ? 'Blocked' : (selSeated ? 'Move here' : 'Seat here');
        d.innerHTML = `<span class="top"><span class="no">${esc(c.l)}</span>${mark}</span><span class="who">${who}</span>`;
        d.title = o ? `Seat ${c.l}: ${o.name} · ${o.stopName || 'no stop'} · ${o.booking}` : blocked ? `Seat ${c.l} is blocked` : `Seat ${c.l} is free`;
        d.setAttribute('role', 'button'); d.tabIndex = 0;
        d.addEventListener('click', () => seatClick(v.id, c.l, blocked));
        d.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); seatClick(v.id, c.l, blocked); } });
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
  }

  // Stop chips and the unseated pool, grouped by booking so families stay together.
  function renderPool() {
    const chips = $('stop-chips');
    const stops = [...state.stops, ...(state.passengers.some(p => !p.stop) ? [{ id: 0, name: 'No stop', colour: '#adb5bd' }] : [])];
    chips.innerHTML = stops.length > 1 || stopFilter !== null ? stops.map(s => `<button type="button" class="vb-stop-chip ${stopFilter === s.id ? 'active' : ''}" data-stop="${s.id}" style="--stop:${s.colour}" aria-pressed="${stopFilter === s.id}" title="Highlight passengers picked up at ${esc(s.name)}"><span class="stop-dot"></span>${esc(s.name)} <span class="n">${state.passengers.filter(p => (p.stop ?? 0) === s.id).length}</span></button>`).join('') : '';
    chips.querySelectorAll('[data-stop]').forEach(b => b.addEventListener('click', () => { const id = +b.dataset.stop; stopFilter = stopFilter === id ? null : id; render(); }));
    const q = $('pool-q').value.trim().toLowerCase();
    const match = p => (stopFilter === null || (p.stop ?? 0) === stopFilter) && (!q || (p.name + ' ' + p.booking).toLowerCase().includes(q));
    const unseated = state.passengers.filter(p => !p.seat);
    const pool = unseated.filter(match);
    // While searching, seated passengers are found too, so anyone can be located on the plan.
    const seatedHits = q ? state.passengers.filter(p => p.seat && match(p)) : [];
    $('pool-count').textContent = unseated.length;
    $('pool-count').className = 'badge ' + (unseated.length ? (state.vehicles.length ? 'bg-warning' : 'bg-secondary') : 'bg-success');
    const chip = p => `<div class="pax-chip ${p.id === selected ? 'selected' : ''}" data-pax="${p.id}" ${edit ? 'draggable="true"' : ''} style="--stop:${p.colour}" role="button" tabindex="0" title="${esc(p.name)}${p.seat ? ' — seat ' + esc(p.seat) : ''}">
        <span class="ini">${esc(initials(p.name))}</span><div class="flex-grow-1 min-w-0"><div class="nm">${esc(p.name)}${p.category ? ` <span class="badge bg-secondary">${esc(p.category)}</span>` : ''}</div>${p.seat ? `<div class="vb-sub">${state.vehicles.length > 1 ? esc(busName(p.vehicle)) + ' · ' : ''}Seat ${esc(p.seat)}</div>` : ''}</div></div>`;
    const groups = [];
    pool.forEach(p => { const g = groups[groups.length - 1]; if (g && g.id === p.bookingId) g.items.push(p); else groups.push({ id: p.bookingId, booking: p.booking, stop: p.stopName, colour: p.colour, items: [p] }); });
    let html = groups.map(g => `<div class="vb-group"><div class="vb-group-head"><b>${esc(g.booking)}</b><span class="stop-dot" style="--stop:${g.colour}"></span><span class="vb-stop-name">${esc(g.stop || 'No stop')}</span><span class="ms-auto">${plural(g.items.length, 'person', 'people')}</span></div>${g.items.map(chip).join('')}</div>`).join('');
    if (seatedHits.length) html += `<div class="vb-group"><div class="vb-group-head"><b>Already seated</b><span class="ms-auto">${seatedHits.length}</span></div>${seatedHits.map(chip).join('')}</div>`;
    if (!html) html = `<div class="crm-empty"><i class="${unseated.length ? 'ri-search-line' : 'ri-check-double-line'}"></i>${unseated.length || q ? 'Nobody matches.' : (state.passengers.length ? 'Everyone has a seat.' : 'No passengers booked on this date yet.')}</div>`;
    $('pool-list').innerHTML = html;
    $('pool-list').querySelectorAll('[data-pax]').forEach(el => {
      const pick = () => { const id = +el.dataset.pax; selected = selected === id ? null : id; revealSeat = selected !== null && !!(pax(id) || {}).seat; render(); };
      el.addEventListener('click', pick);
      el.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
      el.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', el.dataset.pax));
    });
  }

  // Selected passenger: details and actions
  function renderSelected() {
    const s = selected !== null ? pax(selected) : null;
    $('selected-card').classList.toggle('d-none', !s);
    if (!s) return;
    const bus = state.vehicles.find(v => v.id === s.vehicle);
    $('selected-card').style.setProperty('--stop', s.colour);
    $('sel-ini').textContent = initials(s.name);
    $('sel-name').textContent = s.name;
    $('sel-seat').innerHTML = s.seat ? `<i class="ri-checkbox-circle-line me-1 text-success"></i>${bus && state.vehicles.length > 1 ? esc(bus.label) + ' · ' : ''}Seat ${esc(s.seat)}` : '<i class="ri-error-warning-line me-1"></i>No seat yet';
    const digits = s.phone ? s.phone.replace(/[^\d+]/g, '') : '';
    const rows = [['Pickup', `<span class="stop-dot me-1" style="--stop:${s.colour}"></span>${esc(s.stopName || '—')}`], ['Booking', s.bookingUrl ? `<a href="${s.bookingUrl}" target="_blank">${esc(s.booking)}</a>` : esc(s.booking)], ['Phone', s.phone ? `<a href="tel:${esc(digits)}">${esc(s.phone)}</a> · <a href="https://wa.me/${esc(digits.replace(/\D/g, ''))}" target="_blank" rel="noopener" title="WhatsApp"><i class="ri-whatsapp-line"></i></a>` : '—'], ['Meal', esc(s.meal || '—')]];
    if (s.category) rows.splice(1, 0, ['Type', esc(s.category)]);
    if (s.payment) rows.push(['Payment', esc(s.payment)]);
    $('sel-details').innerHTML = rows.map(([k, v]) => `<dt class="col-4">${k}</dt><dd class="col-8">${v}</dd>`).join('');
    $('sel-actions').innerHTML = edit ? (s.seat ? '<button type="button" class="btn btn-light text-danger" id="unseat"><i class="ri-user-unfollow-line me-1"></i>Unseat</button>' : '') + '<span class="vb-sub">' + (mode() === 'block' ? 'Switch to “Seat passengers” to place them.' : s.seat ? 'Tap another seat to move them — a taken seat swaps.' : 'Tap a free seat to seat them.') + '</span>' : '';
    if ($('unseat')) $('unseat').addEventListener('click', () => move(s.id, null, null));
  }

  function render() {
    if ($('undo')) $('undo').disabled = !history.length;
    // Auto-seat is the obvious next step while people still need a seat and a bus has room.
    if ($('autofill')) $('autofill').className = 'btn ' + (state.vehicles.length && state.passengers.some(p => !p.seat) ? 'btn-primary' : 'btn-light');
    if ($('vb-tools')) $('vb-tools').classList.toggle('d-none', !state.vehicles.length); // nothing to seat on without a bus
    renderSummary(); renderPublish(); renderBuses(); renderPool(); renderSelected();
    if (revealSeat) { revealSeat = false; const el = document.querySelector('.seat-plan.board .cell.selected'); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' }); }
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
