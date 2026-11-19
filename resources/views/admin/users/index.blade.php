@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  // Display helpers only: role names for the filter and each person's latest entry in the activity history.
  $roleNames = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');
  $lastSeen = \App\Models\ActivityLog::where('actor_type', 'user')->whereIn('actor_id', $users->pluck('id'))
      ->groupBy('actor_id')->selectRaw('actor_id, max(created_at) as last_at')->pluck('last_at', 'actor_id');
  $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
  $filtered = request()->filled('q') || request()->filled('role');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="tm-title"><h4>Users</h4><span class="text-muted">{{ $users->total() }} {{ Str::plural('person', $users->total()) }}{{ $filtered ? ' match' : '' }}</span></div>
    @can('create-users')<a href="{{ route('users.create') }}" class="btn btn-primary"><i class="ri-user-add-line"></i> Add user</a>@endcan
  </div>
  <p class="tm-lead">Staff accounts that can sign in to this admin. Each person has one role; the role decides what they can see and change.</p>

  @include('admin.includes.flash')

  <div class="card tl-card"><div class="card-body p-0">
    <form method="get" action="{{ route('users.index') }}" class="tl-search-bar">
      <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name or email" aria-label="Search by name or email">@if(request()->filled('q'))<a href="{{ route('users.index', array_filter(['role' => request('role')])) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
      <select class="form-select tm-filter-select" name="role" aria-label="Filter by role" onchange="this.form.submit()">
        <option value="">All roles</option>
        @foreach($roleNames as $roleName)<option value="{{ $roleName }}" @selected(request('role') === $roleName)>{{ $roleName }}</option>@endforeach
      </select>
      <button type="submit" class="visually-hidden">Search</button>
      @if($filtered)<a href="{{ route('users.index') }}" class="btn btn-link text-danger px-1">Clear filters</a>@endif
    </form>

    <div class="table-responsive"><table class="table table-hover align-middle tl-table tm-table mb-0">
      <thead><tr><th>Name</th><th>Role</th><th class="tm-hide-sm">Phone</th><th class="tm-hide-sm">Last activity</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @forelse($users as $user)
        @php
          $isSystem = $user->hasRole('superadmin');
          $isMe = $user->id === auth()->id();
          $seen = $lastSeen[$user->id] ?? null;
        @endphp
        <tr>
          <td>
            <div class="tm-person">
              <span class="tm-avatar tm-av-{{ crc32((string) $user->email) % 6 }}" aria-hidden="true">{{ $initials($user->name) }}</span>
              <div>
                @if(!$isSystem)@can('edit-users')<a href="{{ route('users.edit', $user->id) }}" class="tm-name text-body">{{ $user->name }}</a>@else<span class="tm-name">{{ $user->name }}</span>@endcan
                @else<span class="tm-name">{{ $user->name }}</span>@endif
                <span class="tm-email">{{ $user->email }}</span>
              </div>
            </div>
          </td>
          <td>
            @forelse($user->roles as $role)<span class="badge {{ $role->name === 'superadmin' ? 'bg-dark' : 'bg-light' }} me-1">{{ $role->name }}</span>@empty<span class="badge bg-warning">No role</span>@endforelse
            @if($isMe)<span class="badge bg-info">You</span>@endif
          </td>
          <td class="tm-hide-sm">{!! $user->phone ? e($user->phone) : '<span class="tm-muted">—</span>' !!}</td>
          <td class="tm-hide-sm text-nowrap">
            @if($seen)<span title="{{ \Illuminate\Support\Carbon::parse($seen)->format('M j, Y g:i A') }}">{{ \Illuminate\Support\Carbon::parse($seen)->diffForHumans() }}</span>
            @else<span class="tm-muted">No activity yet</span>@endif
          </td>
          <td class="text-end text-nowrap">
            @if($isSystem)
              <span class="badge bg-light" title="The superadmin account is the recovery backstop, so it cannot be edited or deleted."><i class="ri-lock-line"></i> System account</span>
            @else
              <div class="tm-actions">
                @can('edit-users')<a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-secondary" aria-label="Edit {{ $user->name }}"><i class="ri-pencil-line"></i> Edit</a>@endcan
                @can('delete-users')@unless($isMe)
                  <form action="{{ route('users.destroy', $user->id) }}" method="post" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Delete '.$user->name.'? They will no longer be able to sign in. This cannot be undone.') }})">
                    {{ method_field('DELETE') }}
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $user->name }}"><i class="ri-delete-bin-line"></i></button>
                  </form>
                @endunless @endcan
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="tm-empty">
          <i class="ri-team-line"></i>
          @if($filtered)<strong>No people match these filters</strong><a href="{{ route('users.index') }}">Clear filters</a>
          @else<strong>No users yet</strong>Add the first staff account to give someone access.@endif
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $users->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>
@include('admin.includes.footer')
