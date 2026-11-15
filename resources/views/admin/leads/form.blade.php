@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">New lead</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                                <li class="breadcrumb-item active">New</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="row justify-content-center"><div class="col-xl-8">
<form method="post" action="{{ route('leads.store') }}">@csrf
<div class="card"><div class="card-body">
  <p class="text-muted">For requests that didn't come through the website — a phone call, a walk-in, a message. If the person is already a customer (same email, or same phone without email) the lead is linked to them.</p>
  @include('admin.includes.validation_errors')
  @include('admin.leads._fields')
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Assign to</label><select class="form-select" name="assigned_user_id"><option value="">Nobody yet</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('assigned_user_id', auth()->id()) == $user->id)>{{ $user->name }}{{ $user->id === auth()->id() ? ' (me)' : '' }}</option>@endforeach</select></div></div>
  <button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Create lead</button>
  <a class="btn btn-light" href="{{ route('leads.index') }}">Cancel</a>
</div></div>
</form>
</div></div>
</div></div></div>@include('admin.includes.footer')
