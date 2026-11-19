@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center gap-2">
    <a href="{{ route('roles.index') }}" class="tm-back" title="Back to roles" aria-label="Back to roles"><i class="ri-arrow-left-line"></i></a>
    <div class="tm-title"><h4>New role</h4></div>
  </div>
@include('admin.includes.flash')
@include('admin.roles._form', ['action' => route('roles.store'), 'role' => null, 'assigned' => []])
</div></div></div>@include('admin.includes.footer')
