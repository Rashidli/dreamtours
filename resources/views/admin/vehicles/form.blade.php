@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-vehicles.css') }}?v={{ @filemtime(public_path('assets/css/admin-vehicles.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@php
  $types = \App\Models\VehicleLayout::TYPES;
  $toolHints = ['seat' => 'Bookable', 'leader' => 'Not bookable', 'driver' => '', 'door' => '', 'aisle' => 'Walkway', 'empty' => 'Nothing'];
@endphp

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-end justify-content-between gap-2">
                        <div class="min-w-0">
                          <a class="vb-back" href="{{ route('vehicle-layouts.index') }}"><i class="ri-arrow-left-line"></i><span>Vehicles & seating</span></a>
                          <div class="vb-title"><h4>{{ $layout->exists ? $layout->name : 'New seating template' }}</h4>@if($layout->exists && !$layout->is_active)<span class="badge bg-secondary">Hidden</span>@endif</div>
                        </div>
                        <div class="vb-head-actions">
                          <a class="btn btn-light" href="{{ route('vehicle-layouts.index') }}">Cancel</a>
                          <button class="btn btn-primary" form="layout-form"><i class="ri-save-line me-1"></i>Save template</button>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.validation_errors')

<form method="post" action="{{ $layout->exists ? route('vehicle-layouts.update', $layout) : route('vehicle-layouts.store') }}" id="layout-form">@csrf @if($layout->exists) @method('PUT') @endif
<input type="hidden" name="layout" id="layout-json" value="{{ old('layout', json_encode($layout->layout)) }}">
<div class="row g-3">
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-bus-line"></i></div><div><h5>Template details</h5><p>How staff pick this vehicle when adding a bus to a date.</p></div></div>
      <div class="mb-3"><label class="form-label" for="v-name">Name</label><input class="form-control" id="v-name" name="name" value="{{ old('name', $layout->name) }}" maxlength="100" required placeholder="e.g. Coach 56 — ABC Bus Lines"><div class="form-text">Include the operator or seat count so it is easy to tell apart.</div></div>
      <div class="mb-3"><label class="form-label" for="v-tr">Vehicle type <span class="text-muted fw-normal">(optional)</span></label><select class="form-select" id="v-tr" name="transportation_id"><option value="">—</option>@foreach($transportations as $t)<option value="{{ $t->id }}" @selected(old('transportation_id', $layout->transportation_id) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
      <div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="v-active" name="is_active" value="1" @checked(old('is_active', $layout->is_active))><label class="form-check-label" for="v-active">Offer this template when adding a bus to a date<small>Switch off to hide an old vehicle; dates that use it keep their seats.</small></label></div>
    </div></div>

    <div class="card"><div class="card-body">
      <div class="tf-head"><div class="tf-icon"><i class="ri-layout-grid-line"></i></div><div><h5>Start from a standard layout</h5><p>Builds a front row (driver, leader seat, door) and rows of seats either side of the aisle. You can change any cell afterwards.</p></div></div>
      <div class="row g-2 align-items-end">
        <div class="col-4"><label class="form-label" for="g-rows">Rows</label><input class="form-control" type="number" id="g-rows" min="1" max="{{ \App\Models\VehicleLayout::MAX_ROWS - 2 }}" value="10"></div>
        <div class="col-4"><label class="form-label" for="g-left">Left seats</label><input class="form-control" type="number" id="g-left" min="1" max="3" value="2"></div>
        <div class="col-4"><label class="form-label" for="g-right">Right seats</label><input class="form-control" type="number" id="g-right" min="1" max="3" value="2"></div>
        <div class="col-12"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="g-back"><label class="form-check-label" for="g-back">Back bench across the aisle</label></div></div>
        <div class="col-12"><div class="form-text mt-0 mb-1" id="g-preview"></div><button type="button" class="btn btn-light w-100" id="g-build"><i class="ri-layout-grid-line me-1"></i>Build this layout</button></div>
      </div>
    </div></div>
  </div>

  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      <div class="vb-plan-bar">
        <div class="tf-head mb-0"><div class="tf-icon"><i class="ri-artboard-line"></i></div><div><h5>Seat plan <span class="vb-count-pill ms-1"><span id="seat-count">0</span>&nbsp;passenger seats</span></h5><p>Pick what to paint, then click or drag across the plan.</p></div></div>
      </div>
      <div class="vb-tools mb-3" role="radiogroup" aria-label="Paint with">
        @foreach($types as $type => [$label, $mark])
          <input type="radio" class="btn-check" name="tool" id="tool-{{ $type }}" value="{{ $type }}" @checked($type === 'seat')>
          <label class="vb-tool" for="tool-{{ $type }}" title="{{ $label }}"><span class="seat-plan"><span class="cell t-{{ $type }}">{{ $mark }}</span></span>{{ $label }}</label>
        @endforeach
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <button type="button" class="btn btn-light" data-act="undo" title="Undo the last change (Ctrl+Z)"><i class="ri-arrow-go-back-line me-1"></i>Undo</button>
        <button type="button" class="btn btn-light" data-act="renumber" title="Number seats 1, 2, 3… row by row"><i class="ri-list-ordered me-1"></i>Renumber</button>
        <span class="vb-stepper ms-sm-2"><span class="vb-stepper-label">Rows</span><button type="button" class="btn btn-light" data-act="row-" title="Remove the last row" aria-label="Remove a row"><i class="ri-subtract-line"></i></button><button type="button" class="btn btn-light" data-act="row+" title="Add a row at the back" aria-label="Add a row"><i class="ri-add-line"></i></button></span>
        <span class="vb-stepper ms-sm-2"><span class="vb-stepper-label">Columns</span><button type="button" class="btn btn-light" data-act="col-" title="Remove the right-hand column" aria-label="Remove a column"><i class="ri-subtract-line"></i></button><button type="button" class="btn btn-light" data-act="col+" title="Add a column on the right" aria-label="Add a column"><i class="ri-add-line"></i></button></span>
      </div>
      <div class="vb-canvas"><div class="vb-bus"><span class="vb-front"><i class="ri-arrow-up-line"></i>Front</span><div class="seat-plan editing" id="plan"></div><span class="vb-front">Back</span></div></div>
      <div class="alert alert-warning mt-3 mb-0 d-none" id="dupes"></div>
      <ul class="vb-help">
        <li><i class="ri-cursor-line"></i>Click or drag to paint cells</li>
        <li><i class="ri-edit-2-line"></i>Double-click a seat to change its number</li>
        <li><span class="vb-kbd">Ctrl</span>+<span class="vb-kbd">Z</span> undo</li>
      </ul>
    </div></div>
  </div>
</div>
{{-- Contextual save bar (shared tf-savebar): slides over the top bar while there are unsaved changes. --}}
<div class="tf-savebar" id="savebar"><div class="tf-savebar-inner">
  <span class="tf-savebar-title"><i class="ri-error-warning-line"></i>Unsaved changes</span>
  <span class="d-flex gap-2"><a class="btn btn-light" href="{{ url()->current() }}" onclick="window.vbDiscard = true">Discard</a><button class="btn btn-success"><i class="ri-save-line me-1"></i>Save template</button></span>
</div></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const MARKS = @json(collect($types)->map(fn ($t) => $t[1]));
  const NAMES = @json(collect($types)->map(fn ($t) => $t[0]));
  const MAX_ROWS = {{ \App\Models\VehicleLayout::MAX_ROWS }}, MAX_COLS = {{ \App\Models\VehicleLayout::MAX_COLS }};
  const input = document.getElementById('layout-json'), planEl = document.getElementById('plan');
  let plan = JSON.parse(input.value), history = [], painting = false, initial = null, submitting = false;

  const snapshot = () => { history.push(JSON.stringify(plan)); if (history.length > 50) history.shift(); };
  const seats = () => plan.cells.flat().filter(c => c.t === 'seat');
  const nextLabel = () => { const used = new Set(seats().map(c => String(c.l))); let n = 1; while (used.has(String(n))) n++; return String(n); };
  const renumber = () => { let n = 0; plan.cells.forEach(row => row.forEach(c => { if (c.t === 'seat') c.l = String(++n); else delete c.l; })); };

  function render() {
    plan.rows = plan.cells.length; plan.cols = plan.cells[0].length;
    planEl.style.gridTemplateColumns = `repeat(${plan.cols}, auto)`;
    planEl.innerHTML = '';
    const counts = {};
    seats().forEach(c => { const k = String(c.l).toUpperCase(); counts[k] = (counts[k] || 0) + 1; });
    plan.cells.forEach((row, r) => row.forEach((c, i) => {
      const d = document.createElement('div');
      d.className = 'cell t-' + c.t + (c.t === 'seat' && counts[String(c.l).toUpperCase()] > 1 ? ' border-danger border-2' : '');
      d.textContent = c.t === 'seat' ? c.l : MARKS[c.t];
      d.title = c.t === 'seat' ? 'Seat ' + c.l : NAMES[c.t];
      d.dataset.r = r; d.dataset.c = i;
      planEl.appendChild(d);
    }));
    document.getElementById('seat-count').textContent = seats().length;
    const dupes = Object.keys(counts).filter(k => counts[k] > 1);
    const box = document.getElementById('dupes');
    box.classList.toggle('d-none', !dupes.length);
    box.textContent = dupes.length ? 'These seat numbers appear twice: ' + dupes.join(', ') + '. Double-click one to rename it, or press Renumber.' : '';
    input.value = JSON.stringify(plan);
    if (initial === null) initial = input.value;
    markDirty();
  }

  // Unsaved-changes hint in the save bar, and a warning before leaving with changes.
  const form = document.getElementById('layout-form');
  const fieldsStart = () => [...form.querySelectorAll('#v-name, #v-tr, #v-active')].map(f => f.type === 'checkbox' ? f.checked : f.value).join('|');
  let fieldsInitial = null;
  function markDirty() {
    if (fieldsInitial === null) fieldsInitial = fieldsStart();
    const dirty = input.value !== initial || fieldsStart() !== fieldsInitial;
    document.body.classList.toggle('tf-is-dirty', dirty);
    return dirty;
  }
  form.querySelectorAll('#v-name, #v-tr, #v-active').forEach(f => f.addEventListener('input', markDirty));
  form.querySelectorAll('#v-tr, #v-active').forEach(f => f.addEventListener('change', markDirty));
  window.addEventListener('beforeunload', e => { if (!submitting && !window.vbDiscard && markDirty()) { e.preventDefault(); e.returnValue = ''; } });
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'z' && !e.target.closest('input[type=text], input:not([type]), input[type=number], textarea, select')) {
      e.preventDefault(); document.querySelector('[data-act=undo]').click();
    }
  });
  // What "Build this layout" will make, before replacing the plan.
  const preview = () => {
    const rows = +document.getElementById('g-rows').value || 0, left = +document.getElementById('g-left').value || 0, right = +document.getElementById('g-right').value || 0;
    const n = rows * (left + right) + (document.getElementById('g-back').checked ? left + right + 1 : 0);
    document.getElementById('g-preview').textContent = n > 0 ? `Makes ${n} passenger seats, ${left}+${right} across.` : '';
  };
  ['g-rows', 'g-left', 'g-right', 'g-back'].forEach(id => document.getElementById(id).addEventListener('input', preview));
  preview();

  function paint(el) {
    const tool = document.querySelector('input[name=tool]:checked').value;
    const cell = plan.cells[el.dataset.r][el.dataset.c];
    if (cell.t === tool) return;
    plan.cells[el.dataset.r][el.dataset.c] = tool === 'seat' ? { t: 'seat', l: nextLabel() } : { t: tool };
    render();
  }

  planEl.addEventListener('mousedown', e => { const el = e.target.closest('.cell'); if (!el) return; e.preventDefault(); snapshot(); painting = true; paint(el); });
  planEl.addEventListener('mouseover', e => { const el = e.target.closest('.cell'); if (painting && el) paint(el); });
  document.addEventListener('mouseup', () => { painting = false; });
  planEl.addEventListener('touchstart', e => { const el = e.target.closest('.cell'); if (!el) return; snapshot(); paint(el); }, { passive: true });
  planEl.addEventListener('dblclick', e => {
    const el = e.target.closest('.cell'); if (!el) return;
    const cell = plan.cells[el.dataset.r][el.dataset.c]; if (cell.t !== 'seat') return;
    const label = (prompt('Seat number (letters and digits, up to 10):', cell.l) || '').trim();
    if (!label || label === cell.l) return;
    if (!/^[\p{L}\p{N}-]{1,10}$/u.test(label)) { alert('Use 1–10 letters or digits.'); return; }
    snapshot(); cell.l = label; render();
  });

  document.querySelectorAll('[data-act]').forEach(btn => btn.addEventListener('click', () => {
    const act = btn.dataset.act;
    if (act === 'undo') { if (history.length) { plan = JSON.parse(history.pop()); render(); } return; }
    snapshot();
    if (act === 'renumber') renumber();
    if (act === 'row+' && plan.cells.length < MAX_ROWS) plan.cells.push(plan.cells[plan.cells.length - 1].map(c => c.t === 'seat' ? { t: 'seat', l: '' } : { t: c.t }));
    if (act === 'row-' && plan.cells.length > 1) plan.cells.pop();
    if (act === 'col+' && plan.cells[0].length < MAX_COLS) plan.cells.forEach(row => row.push({ t: 'empty' }));
    if (act === 'col-' && plan.cells[0].length > 1) plan.cells.forEach(row => row.pop());
    plan.cells.flat().forEach(c => { if (c.t === 'seat' && !c.l) c.l = nextLabel(); });
    render();
  }));

  document.getElementById('g-build').addEventListener('click', () => {
    if (seats().length && !confirm('Replace the current plan with a new standard layout?')) return;
    const rows = +document.getElementById('g-rows').value, left = +document.getElementById('g-left').value, right = +document.getElementById('g-right').value;
    const cols = left + 1 + right;
    if (rows < 1 || rows > MAX_ROWS - 2 || cols > MAX_COLS) { alert('Use 1–' + (MAX_ROWS - 2) + ' rows and at most ' + (MAX_COLS - 1) + ' seats across.'); return; }
    snapshot();
    const front = Array.from({ length: cols }, () => ({ t: 'empty' }));
    front[0] = { t: 'driver' }; front[left] = { t: 'aisle' }; front[left + 1] = { t: 'leader' };
    if (right > 1) front[cols - 1] = { t: 'door' };
    const cells = [front];
    for (let r = 0; r < rows; r++) cells.push(Array.from({ length: cols }, (_, c) => ({ t: c === left ? 'aisle' : 'seat' })));
    if (document.getElementById('g-back').checked) cells.push(Array.from({ length: cols }, () => ({ t: 'seat' })));
    plan = { rows: cells.length, cols, cells };
    renumber(); render();
  });

  document.getElementById('layout-form').addEventListener('submit', e => {
    if (!seats().length) { e.preventDefault(); alert('Add at least one passenger seat.'); return; }
    submitting = true;
  });
  render();
});
</script>
</div></div></div>@include('admin.includes.footer')
