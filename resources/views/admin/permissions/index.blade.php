@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php($filtered = request()->filled('q') || request()->filled('group'))
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="tm-title"><h4>Permissions</h4><span class="text-muted">{{ $permissions->total() }} {{ Str::plural('permission', $permissions->total()) }}{{ $filtered ? ' match' : '' }}</span></div>
    @can('create-permissions')<a href="{{ route('permissions.create') }}" class="btn btn-primary"><i class="ri-add-line"></i> New permission</a>@endcan
  </div>
  <p class="tm-lead">The building blocks of roles. The app checks these names in code, so add or rename one only together with a developer; to change who can do what, edit a @can('list-roles')<a href="{{ route('roles.index') }}">role</a>@else role @endcan instead.</p>

  @include('admin.includes.flash')

  <div class="card tl-card"><div class="card-body p-0">
    <form class="tl-search-bar" method="get" action="{{ route('permissions.index') }}">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search permission name" aria-label="Search permission name">@if(request()->filled('q'))<a href="{{ route('permissions.index', array_filter(['group' => request('group')])) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
      <select class="form-select tm-filter-select" name="group" aria-label="Filter by group" onchange="this.form.submit()">
        <option value="">All groups</option>
        @foreach(\App\Support\PermissionGroups::sectionOrder() as $section)
          <option value="{{ $section }}" @selected(request('group') === $section)>{{ $section }}</option>
        @endforeach
      </select>
      <button type="submit" class="visually-hidden">Search</button>
      @if($filtered)<a href="{{ route('permissions.index') }}" class="btn btn-link text-danger px-1">Clear filters</a>@endif
    </form>

    <div class="table-responsive"><table class="table table-hover align-middle tl-table tm-table mb-0">
      <thead><tr><th>Permission</th><th>Group</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @forelse($permissions as $permission)
        <tr>
          <td><span class="tm-strong d-block">{{ \App\Support\PermissionGroups::label($permission->name) }}</span><span class="tm-mono">{{ $permission->name }}</span></td>
          <td>@if($permission->group_name)<span class="badge bg-light">{{ $permission->group_name }}</span>@else<span class="tm-muted">—</span>@endif</td>
          <td class="text-end text-nowrap">
            <div class="tm-actions">
              @can('edit-permissions')<a href="{{ route('permissions.edit', $permission->id) }}" class="btn btn-sm btn-outline-secondary" aria-label="Edit {{ $permission->name }}"><i class="ri-pencil-line"></i> Edit</a>@endcan
              @can('delete-permissions')
                <form action="{{ route('permissions.destroy', $permission->id) }}" method="post" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Delete the permission “'.$permission->name.'”? Every role loses it, and screens that check it will be closed to everyone except the superadmin.') }})">
                  {{ method_field('DELETE') }}
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $permission->name }}"><i class="ri-delete-bin-line"></i></button>
                </form>
              @endcan
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="3" class="tm-empty"><i class="ri-key-2-line"></i>
          @if($filtered)<strong>No permissions match these filters</strong><a href="{{ route('permissions.index') }}">Clear filters</a>
          @else<strong>No permissions yet</strong>Permissions are usually added by the app's setup.@endif
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $permissions->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>
@include('admin.includes.footer')
