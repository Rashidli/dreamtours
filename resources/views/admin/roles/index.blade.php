@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="tm-title"><h4>Roles</h4><span class="text-muted">{{ $roles->total() }} {{ Str::plural('role', $roles->total()) }}{{ request()->filled('q') ? ' match' : '' }}</span></div>
    @can('create-roles')<a href="{{ route('roles.create') }}" class="btn btn-primary text-nowrap"><i class="ri-add-line"></i> New role</a>@endcan
  </div>
  <p class="tm-lead">A role is a set of permissions; each staff account has one. Who leads or drives a trip is set on the departure date, not here.</p>

  @include('admin.includes.flash')

  <div class="card tl-card"><div class="card-body p-0">
    <form class="tl-search-bar" method="get" action="{{ route('roles.index') }}">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search roles" aria-label="Search roles">@if(request('q'))<a href="{{ route('roles.index') }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
      <button type="submit" class="visually-hidden">Search</button>
    </form>
    <div class="table-responsive"><table class="table table-hover align-middle tl-table tm-table mb-0">
      <thead><tr><th>Role</th><th class="text-end">People</th><th class="text-end">Permissions</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @forelse($roles as $role)
        <tr>
          <td>
            @if($role->name !== 'superadmin')@can('edit-roles')<a href="{{ route('roles.edit', $role->id) }}" class="tm-strong text-body">{{ $role->name }}</a>@else<span class="tm-strong">{{ $role->name }}</span>@endcan
            @else<span class="tm-strong">{{ $role->name }}</span>@endif
            @if($hint = \App\Support\PermissionGroups::ROLE_HINTS[$role->name] ?? null)<span class="d-block tm-muted">{{ $hint }}</span>@endif
          </td>
          <td class="text-end">@can('list-users')<a href="{{ route('users.index', ['role' => $role->name]) }}" title="See the people with this role">{{ $role->users_count }}</a>@else{{ $role->users_count }}@endcan</td>
          <td class="text-end">{{ $role->name === 'superadmin' ? 'All' : $role->permissions_count }}</td>
          <td class="text-end text-nowrap">
            @if($role->name === 'superadmin')
              <span class="badge bg-light" title="The superadmin role always has every permission."><i class="ri-lock-line"></i> System role</span>
            @else
              <div class="tm-actions">
                @can('edit-roles')<a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-secondary" aria-label="Edit {{ $role->name }}"><i class="ri-pencil-line"></i> Edit</a>@endcan
                @can('delete-roles')<form action="{{ route('roles.destroy', $role->id) }}" method="post" class="d-inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from($role->users_count ? "Delete “{$role->name}”? {$role->users_count} people have this role and will lose its access." : "Delete “{$role->name}”?") }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $role->name }}"><i class="ri-delete-bin-line"></i></button></form>@endcan
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="tm-empty"><i class="ri-shield-user-line"></i>
          @if(request()->filled('q'))<strong>No roles match “{{ request('q') }}”</strong><a href="{{ route('roles.index') }}">Clear the search</a>
          @else<strong>No roles yet</strong>Create a role, then give it to staff accounts.@endif
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $roles->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>
@include('admin.includes.footer')
