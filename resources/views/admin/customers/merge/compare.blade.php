@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

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
<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <p class="mb-0">For each field choose the value to keep. <b>{{ $other->full_name }}</b>'s bookings, requests, tags and notes move to <b>{{ $keep->full_name }}</b>; {{ $other->full_name }} is then archived as “merged”, never erased.</p>
    <a class="btn btn-sm btn-light text-nowrap" href="{{ route('customer-merge.compare', ['keep' => $other->id, 'other' => $keep->id]) }}"><i class="ri-arrow-left-right-line"></i> Swap which record stays</a>
  </div>
  <div class="table-responsive"><table class="table crm-table">
    <thead><tr><th style="width:20%"></th>
      <th>Stays: <a href="{{ route('customers.show', $keep) }}" target="_blank">{{ $keep->full_name }}</a> <span class="text-muted">#{{ $keep->id }}</span><br><span class="text-muted fw-normal">{{ $keep->bookings_count }} bookings · {{ $keep->visa_enquiries_count }} visa · since {{ $keep->created_at?->format('M j, Y') }}</span></th>
      <th>Merged in: <a href="{{ route('customers.show', $other) }}" target="_blank">{{ $other->full_name }}</a> <span class="text-muted">#{{ $other->id }}</span><br><span class="text-muted fw-normal">{{ $other->bookings_count }} bookings · {{ $other->visa_enquiries_count }} visa · since {{ $other->created_at?->format('M j, Y') }}</span></th>
    </tr></thead>
    <tbody>
    @foreach($fields as $field => $label)
      @php([$mine, $theirs] = [$show($keep, $field), $show($other, $field)])
      @php($default = $field === 'customer_status_id' && $statusFromOther ? 'other' : (blank($mine) && filled($theirs) ? 'other' : 'keep'))
      <tr class="{{ $mine == $theirs ? 'text-muted' : '' }}">
        <th class="fw-normal">{{ $label }}</th>
        @foreach(['keep' => $mine, 'other' => $theirs] as $side => $value)
          <td>@if($mine == $theirs){{ $value ?? '—' }}@else<label class="mb-0"><input type="radio" name="choice[{{ $field }}]" value="{{ $side }}" @checked(old("choice.$field", $default) === $side) @disabled($field === 'date_of_birth' && !auth()->user()->can('view-customer-birthdays'))> {{ $value ?? '— (empty)' }}</label>@endif</td>
        @endforeach
      </tr>
    @endforeach
      <tr><th class="fw-normal">Tags</th><td colspan="2">{{ $keep->tags->merge($other->tags)->unique('id')->pluck('name')->implode(', ') ?: '—' }} <span class="text-muted">(combined)</span></td></tr>
      <tr><th class="fw-normal">Marketing consent</th><td colspan="2">{{ $keep->marketing_opt_in || $other->marketing_opt_in ? 'Kept — the earliest consent record is used' : 'None' }}</td></tr>
    </tbody>
  </table></div>
  <p class="text-muted">Contact details you don't keep (a second email or phone) are copied into the notes, so nothing is lost.@if($statusFromOther) <b>“{{ $other->status->name }}” is preselected so promotions stay blocked.</b>@endif</p>
  <label class="d-block mb-3"><input type="checkbox" name="confirm" value="1"> I have checked these are the same person.</label>
  <button class="btn btn-danger"><i class="ri-git-merge-line align-middle me-1"></i> Merge into {{ $keep->full_name }}</button>
  <a class="btn btn-light" href="{{ route('customer-merge.index') }}">Cancel</a>
</div></div>
</form>
</div></div></div>@include('admin.includes.footer')
