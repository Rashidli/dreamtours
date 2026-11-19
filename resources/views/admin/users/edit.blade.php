@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center gap-2">
    <a href="{{ route('users.index') }}" class="tm-back" title="Back to users" aria-label="Back to users"><i class="ri-arrow-left-line"></i></a>
    <div class="tm-title"><h4>{{ $user->name }}</h4><span class="text-muted">Edit user</span></div>
  </div>

  @include('admin.includes.flash')

  @include('admin.users._form', ['action' => route('users.update', $user->id), 'user' => $user])

</div></div></div>
@include('admin.includes.footer')
