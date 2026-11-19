@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $contact->name, 'parent' => ['Contact requests', route('contacts.index')], 'current' => 'Request'])
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-chat-3-line', 'title' => 'Message', 'hint' => 'Sent from the contact form on the website.'])
      <div class="cms-message">{!! nl2br(e($contact->message)) !!}</div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Contact</h5>
      <div class="cms-details-body"><dl>
        <dt>Name</dt><dd>{{ $contact->name }}</dd>
        <dt>Phone</dt><dd><a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></dd>
        <dt>Received</dt><dd>{{ $contact->created_at?->format('D, M j, Y g:i A') }}</dd>
      </dl></div>
      <div class="d-grid gap-2 mt-3">
        @if($contact->phone)<a class="btn btn-primary" href="tel:{{ $contact->phone }}"><i class="ri-phone-line align-middle me-1"></i> Call {{ $contact->phone }}</a>@endif
        <a class="btn btn-light" href="{{ route('contacts.index') }}"><i class="ri-arrow-left-line align-middle me-1"></i> All requests</a>
      </div>
    </div></div>
  </div>
</div>
</div></div></div>
@include('admin.includes.footer')
