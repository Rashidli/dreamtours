@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">
@php($types = \App\Models\VehicleLayout::TYPES)

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $layout->exists ? 'Edit '.$layout->name : 'New seating template' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('vehicle-layouts.index') }}">Vehicles & seating</a></li>
                                <li class="breadcrumb-item active">{{ $layout->exists ? 'Edit' : 'New' }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.validation_errors')

<form method="post" action="{{ $layout->exists ? route('vehicle-layouts.update', $layout) : route('vehicle-layouts.store') }}" id="layout-form">@csrf @if($layout->exists) @method('PUT') @endif
<input type="hidden" name="layout" id="layout-json" value="{{ old('layout', json_encode($layout->layout)) }}">
<div class="row g-3">
  <div class="col-xl-4">
    <div class="card crm-filters"><div class="card-body">
      <div class="mb-3"><label class="form-label" for="v-name">Name</label><input class="form-control" id="v-name" name="name" value="{{ old('name', $layout->name) }}" maxlength="100" required placeholder="e.g. Coach 56 — ABC Bus Lines"></div>
      <div class="mb-3"><label class="form-label" for="v-tr">Vehicle type <span class="text-muted fw-normal">(optional)</span></label><select class="form-select" id="v-tr" name="transportation_id"><option value="">—</option>@foreach($transportations as $t)<option value="{{ $t->id }}" @selected(old('transportation_id', $layout->transportation_id) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="v-active" name="is_active" value="1" @checked(old('is_active', $layout->is_active))><label class="form-check-label" for="v-active">Offer this template when adding a bus to a date</label></div>
    </div></div>

    <div class="card crm-filters"><div class="card-body">
      <h5 class="card-title mb-1">Start from a standard layout</h5>
      <p class="text-muted">Builds a front row (driver, leader seat, door) and rows of seats either side of the aisle. You can change any cell afterwards.</p>
      <div class="row g-2 align-items-end">
        <div class="col-4"><label class="form-label" for="g-rows">Rows</label><input class="form-control" type="number" id="g-rows" min="1" max="{{ \App\Models\VehicleLayout::MAX_ROWS - 2 }}" value="10"></div>
        <div class="col-4"><label class="form-label" for="g-left">Left seats</label><input class="form-control" type="number" id="g-left" min="1" max="3" value="2"></div>
        <div class="col-4"><label class="form-label" for="g-right">Right seats</label><input class="form-control" type="number" id="g-right" min="1" max="3" value="2"></div>
        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="g-back"><label class="form-check-label" for="g-back">Back bench across the aisle</label></div></div>
        <div class="col-12"><button type="button" class="btn btn-outline-primary w-100" id="g-build"><i class="ri-layout-grid-line me-1"></i>Build this layout</button></div>
      </div>
    </div></div>
  </div>

  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
        <h5 class="mb-0">Plan <span class="text-muted fw-normal">· <span id="seat-count">0</span> passenger seats</span></h5>
        <div class="d-flex flex-wrap gap-1">
          <button type="button" class="btn btn-sm btn-light" data-act="undo" title="Undo the last change"><i class="ri-arrow-go-back-line"></i> Undo</button>
          <button type="button" class="btn btn-sm btn-light" data-act="renumber" title="Number seats 1, 2, 3… row by row"><i class="ri-sort-number-asc"></i> Renumber</button>
          <button type="button" class="btn btn-sm btn-light" data-act="row+"><i class="ri-add-line"></i> Row</button>
          <button type="button" class="btn btn-sm btn-light" data-act="row-"><i class="ri-subtract-line"></i> Row</button>
          <button type="button" class="btn btn-sm btn-light" data-act="col+"><i class="ri-add-line"></i> Column</button>
          <button type="button" class="btn btn-sm btn-light" data-act="col-"><i class="ri-subtract-line"></i> Column</button>
        </div>
      </div>
      <div class="mb-3" role="radiogroup" aria-label="Paint with">
        <span class="fw-semibold me-2">Paint with:</span>
        @foreach($types as $type => [$label, $mark])
          <input type="radio" class="btn-check" name="tool" id="tool-{{ $type }}" value="{{ $type }}" @checked($type === 'seat')>
          <label class="btn btn-sm btn-outline-secondary mb-1" for="tool-{{ $type }}"><span class="seat-plan d-inline-block p-0 border-0 bg-transparent align-middle me-1"><span class="cell t-{{ $type }}" style="width:16px;height:14px;font-size:9px;display:inline-flex">{{ $mark }}</span></span>{{ $label }}</label>
        @endforeach
      </div>
      <div class="overflow-auto pb-2"><div class="seat-plan editing" id="plan"></div></div>
      <p class="text-muted mt-2 mb-0"><i class="ri-information-line me-1"></i>Click or drag across cells to paint them. Double-click a seat to change its number. The front of the vehicle is at the top.</p>
      <div class="alert alert-warning mt-3 mb-0 d-none" id="dupes"></div>
    </div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Save template</button>
      <a class="btn btn-light" href="{{ route('vehicle-layouts.index') }}">Cancel</a>
    </div>
  </div>
</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const MARKS = @json(collect($types)->map(fn ($t) => $t[1]));
  const NAMES = @json(collect($types)->map(fn ($t) => $t[0]));
  const MAX_ROWS = {{ \App\Models\VehicleLayout::MAX_ROWS }}, MAX_COLS = {{ \App\Models\VehicleLayout::MAX_COLS }};
  const input = document.getElementById('layout-json'), planEl = document.getElementById('plan');
  let plan = JSON.parse(input.value), history = [], painting = false;

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
  }

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
    if (!seats().length) { e.preventDefault(); alert('Add at least one passenger seat.'); }
  });
  render();
});
</script>
</div></div></div>@include('admin.includes.footer')
