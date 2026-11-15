{{-- Role editor: one card per section. Plain View/Create/Edit/Delete permissions are a grid
     (one row per area); everything else is a labelled switch with what it allows. Inputs stay
     permission[] so the controller is unchanged; the footer's .group-checkbox toggles a section. --}}
<p class="text-muted">Tick what people with this role may do. <b>View</b> is needed to open an area at all; Create, Edit and Delete only add to it. Every check also runs on the server, so a hidden button never means hidden data.</p>
<div class="row g-3">
@foreach($permissions as $groupName => $groupPermissions)
  @php
    $checked = $checked ?? [];
    $groupId = 'group-'.\Illuminate\Support\Str::slug($groupName);
    $grid = \App\Support\PermissionGroups::grid($groupPermissions);
    $on = $groupPermissions->filter(fn ($p) => in_array($p->name, $checked, true))->count();
  @endphp
  <div class="col-xl-6">
    <div class="card h-100 mb-0" data-section="{{ $groupId }}">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="form-check mb-0">
          <input class="form-check-input group-checkbox" type="checkbox" id="{{ $groupId }}" data-group="{{ $groupName }}" @checked($on === $groupPermissions->count())>
          <label class="form-check-label fw-semibold" for="{{ $groupId }}">{{ $groupName }}</label>
        </div>
        <span class="text-muted" data-count>{{ $on }} of {{ $groupPermissions->count() }}</span>
      </div>
      <div class="card-body">
        @if($grid['rows'])
          <div class="table-responsive"><table class="table crm-table mb-0">
            <thead><tr><th>Area</th>@foreach(\App\Support\PermissionGroups::VERB_LABELS as $verbLabel)<th class="text-center" style="width:68px">{{ $verbLabel }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach($grid['rows'] as $resource => $cells)
              <tr>
                <td><button type="button" class="btn btn-link p-0 text-body text-start text-decoration-none" data-row title="Tick or clear the whole row">{{ \App\Support\PermissionGroups::resourceLabel($resource) }}</button></td>
                @foreach(array_keys(\App\Support\PermissionGroups::VERB_LABELS) as $verb)
                  <td class="text-center">
                    @if($permission = $cells[$verb] ?? null)
                      <input class="form-check-input" type="checkbox" name="permission[]" value="{{ $permission->name }}" data-group="{{ $groupName }}" id="perm-{{ $permission->id }}" title="{{ $permission->name }}" aria-label="{{ \App\Support\PermissionGroups::label($permission->name) }}" @checked(in_array($permission->name, $checked, true))>
                    @else
                      <span class="text-muted" aria-hidden="true">·</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
            </tbody>
          </table></div>
        @endif
        @if($grid['special'])
          <div class="{{ $grid['rows'] ? 'mt-3 pt-3 border-top' : '' }}">
            @foreach($grid['special'] as $permission)
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="perm-{{ $permission->id }}" name="permission[]" value="{{ $permission->name }}" data-group="{{ $groupName }}" @checked(in_array($permission->name, $checked, true))>
                <label class="form-check-label" for="perm-{{ $permission->id }}" title="{{ $permission->name }}"><span class="fw-semibold">{{ \App\Support\PermissionGroups::label($permission->name) }}</span>@if($hint = \App\Support\PermissionGroups::SPECIAL[$permission->name][1] ?? null)<span class="d-block text-muted">{{ $hint }}</span>@endif</label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
@endforeach
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const recount = card => {
    const boxes = card.querySelectorAll('input[name="permission[]"]');
    const on = [...boxes].filter(b => b.checked).length;
    card.querySelector('[data-count]').textContent = on + ' of ' + boxes.length;
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
