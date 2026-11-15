@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Roles</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Roles</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.flash')

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">A role is a set of permissions; each staff account has one. Who leads or drives a trip is set on the departure date, not here.</p>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search roles" aria-label="Search roles">@if(request('q'))<a class="btn btn-light" href="{{ route('roles.index') }}">Reset</a>@endif</form>
    @can('create-roles')<a href="{{ route('roles.create') }}" class="btn btn-primary text-nowrap"><i class="ri-add-line me-1"></i>New role</a>@endcan
  </div>
</div>

<div class="card"><div class="card-body">
  <div class="table-responsive"><table class="table table-hover crm-table mb-0">
    <thead><tr><th>Role</th><th class="text-end">People</th><th class="text-end">Permissions</th><th></th></tr></thead>
    <tbody>
    @forelse($roles as $role)
      <tr>
        <td><span class="fw-semibold">{{ $role->name }}</span>@if($hint = \App\Support\PermissionGroups::ROLE_HINTS[$role->name] ?? null)<span class="sub">{{ $hint }}</span>@endif</td>
        <td class="amount fw-normal">@can('list-users')<a href="{{ route('users.index', ['role' => $role->name]) }}">{{ $role->users_count }}</a>@else{{ $role->users_count }}@endcan</td>
        <td class="amount fw-normal">{{ $role->name === 'superadmin' ? 'All' : $role->permissions_count }}</td>
        <td class="text-end text-nowrap">
          @if($role->name === 'superadmin')
            <span class="badge rounded-pill bg-light text-muted">System role</span>
          @else
            @can('edit-roles')<a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>@endcan
            @can('delete-roles')<form action="{{ route('roles.destroy', $role->id) }}" method="post" class="d-inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from($role->users_count ? "Delete “{$role->name}”? {$role->users_count} people have this role and will lose its access." : "Delete “{$role->name}”?") }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="ri-delete-bin-line"></i></button></form>@endcan
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="4" class="crm-empty"><i class="ri-shield-user-line"></i>No roles match.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <div class="mt-3">{{ $roles->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>@include('admin.includes.footer')
