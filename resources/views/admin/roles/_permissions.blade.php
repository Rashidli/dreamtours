{{-- Role editor: one card per section. Plain View/Create/Edit/Delete permissions are a grid
     (one row per area); everything else is a labelled switch with what it allows. Inputs stay
     permission[] so the controller is unchanged; the footer's .group-checkbox toggles a section. --}}
<div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mt-4 mb-2">
  <h5 class="mb-0 fs-6 fw-semibold">Permissions</h5>
</div>
<div class="tm-perm-intro"><i class="ri-information-line" aria-hidden="true"></i><div><b>View</b> is needed to open an area at all; Create, Edit and Delete only add to it. Click an area name to tick or clear its whole row. Every check also runs on the server, so a hidden button never means hidden data.</div></div>
<div class="tm-perm-grid">
@foreach($permissions as $groupName => $groupPermissions)
  @php
    $checked = $checked ?? [];
    $groupId = 'group-'.\Illuminate\Support\Str::slug($groupName);
    $grid = \App\Support\PermissionGroups::grid($groupPermissions);
    $on = $groupPermissions->filter(fn ($p) => in_array($p->name, $checked, true))->count();
  @endphp
  <div class="tm-perm-item">
    <div class="card tm-perm-card" data-section="{{ $groupId }}">
      <div class="tm-perm-head">
        <div><h5>{{ $groupName }}</h5><span class="tm-count {{ $on ? 'tm-count-on' : '' }}" data-count>{{ $on }} of {{ $groupPermissions->count() }}</span></div>
        <div class="form-check tm-perm-all">
          <input class="form-check-input group-checkbox" type="checkbox" id="{{ $groupId }}" data-group="{{ $groupName }}" @checked($on === $groupPermissions->count())>
          <label class="form-check-label" for="{{ $groupId }}">Select all</label>
        </div>
      </div>
      @if($grid['rows'])
        <div class="table-responsive"><table class="table tm-perm-table">
          <thead><tr><th>Area</th>@foreach(\App\Support\PermissionGroups::VERB_LABELS as $verbLabel)<th class="tm-verb">{{ $verbLabel }}</th>@endforeach</tr></thead>
          <tbody>
          @foreach($grid['rows'] as $resource => $cells)
            <tr>
              <td><button type="button" class="tm-row-btn" data-row title="Tick or clear the whole row">{{ \App\Support\PermissionGroups::resourceLabel($resource) }}</button></td>
              @foreach(array_keys(\App\Support\PermissionGroups::VERB_LABELS) as $verb)
                <td class="tm-verb">
                  @if($permission = $cells[$verb] ?? null)
                    <input class="form-check-input" type="checkbox" name="permission[]" value="{{ $permission->name }}" data-group="{{ $groupName }}" id="perm-{{ $permission->id }}" title="{{ $permission->name }}" aria-label="{{ \App\Support\PermissionGroups::label($permission->name) }}" @checked(in_array($permission->name, $checked, true))>
                  @else
                    <span class="tm-none" title="Not available for this area">–</span>
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
          </tbody>
        </table></div>
      @endif
      @if($grid['special'])
        <div class="tm-specials {{ $grid['rows'] ? 'border-top' : '' }}">
          @if($grid['rows'])<div class="tm-specials-title">Other permissions</div>@endif
          @foreach($grid['special'] as $permission)
            <div class="form-check form-switch tm-special">
              <input class="form-check-input" type="checkbox" role="switch" id="perm-{{ $permission->id }}" name="permission[]" value="{{ $permission->name }}" data-group="{{ $groupName }}" @checked(in_array($permission->name, $checked, true))>
              <label class="form-check-label" for="perm-{{ $permission->id }}" title="{{ $permission->name }}"><b>{{ \App\Support\PermissionGroups::label($permission->name) }}</b>@if($hint = \App\Support\PermissionGroups::SPECIAL[$permission->name][1] ?? null)<span>{{ $hint }}</span>@endif</label>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endforeach
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const recount = card => {
    const boxes = card.querySelectorAll('input[name="permission[]"]');
    const on = [...boxes].filter(b => b.checked).length;
    const count = card.querySelector('[data-count]');
    count.textContent = on + ' of ' + boxes.length;
    count.classList.toggle('tm-count-on', on > 0);
    card.querySelector('.group-checkbox').checked = on === boxes.length;
  };
  document.querySelectorAll('[data-section]').forEach(card => {
    card.addEventListener('change', () => recount(card));
    // The footer's section toggle sets .checked without firing change, so recount after it runs.
    card.querySelector('.group-checkbox').addEventListener('click', () => setTimeout(() => recount(card)));
    card.querySelectorAll('[data-row]').forEach(btn => btn.addEventListener('click', () => {
      const boxes = btn.closest('tr').querySelectorAll('input[type=checkbox]');
      const target = ![...boxes].every(b => b.checked);
      boxes.forEach(b => { b.checked = target; });
      recount(card);
    }));
  });
});
</script>
