@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-leads.css') }}?v={{ @filemtime(public_path('assets/css/admin-leads.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <a class="btn btn-light ld-back" href="{{ route('leads.index') }}" title="Back to leads" aria-label="Back to leads"><i class="ri-arrow-left-line"></i></a>
                            <h4 class="mb-0">New lead</h4>
                        </div>
                        <div class="page-title-right d-none d-md-block">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                                <li class="breadcrumb-item active">New</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<form method="post" action="{{ route('leads.store') }}" class="tf-form">@csrf
@include('admin.includes.validation_errors')
<div class="row">
  <div class="col-lg-8">
    <div class="card ld-card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-user-add-line"></i></span><div><h5>Lead details</h5><p>For requests that didn't come through the website — a phone call, a walk-in, a message.</p></div></div>
      @include('admin.leads._fields')
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card ld-card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-user-star-line"></i></span><div><h5>Owner</h5><p>Who follows up on this lead.</p></div></div>
      <label class="form-label fw-semibold" for="lf-assigned">Assign to</label>
      <select class="form-select" id="lf-assigned" name="assigned_user_id"><option value="">Nobody yet</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('assigned_user_id', auth()->id()) == $user->id)>{{ $user->name }}{{ $user->id === auth()->id() ? ' (me)' : '' }}</option>@endforeach</select>
      <div class="form-text">The lead starts in “New”. Assigning it moves it to “Assigned”.</div>
      <div class="tf-subtle d-flex gap-2 mt-3"><i class="ri-information-line text-muted"></i><div class="text-muted">If the person is already a customer (same email, or same phone without email) the lead is linked to them. Otherwise a new customer is created.</div></div>
    </div></div>
  </div>
</div>
<div class="ld-form-actions">
  <a class="btn btn-light" href="{{ route('leads.index') }}">Cancel</a>
  <button class="btn btn-primary"><i class="ri-save-line"></i> Create lead</button>
</div>
</form>
</div></div></div>@include('admin.includes.footer')
