@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $customer->exists ? 'Edit '.$customer->full_name : 'New customer' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item active">{{ $customer->exists ? 'Edit' : 'New' }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<form class="tf-form" method="post" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">@csrf @if($customer->exists) @method('PUT') @endif
@include('admin.includes.validation_errors')
<div class="row gx-lg-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-user-3-line"></i></span><div><h5>Contact details</h5><p>How to reach this customer. An email or a phone number is required.</p></div></div>
      <div class="row g-3">
        <div class="col-md-4"><label class="fw-semibold tf-req" for="cf-first">First name</label><input class="form-control @error('first_name') is-invalid @enderror" id="cf-first" name="first_name" value="{{ old('first_name', $customer->first_name) }}" required maxlength="100">@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="fw-semibold" for="cf-last">Last name</label><input class="form-control @error('last_name') is-invalid @enderror" id="cf-last" name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="100"></div>
        <div class="col-md-4"><label class="fw-semibold" for="cf-preferred">Preferred name</label><input class="form-control" id="cf-preferred" name="preferred_name" value="{{ old('preferred_name', $customer->preferred_name) }}" maxlength="100" placeholder="What they like to be called"></div>
        <div class="col-md-4"><label class="fw-semibold" for="cf-email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="cf-email" type="email" name="email" value="{{ old('email', $customer->email) }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="fw-semibold" for="cf-phone">Phone</label><input class="form-control @error('phone') is-invalid @enderror" id="cf-phone" type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="50">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Email or phone is required.</div>@enderror</div>
        <div class="col-md-4"><label class="fw-semibold" for="cf-whatsapp">WhatsApp</label><input class="form-control" id="cf-whatsapp" type="tel" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}" maxlength="50"><div class="form-text">With country code, e.g. +1 416…</div></div>
      </div>
    </div></div>

    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-map-pin-line"></i></span><div><h5>About the customer</h5><p>Optional details that help staff serve them.</p></div></div>
      <div class="row g-3">
        @can('view-customer-birthdays')<div class="col-sm-6 col-md-3"><label class="fw-semibold" for="cf-dob">Date of birth</label><input class="form-control" id="cf-dob" type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->format('Y-m-d')) }}"><div class="form-text">Optional.</div></div>@endcan
        <div class="col-sm-6 col-md-3"><label class="fw-semibold" for="cf-city">City</label><input class="form-control" id="cf-city" name="city" value="{{ old('city', $customer->city) }}" maxlength="100"></div>
        <div class="col-sm-6 col-md-3"><label class="fw-semibold" for="cf-language">Language</label><input class="form-control" id="cf-language" name="language" value="{{ old('language', $customer->language) }}" maxlength="30" placeholder="e.g. English"></div>
        <div class="col-sm-6 col-md-3"><label class="fw-semibold" for="cf-source">Source</label><select class="form-select" id="cf-source" name="source"><option value="">—</option>@foreach(\App\Models\Customer::SOURCES as $value => $label)<option value="{{ $value }}" @selected(old('source', $customer->source) === $value)>{{ $label }}</option>@endforeach</select></div>
      </div>
    </div></div>

    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-sticky-note-line"></i></span><div><h5>Notes</h5><p>Staff only — never shown to the customer.</p></div></div>
      <label class="visually-hidden" for="cf-notes">Notes</label>
      <textarea class="form-control" id="cf-notes" name="notes" rows="5" placeholder="Preferences, special needs, things to remember…">{{ old('notes', $customer->notes) }}</textarea>
    </div></div>
  </div>

  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-shield-user-line"></i></span><div><h5>Status</h5><p>Where this customer stands and who looks after them.</p></div></div>
      <div class="mb-3"><label class="fw-semibold" for="cf-status">Status</label><select class="form-select" id="cf-status" name="customer_status_id"><option value="">—</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(old('customer_status_id', $customer->customer_status_id) == $status->id)>{{ $status->name }}{{ $status->blocks_marketing ? ' (blocks promotions)' : '' }}{{ $status->archived_at ? ' (archived)' : '' }}</option>@endforeach</select></div>
      <div><label class="fw-semibold" for="cf-assigned">Assigned to</label><select class="form-select" id="cf-assigned" name="assigned_user_id"><option value="">Nobody</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('assigned_user_id', $customer->assigned_user_id) == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
    </div></div>

    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-mail-send-line"></i></span><div><h5>Marketing</h5><p>Promotional emails and messages.</p></div></div>
      <div class="form-check form-switch tf-switch"><input class="form-check-input" type="checkbox" role="switch" id="cf-marketing" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in', $customer->marketing_opt_in))><label class="form-check-label" for="cf-marketing">Agreed to receive news and promotions</label></div>
      <div class="tf-subtle mt-2 cs-hint"><i class="ri-information-line"></i><span>Only switch this on when the customer has agreed. The date and who recorded it are saved. A “Do Not Contact” status blocks promotions regardless.</span></div>
    </div></div>

    <div class="card"><div class="card-body">
      <div class="tf-head"><span class="tf-icon"><i class="ri-price-tag-3-line"></i></span><div><h5>Tags</h5><p>Pick as many as apply.</p></div></div>
      @php($chosen = collect(old('tag_ids', $customer->tags->pluck('id')->all()))->map(fn ($id) => (int) $id))
      <div class="cs-pills">
      @forelse($tags as $tag)<input class="btn-check" type="checkbox" id="cf-tag-{{ $tag->id }}" name="tag_ids[]" value="{{ $tag->id }}" @checked($chosen->contains($tag->id))><label class="cs-pill" for="cf-tag-{{ $tag->id }}"><i class="ri-check-line"></i>{{ $tag->name }}{{ $tag->archived_at ? ' (archived)' : '' }}</label>@empty<span class="text-muted">No tags yet.@can('manage-customer-labels') <a href="{{ route('customer-labels.index') }}">Create tags</a>.@endcan</span>@endforelse
      </div>
    </div></div>
  </div>
</div>

<div class="cs-savebar">
  <a class="btn btn-light" href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}">Cancel</a>
  <button class="btn btn-primary"><i class="ri-save-line"></i> {{ $customer->exists ? 'Save changes' : 'Create customer' }}</button>
</div>
</form>
</div></div></div>@include('admin.includes.footer')
