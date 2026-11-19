@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $tones = ['new' => 'warning', 'contacted' => 'info', 'in_progress' => 'primary', 'closed' => 'secondary'];
  $tone = $tones[$enquiry->status] ?? 'secondary';
  $tel = preg_replace('/[^\d+]/', '', (string) $enquiry->phone);
  $wa = preg_replace('/\D+/', '', (string) $enquiry->phone);
  $assistance = $enquiry->assistanceLabels();
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('visa-enquiries.index') }}" class="cat-back" title="Back to visa enquiries" aria-label="Back to visa enquiries"><i class="ri-arrow-left-line"></i></a>
      <h4>{{ $enquiry->full_name }}</h4>
      <span class="badge bg-{{ $tone }} bg-opacity-10 text-{{ $tone }}">{{ \App\Models\VisaEnquiry::STATUSES[$enquiry->status] ?? $enquiry->status }}</span>
      <span class="cat-count">{{ $enquiry->reference }}</span>
    </div>
  </div>

  @include('admin.includes.flash')
  @include('admin.includes.validation_errors')

  <div class="row">
    <div class="col-lg-8">
      <div class="card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-user-line"></i></span><div class="flex-grow-1"><h5>Contact</h5><p>Received {{ $enquiry->created_at->format('D, M j, Y g:i A') }} ({{ $enquiry->created_at->diffForHumans() }}).</p></div></div>
        <div class="cat-quick mb-3">
          @if($enquiry->phone)<a class="btn btn-light" href="tel:{{ $tel }}"><i class="ri-phone-line"></i> Call</a><a class="btn btn-light" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line cat-wa"></i> WhatsApp</a>@endif
          @if($enquiry->email)<a class="btn btn-light" href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Your visa enquiry '.$enquiry->reference) }}"><i class="ri-mail-line"></i> Email</a>@endif
        </div>
        <dl class="cat-dl">
          <dt>Email</dt><dd>@if($enquiry->email)<a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a>@else—@endif</dd>
          <dt>Phone / WhatsApp</dt><dd>@if($enquiry->phone)<a href="tel:{{ $tel }}">{{ $enquiry->phone }}</a> · <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">WhatsApp</a>@else—@endif</dd>
        </dl>
      </div></div>

      <div class="card"><div class="card-body">
        <div class="tf-head"><span class="tf-icon"><i class="ri-passport-line"></i></span><div><h5>Trip & visa</h5><p>What they asked for on the Visa Support form.</p></div></div>
        <dl class="cat-dl">
          <dt>Destination country</dt><dd>{{ $enquiry->country ?: '—' }}</dd>
          <dt>Passport country</dt><dd>{{ $enquiry->passport_country ?: '—' }}</dd>
          <dt>Assistance required</dt><dd>@if($assistance)<div class="cat-chips">@foreach($assistance as $help)<span class="badge bg-light text-body">{{ $help }}</span>@endforeach</div>@else—@endif</dd>
          <dt>Applicants</dt><dd>{{ $enquiry->applicants ?: '—' }}</dd>
          <dt>Travel date</dt><dd>{{ $enquiry->travel_date?->format('M j, Y') ?: '—' }}</dd>
          <dt>Question / message</dt><dd>@if($enquiry->message)<div class="cat-message">{!! nl2br(e($enquiry->message)) !!}</div>@else—@endif</dd>
        </dl>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="card"><div class="card-body cat-form">
        <h5 class="cat-side-title">Follow-up</h5>
        @can('edit-visa-enquiries')
          <form method="post" action="{{ route('visa-enquiries.update', $enquiry) }}">@csrf @method('PUT')
            <label class="fw-semibold" for="ve-status">Status</label>
            <select id="ve-status" class="form-select mb-3" name="status">@foreach(\App\Models\VisaEnquiry::STATUSES as $key => $statusLabel)<option value="{{ $key }}" @selected($enquiry->status === $key)>{{ $statusLabel }}</option>@endforeach</select>
            <label class="fw-semibold" for="ve-notes">Internal notes <span class="text-muted fw-normal">(staff only)</span></label>
            <textarea id="ve-notes" class="form-control" rows="7" name="internal_notes" placeholder="Calls made, documents received, next step…">{{ old('internal_notes', $enquiry->internal_notes) }}</textarea>
            <div class="form-text mb-3">Never shown to the customer.</div>
            <div class="d-grid"><button class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> Save follow-up</button></div>
          </form>
        @else
          <dl class="cat-dl">
            <dt>Status</dt><dd>{{ \App\Models\VisaEnquiry::STATUSES[$enquiry->status] ?? $enquiry->status }}</dd>
            <dt>Internal notes</dt><dd>{!! $enquiry->internal_notes ? nl2br(e($enquiry->internal_notes)) : '—' !!}</dd>
          </dl>
        @endcan
      </div></div>

      @can('delete-visa-enquiries')
        <form method="post" action="{{ route('visa-enquiries.destroy', $enquiry) }}" class="text-center">@csrf @method('DELETE')<button class="btn btn-link text-danger" onclick="return confirm('Delete enquiry {{ $enquiry->reference }}? This cannot be undone.')"><i class="ri-delete-bin-line"></i> Delete this enquiry</button></form>
      @endcan
    </div>
  </div>

</div></div></div>
@include('admin.includes.footer')
