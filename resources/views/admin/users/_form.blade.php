{{-- Shared by users/create and users/edit. $user is null when adding a person. --}}
@php
  $editing = (bool) $user;
  $currentRole = old('role', $editing ? $user->roles->pluck('name')->first() : null);
  $initials = collect(preg_split('/\s+/', trim((string) ($user->name ?? ''))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
@endphp
<form action="{{ $action }}" method="post" class="tm-form">
  @csrf
  @if($editing){{ method_field('PUT') }}@endif
  <div class="row g-4">
    <div class="col-xl-8">

      <div class="card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-user-line"></i></span><div><h5>Account</h5><p>Who this person is and how they sign in.</p></div></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="user-name" class="fw-semibold">Name</label>
            <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" id="user-name" value="{{ old('name', $user->name ?? '') }}" autocomplete="off" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Shown in the admin, on trip teams and in the activity history.</div>@enderror
          </div>
          <div class="col-md-6">
            <label for="user-email" class="fw-semibold">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" id="user-email" value="{{ old('email', $user->email ?? '') }}" autocomplete="off" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Used to sign in.</div>@enderror
          </div>
          <div class="col-md-6">
            <label for="user-phone" class="fw-semibold">Phone <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" id="user-phone" value="{{ old('phone', $user->phone ?? '') }}" maxlength="50">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Shown to the rest of a trip's team.</div>@enderror
          </div>
          <div class="col-md-6">
            <label for="user-password" class="fw-semibold">{{ $editing ? 'New password' : 'Password' }}</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" id="user-password" autocomplete="new-password" @unless($editing) required @endunless>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">{{ $editing ? 'Leave empty to keep the current password.' : 'At least 8 characters. Share it with the person privately.' }}</div>@enderror
          </div>
        </div>
      </div></div>

      <div class="card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-shield-user-line"></i></span><div><h5>Role</h5><p>One role per person. It decides what they can see and change.@can('list-roles') <a href="{{ route('roles.index') }}">Manage roles</a>@endcan</p></div></div>
        <div class="tm-choice-list" role="radiogroup" aria-label="Role">
          @foreach($roles as $role)
            <label class="tm-choice" for="role-{{ $role->id }}">
              <input class="form-check-input" id="role-{{ $role->id }}" type="radio" name="role" value="{{ $role->name }}" @checked($currentRole === $role->name)>
              <div><strong>{{ $role->name }}</strong>@if($hint = \App\Support\PermissionGroups::ROLE_HINTS[$role->name] ?? null)<span>{{ $hint }}</span>@endif</div>
            </label>
          @endforeach
        </div>
        @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div></div>

    </div>

    <div class="col-xl-4">
      <div class="card tm-side-card"><div class="card-body">
        @if($editing)
          <div class="tm-summary">
            <span class="tm-avatar tm-avatar-lg tm-av-{{ crc32((string) $user->email) % 6 }}" aria-hidden="true">{{ $initials }}</span>
            <div><span class="tm-name">{{ $user->name }}</span><span class="d-block text-muted text-break">{{ $user->email }}</span></div>
          </div>
          <ul class="tm-facts">
            <li><span>Role</span><span>{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</span></li>
            <li><span>Added</span><span>{{ $user->created_at?->format('M j, Y') ?? '—' }}</span></li>
            <li><span>Last updated</span><span>{{ $user->updated_at?->format('M j, Y') ?? '—' }}</span></li>
          </ul>
        @else
          <h5 class="fs-6 fw-semibold mb-1">New staff account</h5>
          <p class="text-muted mb-0">The person can sign in as soon as you save. Changes to their role apply immediately.</p>
        @endif
        <div class="d-grid gap-2 mt-3">
          <button class="btn btn-primary btn-lg"><i class="ri-save-line me-1"></i>{{ $editing ? 'Save changes' : 'Create user' }}</button>
          <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
        </div>
      </div></div>
    </div>
  </div>
</form>
