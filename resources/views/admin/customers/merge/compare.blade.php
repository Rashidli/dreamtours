@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Merge customers</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customer-merge.index') }}">Duplicates</a></li>
                                <li class="breadcrumb-item active">Merge</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
@include('admin.includes.validation_errors')
@php
  $show = function ($customer, $field) {
      return match ($field) {
          'customer_status_id' => $customer->status?->name,
          'assigned_user_id' => $customer->assignedUser?->name,
          'source' => \App\Models\Customer::SOURCES[$customer->source] ?? $customer->source,
          'date_of_birth' => auth()->user()->can('view-customer-birthdays') ? $customer->date_of_birth?->format('Y-m-d') : ($customer->date_of_birth ? 'hidden' : null),
          default => $customer->{$field},
      };
  };
  // Safety default: a status that blocks promotions (Do Not Contact) is kept whichever record has it.
  $statusFromOther = $other->status?->blocks_marketing && !$keep->status?->blocks_marketing;
@endphp
<form method="post" action="{{ route('customer-merge.merge') }}">@csrf
<input type="hidden" name="keep" value="{{ $keep->id }}"><input type="hidden" name="other" value="{{ $other->id }}">
<div class="card tl-card"><div class="card-body p-0">
  <div class="cs-labels-head d-flex flex-wrap gap-3 justify-content-between align-items-start">
    <div class="tf-head mb-0"><span class="tf-icon"><i class="ri-git-merge-line"></i></span><div><h5>Choose the value to keep for each field</h5><p><b>{{ $other->full_name }}</b>'s bookings, requests, tags and notes move to <b>{{ $keep->full_name }}</b>; {{ $other->full_name }} is then archived as “merged”, never erased.</p></div></div>
    <a class="btn btn-light text-nowrap" href="{{ route('customer-merge.compare', ['keep' => $other->id, 'other' => $keep->id]) }}"><i class="ri-arrow-left-right-line"></i> Swap which record stays</a>
  </div>
  <div class="table-responsive"><table class="table align-middle tl-table cs-merge-table mb-0">
    <thead><tr><th style="width:18%">Field</th>
      <th><span class="cs-merge-role cs-merge-keep">Stays</span> <a href="{{ route('customers.show', $keep) }}" target="_blank" class="text-body">{{ $keep->full_name }}</a> <span class="text-muted fw-normal">#{{ $keep->id }}</span><span class="cs-sub fw-normal">{{ $keep->bookings_count }} bookings · {{ $keep->visa_enquiries_count }} visa · since {{ $keep->created_at?->format('M j, Y') }}</span></th>
      <th><span class="cs-merge-role cs-merge-other">Merged in</span> <a href="{{ route('customers.show', $other) }}" target="_blank" class="text-body">{{ $other->full_name }}</a> <span class="text-muted fw-normal">#{{ $other->id }}</span><span class="cs-sub fw-normal">{{ $other->bookings_count }} bookings · {{ $other->visa_enquiries_count }} visa · since {{ $other->created_at?->format('M j, Y') }}</span></th>
    </tr></thead>
    <tbody>
    @foreach($fields as $field => $label)
      @php
        [$mine, $theirs] = [$show($keep, $field), $show($other, $field)];
        $default = $field === 'customer_status_id' && $statusFromOther ? 'other' : (blank($mine) && filled($theirs) ? 'other' : 'keep');
      @endphp
      <tr class="{{ $mine == $theirs ? 'cs-merge-same' : '' }}">
        <th class="fw-normal text-muted">{{ $label }}</th>
        @foreach(['keep' => $mine, 'other' => $theirs] as $side => $value)
          <td>@if($mine == $theirs)<span class="text-muted">{{ $value ?? '—' }}</span>@else<label class="cs-choice"><input class="form-check-input" type="radio" name="choice[{{ $field }}]" value="{{ $side }}" @checked(old("choice.$field", $default) === $side) @disabled($field === 'date_of_birth' && !auth()->user()->can('view-customer-birthdays'))><span class="{{ blank($value) ? 'text-muted' : '' }}">{{ $value ?? '— (empty)' }}</span></label>@endif</td>
        @endforeach
      </tr>
    @endforeach
      <tr><th class="fw-normal text-muted">Tags</th><td colspan="2">{{ $keep->tags->merge($other->tags)->unique('id')->pluck('name')->implode(', ') ?: '—' }} <span class="text-muted">(combined)</span></td></tr>
      <tr><th class="fw-normal text-muted">Marketing consent</th><td colspan="2">{{ $keep->marketing_opt_in || $other->marketing_opt_in ? 'Kept — the earliest consent record is used' : 'None' }}</td></tr>
    </tbody>
  </table></div>
  <div class="cs-merge-foot">
    <p class="text-muted mb-3"><i class="ri-information-line me-1"></i>Contact details you don't keep (a second email or phone) are copied into the notes, so nothing is lost.@if($statusFromOther) <b>“{{ $other->status->name }}” is preselected so promotions stay blocked.</b>@endif</p>
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
      <div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="merge-confirm" name="confirm" value="1"><label class="form-check-label fw-semibold" for="merge-confirm">I have checked these are the same person.</label></div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-light" href="{{ route('customer-merge.index') }}">Cancel</a>
        <button class="btn btn-danger"><i class="ri-git-merge-line"></i> Merge into {{ $keep->full_name }}</button>
      </div>
    </div>
  </div>
</div></div>
</form>
</div></div></div>@include('admin.includes.footer')
