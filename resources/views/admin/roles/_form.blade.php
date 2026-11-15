{{-- Shared by create and edit: name, then the permission grid, with Save always in reach. --}}
<form action="{{ $action }}" method="post">@csrf @if($role?->exists) @method('PUT') @endif
  @include('admin.includes.validation_errors')
  <div class="card crm-filters"><div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-6 col-xl-4"><label for="role-name" class="form-label">Role name</label><input class="form-control @error('name') is-invalid @enderror" name="name" id="role-name" value="{{ old('name', $role?->name) }}" maxlength="100" required placeholder="e.g. Sales Manager">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="col">@if($role?->exists)<p class="text-muted mb-0"><i class="ri-information-line me-1"></i>Changes apply immediately to the {{ $role->users()->count() }} {{ str('person')->plural($role->users()->count()) }} with this role.</p>@endif</div>
      @if($role?->exists && ($defaults = \App\Support\StaffRoles::defaultsFor($role->name)))
        <div class="col-auto"><button type="button" class="btn btn-outline-secondary" data-defaults="{{ json_encode($defaults) }}" title="Ticks exactly the recommended permissions; nothing is saved until you press Save"><i class="ri-restart-line me-1"></i>Use default permissions</button></div>
      @endif
    </div>
  </div></div>
  @include('admin.roles._permissions', ['checked' => old('permission', $assigned)])
  <div class="d-flex gap-2 py-3 position-sticky bottom-0 bg-body" style="z-index:2">
    <button class="btn btn-primary"><i class="ri-save-line me-1"></i>{{ $role?->exists ? 'Save role' : 'Create role' }}</button>
    <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.querySelector('[data-defaults]');
  if (!btn) return;
  btn.addEventListener('click', () => {
    const wanted = new Set(JSON.parse(btn.dataset.defaults));
    document.querySelectorAll('input[name="permission[]"]').forEach(b => { b.checked = wanted.has(b.value); b.dispatchEvent(new Event('change', { bubbles: true })); });
  });
});
</script>
