@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

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
<form method="post" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">@csrf @if($customer->exists) @method('PUT') @endif
<div class="card"><div class="card-body">
@include('admin.includes.validation_errors')
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">First name *</label><input class="form-control" name="first_name" value="{{ old('first_name', $customer->first_name) }}" required maxlength="100"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="100"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Preferred name</label><input class="form-control" name="preferred_name" value="{{ old('preferred_name', $customer->preferred_name) }}" maxlength="100" placeholder="What they like to be called"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $customer->email) }}"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="50"><div class="form-text">Email or phone is required.</div></div>
    <div class="col-md-4 mb-3"><label class="form-label">WhatsApp</label><input class="form-control" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}" maxlength="50"></div>
    @can('view-customer-birthdays')<div class="col-md-3 mb-3"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->format('Y-m-d')) }}"><div class="form-text">Optional.</div></div>@endcan
    <div class="col-md-3 mb-3"><label class="form-label">City</label><input class="form-control" name="city" value="{{ old('city', $customer->city) }}" maxlength="100"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Language</label><input class="form-control" name="language" value="{{ old('language', $customer->language) }}" maxlength="30" placeholder="e.g. English"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Source</label><select class="form-select" name="source"><option value="">—</option>@foreach(\App\Models\Customer::SOURCES as $value => $label)<option value="{{ $value }}" @selected(old('source', $customer->source) === $value)>{{ $label }}</option>@endforeach</select></div>
</div>
<hr>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" name="customer_status_id"><option value="">—</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(old('customer_status_id', $customer->customer_status_id) == $status->id)>{{ $status->name }}{{ $status->blocks_marketing ? ' (blocks promotions)' : '' }}{{ $status->archived_at ? ' (archived)' : '' }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Assigned to</label><select class="form-select" name="assigned_user_id"><option value="">Nobody</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('assigned_user_id', $customer->assigned_user_id) == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label d-block">Marketing</label>
        <label><input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in', $customer->marketing_opt_in))> Agreed to receive news and promotions</label>
        <div class="form-text">Only tick this when the customer has agreed. The date and who recorded it are saved. A “Do Not Contact” status blocks promotions regardless.</div>
    </div>
    <div class="col-12 mb-3"><label class="form-label d-block">Tags</label>
        @php($chosen = collect(old('tag_ids', $customer->tags->pluck('id')->all()))->map(fn ($id) => (int) $id))
        @forelse($tags as $tag)<label class="me-3"><input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked($chosen->contains($tag->id))> {{ $tag->name }}{{ $tag->archived_at ? ' (archived)' : '' }}</label>@empty<span class="text-muted small">No tags yet.@can('manage-customer-labels') <a href="{{ route('customer-labels.index') }}">Create tags</a>.@endcan</span>@endforelse
    </div>
    <div class="col-12 mb-3"><label class="form-label">Notes <small class="text-muted">(staff only)</small></label><textarea class="form-control" name="notes" rows="4">{{ old('notes', $customer->notes) }}</textarea></div>
</div>
<button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save</button>
<a class="btn btn-light" href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}">Cancel</a>
</div></div>
</form>
</div></div></div>@include('admin.includes.footer')
