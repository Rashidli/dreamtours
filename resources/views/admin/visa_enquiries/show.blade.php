@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Visa enquiry {{ $enquiry->reference }}</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('visa-enquiries.index') }}">Visa enquiries</a></li><li class="breadcrumb-item active">{{ $enquiry->reference }}</li></ol></div>
  </div></div></div>
  @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
  @include('admin.includes.validation_errors')
  <div class="row">
    <div class="col-lg-8"><div class="card"><div class="card-body">
      <div class="d-flex justify-content-between"><div><h4 class="mb-1">{{ $enquiry->full_name }}</h4><p class="text-muted">Received {{ $enquiry->created_at->format('D, M j, Y g:i A') }}</p></div><a class="btn btn-light" href="{{ route('visa-enquiries.index') }}"><i class="ri-arrow-left-line"></i> Back</a></div>
      <table class="table table-sm mb-0"><tbody>
        <tr><th style="width:35%">Email</th><td><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></td></tr>
        <tr><th>Phone / WhatsApp</th><td><a href="tel:{{ preg_replace('/[^\d+]/', '', $enquiry->phone) }}">{{ $enquiry->phone }}</a> · <a href="https://wa.me/{{ preg_replace('/\D+/', '', $enquiry->phone) }}" target="_blank" rel="noopener">WhatsApp</a></td></tr>
        <tr><th>Destination country</th><td>{{ $enquiry->country }}</td></tr>
        <tr><th>Passport country</th><td>{{ $enquiry->passport_country ?: '—' }}</td></tr>
        <tr><th>Assistance required</th><td>{{ implode(', ', $enquiry->assistanceLabels()) ?: '—' }}</td></tr>
        <tr><th>Applicants</th><td>{{ $enquiry->applicants }}</td></tr>
        <tr><th>Travel date</th><td>{{ $enquiry->travel_date?->format('M j, Y') ?: '—' }}</td></tr>
        <tr><th>Question / message</th><td>{!! $enquiry->message ? nl2br(e($enquiry->message)) : '—' !!}</td></tr>
      </tbody></table>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body">
      <h5>Follow-up</h5>
      @can('edit-visa-enquiries')
        <form method="post" action="{{ route('visa-enquiries.update', $enquiry) }}">@csrf @method('PUT')
          <label class="form-label">Status</label>
          <select class="form-select mb-3" name="status">@foreach(\App\Models\VisaEnquiry::STATUSES as $key => $label)<option value="{{ $key }}" @selected($enquiry->status === $key)>{{ $label }}</option>@endforeach</select>
          <label class="form-label">Internal notes <small class="text-muted">(staff only)</small></label>
          <textarea class="form-control mb-3" rows="6" name="internal_notes">{{ old('internal_notes', $enquiry->internal_notes) }}</textarea>
          <button class="btn btn-primary w-100"><i class="ri-save-line"></i> Save</button>
        </form>
      @else
        <p class="mb-1"><strong>Status:</strong> {{ \App\Models\VisaEnquiry::STATUSES[$enquiry->status] ?? $enquiry->status }}</p>
        <p class="mb-0">{!! nl2br(e($enquiry->internal_notes)) !!}</p>
      @endcan
    </div></div></div>
  </div>
</div></div></div>
@include('admin.includes.footer')
