{{-- Shared by create and edit: name, then the permission grid, with Save always in reach. --}}
<form action="{{ $action }}" method="post" class="tm-form" id="role-form">@csrf @if($role?->exists) @method('PUT') @endif
  @include('admin.includes.validation_errors')
  <div class="card"><div class="card-body">
    <div class="tf-head"><span class="tf-icon"><i class="ri-shield-user-line"></i></span><div><h5>Role details</h5><p>@if($role?->exists)Changes apply immediately to the {{ $role->users()->count() }} {{ str('person')->plural($role->users()->count()) }} with this role.@else A clear name helps when you pick a role for a new staff account.@endif</p></div></div>
    <div class="row g-3 align-items-end">
      <div class="col-md-6 col-xl-5"><label for="role-name" class="fw-semibold">Role name</label><input class="form-control @error('name') is-invalid @enderror" name="name" id="role-name" value="{{ old('name', $role?->name) }}" maxlength="100" required placeholder="e.g. Sales Manager">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      @if($role?->exists && ($defaults = \App\Support\StaffRoles::defaultsFor($role->name)))
        <div class="col-md-auto ms-md-auto"><button type="button" class="btn btn-outline-secondary" data-defaults="{{ json_encode($defaults) }}" title="Ticks exactly the recommended permissions; nothing is saved until you press Save"><i class="ri-restart-line me-1"></i>Use default permissions</button></div>
      @endif
    </div>
  </div></div>
  @include('admin.roles._permissions', ['checked' => old('permission', $assigned)])
  <div class="tm-savebar">
    <span class="tm-savebar-note"><span class="tm-clean-note"><i class="ri-information-line"></i> Nothing is saved until you press {{ $role?->exists ? 'Save role' : 'Create role' }}.</span><span class="tm-dirty-note"><i class="ri-edit-2-line"></i> Unsaved changes</span></span>
    <div class="tm-savebar-actions">
      <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
      <button class="btn btn-primary"><i class="ri-save-line me-1"></i>{{ $role?->exists ? 'Save role' : 'Create role' }}</button>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('role-form');
  if (form) {
    const dirty = () => form.classList.add('tm-is-dirty');
    form.addEventListener('change', dirty);
    form.addEventListener('input', dirty);
    form.addEventListener('click', e => { if (e.target.closest('.group-checkbox, [data-row], [data-defaults]')) dirty(); });
  }
  const btn = document.querySelector('[data-defaults]');
  if (!btn) return;
  btn.addEventListener('click', () => {
    const wanted = new Set(JSON.parse(btn.dataset.defaults));
    document.querySelectorAll('input[name="permission[]"]').forEach(b => { b.checked = wanted.has(b.value); b.dispatchEvent(new Event('change', { bubbles: true })); });
  });
});
</script>
