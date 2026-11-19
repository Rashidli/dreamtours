@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-team.css') }}?v={{ @filemtime(public_path('assets/css/admin-team.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box d-flex flex-wrap align-items-center gap-2">
    <a href="{{ route('permissions.index') }}" class="tm-back" title="Back to permissions" aria-label="Back to permissions"><i class="ri-arrow-left-line"></i></a>
    <div class="tm-title"><h4>New permission</h4></div>
  </div>
  

  @include('admin.permissions._form', ['action' => route('permissions.store'), 'permission' => null])

</div></div></div>
@include('admin.includes.footer')
