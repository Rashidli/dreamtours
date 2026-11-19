{{-- Shared by permissions/create and permissions/edit. $permission is null when adding one. --}}
<form action="{{ $action }}" method="post" class="tm-form">
  @csrf
  @if($permission){{ method_field('PUT') }}@endif
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-key-2-line"></i></span><div><h5>Permission</h5><p>A code name the app checks, and the section of the role editor it appears in.</p></div></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="permission-name" class="fw-semibold">Name</label>
            <input class="form-control tm-mono @error('name') is-invalid @enderror" type="text" name="name" id="permission-name" value="{{ old('name', $permission->name ?? '') }}" placeholder="e.g. list-tours" autocomplete="off">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Lowercase words joined by dashes, usually <span class="tm-mono">list-</span>, <span class="tm-mono">create-</span>, <span class="tm-mono">edit-</span> or <span class="tm-mono">delete-</span> plus the area.</div>@enderror
          </div>
          <div class="col-md-6">
            <label for="permission-group" class="fw-semibold">Group</label>
            <input class="form-control @error('group_name') is-invalid @enderror" type="text" name="group_name" id="permission-group" list="permissionGroups" value="{{ old('group_name', $permission->group_name ?? '') }}" autocomplete="off">
            <datalist id="permissionGroups">
              @foreach(\App\Support\PermissionGroups::sectionOrder() as $section)
                <option value="{{ $section }}">
              @endforeach
            </datalist>
            @error('group_name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Pick an existing section so it shows up in the right card of the role editor.</div>@enderror
          </div>
        </div>
      </div></div>
    </div>
    <div class="col-xl-4">
      <div class="card tm-side-card"><div class="card-body">
        <h5 class="fs-6 mb-1">{{ $permission ? 'Save permission' : 'New permission' }}</h5>
        <p class="text-muted mb-0">{{ $permission ? 'Renaming a permission the app already checks will close that screen until the code is updated.' : 'New permissions start switched off for every role. Turn them on in the role editor.' }}</p>
        <div class="d-grid gap-2 mt-3">
          <button class="btn btn-primary btn-lg"><i class="ri-save-line me-1"></i>{{ $permission ? 'Save changes' : 'Create permission' }}</button>
          <a href="{{ route('permissions.index') }}" class="btn btn-light">Cancel</a>
        </div>
      </div></div>
    </div>
  </div>
</form>
