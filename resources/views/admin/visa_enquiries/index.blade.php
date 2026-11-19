@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-catalogue.css') }}?v={{ @filemtime(public_path('assets/css/admin-catalogue.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
@php
  $status = (string) request('status', '');
  $filtered = request()->filled('q') || $status !== '';
  $tones = ['new' => 'warning', 'contacted' => 'info', 'in_progress' => 'primary', 'closed' => 'secondary'];
  $tel = fn ($phone) => preg_replace('/[^\d+]/', '', (string) $phone);
  $wa = fn ($phone) => preg_replace('/\D+/', '', (string) $phone);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">

  <div class="page-title-box cat-header">
    <div class="d-flex align-items-baseline gap-2 flex-wrap"><h4>Visa enquiries</h4><span class="cat-count">{{ $enquiries->total() }} {{ Str::plural('enquiry', $enquiries->total()) }}{{ $filtered ? ' match' : '' }}</span></div>
  </div>
  <p class="cat-intro">Sent from the Visa Support page. These are separate from trip bookings and never affect seats. Fees shown on that page are set under <a href="{{ route('domain-content.index', 'visa-services') }}">Visa support pricing</a>.</p>

  @include('admin.includes.flash')

  <div class="card tl-card"><div class="card-body p-0">
    <form method="get" action="{{ route('visa-enquiries.index') }}">
      <div class="tl-tabs-bar">
        <div class="tl-tabs" role="radiogroup" aria-label="Status">@foreach(['' => 'All'] + \App\Models\VisaEnquiry::STATUSES as $value => $tabLabel)<input type="radio" class="btn-check" name="status" id="status-{{ $value ?: 'all' }}" value="{{ $value }}" @checked($status === (string) $value) onchange="this.form.submit()"><label class="tl-tab" for="status-{{ $value ?: 'all' }}">{{ $tabLabel }}@if($value === 'new' && $newCount)<span class="cat-tab-count">{{ $newCount }}</span>@endif</label>@endforeach</div>
      </div>
      <div class="tl-search-bar">
        <label class="tl-search-field"><i class="ri-search-line" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Reference, name, email, phone or country" aria-label="Search enquiries">@if(request()->filled('q'))<a href="{{ route('visa-enquiries.index', $status !== '' ? ['status' => $status] : []) }}" class="tl-search-clear" aria-label="Clear the search"><i class="ri-close-circle-fill"></i></a>@endif</label>
        <button type="submit" class="visually-hidden">Search</button>
      </div>
    </form>

    <div class="table-responsive"><table class="table table-hover align-middle tl-table mb-0">
      <thead><tr><th>Enquiry</th><th>Contact</th><th class="d-none d-md-table-cell">Country</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @forelse($enquiries as $e)
        <tr>
          <td>
            <a href="{{ route('visa-enquiries.show', $e) }}" class="fw-semibold text-body">{{ $e->full_name }}</a>
            <div class="cat-sub">{{ $e->reference }} · {{ $e->created_at->format('M j, Y g:i A') }}</div>
          </td>
          <td>
            <div class="cat-contact">
              @if($e->phone)<a class="btn btn-light" href="tel:{{ $tel($e->phone) }}" title="Call {{ $e->phone }}" aria-label="Call {{ $e->full_name }}"><i class="ri-phone-line"></i></a><a class="btn btn-light cat-wa" href="https://wa.me/{{ $wa($e->phone) }}" target="_blank" rel="noopener" title="WhatsApp {{ $e->phone }}" aria-label="WhatsApp {{ $e->full_name }}"><i class="ri-whatsapp-line"></i></a>@endif
              @if($e->email)<a class="btn btn-light" href="mailto:{{ $e->email }}?subject={{ rawurlencode('Your visa enquiry '.$e->reference) }}" title="Email {{ $e->email }}" aria-label="Email {{ $e->full_name }}"><i class="ri-mail-line"></i></a>@endif
            </div>
          </td>
          <td class="d-none d-md-table-cell">{{ $e->country ?: '—' }}@if($e->applicants)<div class="cat-sub">{{ $e->applicants }} {{ Str::plural('applicant', (int) $e->applicants) }}</div>@endif</td>
          <td><span class="badge bg-{{ $tones[$e->status] ?? 'secondary' }} bg-opacity-10 text-{{ $tones[$e->status] ?? 'secondary' }}">{{ \App\Models\VisaEnquiry::STATUSES[$e->status] ?? $e->status }}</span></td>
          <td class="cat-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#visa-enquiry-{{ $e->id }}">Details</button>
            <a class="btn btn-sm btn-primary" href="{{ route('visa-enquiries.show', $e) }}" title="Open and follow up" aria-label="Open {{ $e->reference }}"><i class="ri-arrow-right-line"></i></a>
            @can('delete-visa-enquiries')<form method="post" action="{{ route('visa-enquiries.destroy', $e) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $e->reference }}" onclick="return confirm('Delete enquiry {{ $e->reference }} from {{ addslashes($e->full_name) }}? This cannot be undone.')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="5">
          <div class="cat-empty">
            <div class="cat-empty-icon"><i class="{{ $filtered ? 'ri-search-line' : 'ri-passport-line' }}"></i></div>
            @if($filtered)
              <h5>No enquiries match</h5><p>Try another search or status.</p><a class="btn btn-light" href="{{ route('visa-enquiries.index') }}">Clear filters</a>
            @else
              <h5>No visa enquiries yet</h5><p>When someone sends the form on the Visa Support page, it shows up here.</p>
            @endif
          </div>
        </td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div class="tl-pager">{{ $enquiries->links('admin.vendor.pagination.bootstrap-5') }}</div>
  </div></div>

</div></div></div>

@foreach($enquiries as $e)
  <div class="modal fade" id="visa-enquiry-{{ $e->id }}" tabindex="-1" aria-labelledby="visa-enquiry-{{ $e->id }}-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
    <div class="modal-header"><div><h5 class="modal-title" id="visa-enquiry-{{ $e->id }}-title">{{ $e->full_name }} <span class="badge bg-{{ $tones[$e->status] ?? 'secondary' }} bg-opacity-10 text-{{ $tones[$e->status] ?? 'secondary' }} align-middle ms-1">{{ \App\Models\VisaEnquiry::STATUSES[$e->status] ?? $e->status }}</span></h5><div class="cat-sub">{{ $e->reference }} · received {{ $e->created_at->format('D, M j, Y g:i A') }}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="cat-quick mb-3">
        @if($e->phone)<a class="btn btn-light" href="tel:{{ $tel($e->phone) }}"><i class="ri-phone-line"></i> Call</a><a class="btn btn-light" href="https://wa.me/{{ $wa($e->phone) }}" target="_blank" rel="noopener"><i class="ri-whatsapp-line cat-wa"></i> WhatsApp</a>@endif
        @if($e->email)<a class="btn btn-light" href="mailto:{{ $e->email }}?subject={{ rawurlencode('Your visa enquiry '.$e->reference) }}"><i class="ri-mail-line"></i> Email</a>@endif
      </div>
      <dl class="cat-dl">
        <dt>Email</dt><dd>@if($e->email)<a href="mailto:{{ $e->email }}">{{ $e->email }}</a>@else—@endif</dd>
        <dt>Phone / WhatsApp</dt><dd>@if($e->phone)<a href="tel:{{ $tel($e->phone) }}">{{ $e->phone }}</a>@else—@endif</dd>
        <dt>Destination country</dt><dd>{{ $e->country ?: '—' }}</dd>
        <dt>Passport country</dt><dd>{{ $e->passport_country ?: '—' }}</dd>
        <dt>Assistance required</dt><dd>@if($e->assistanceLabels())<div class="cat-chips">@foreach($e->assistanceLabels() as $help)<span class="badge bg-light text-body">{{ $help }}</span>@endforeach</div>@else—@endif</dd>
        <dt>Applicants</dt><dd>{{ $e->applicants ?: '—' }}</dd>
        <dt>Travel date</dt><dd>{{ $e->travel_date?->format('M j, Y') ?: '—' }}</dd>
        <dt>Question / message</dt><dd>@if($e->message)<div class="cat-message">{!! nl2br(e($e->message)) !!}</div>@else—@endif</dd>
        @if($e->internal_notes)<dt>Internal notes</dt><dd>{!! nl2br(e($e->internal_notes)) !!}</dd>@endif
      </dl>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><a class="btn btn-primary" href="{{ route('visa-enquiries.show', $e) }}">Open and follow up</a></div>
  </div></div></div>
@endforeach
@include('admin.includes.footer')
