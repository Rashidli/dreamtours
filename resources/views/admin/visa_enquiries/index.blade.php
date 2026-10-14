@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
  <div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between">
    <h4 class="mb-sm-0">Visa enquiries</h4>
    <div class="page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item active">Visa enquiries</li></ol></div>
  </div></div></div>
  <div class="card"><div class="card-body">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    <h4 class="mb-1">Visa enquiries @if($newCount)<a href="{{ route('visa-enquiries.index', ['status' => 'new']) }}" class="badge bg-warning text-dark ms-2">{{ $newCount }} new</a>@endif</h4>
    <p class="text-muted mb-0">Sent from the Visa Support page. These are separate from trip bookings and never affect seats.</p>
    <form class="row g-2 my-3">
      <div class="col-md-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Reference, name, email, phone or country"></div>
      <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(\App\Models\VisaEnquiry::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-md-3"><button class="btn btn-outline-primary">Filter</button></div>
    </form>
    <div class="table-responsive"><table class="table align-middle">
      <thead><tr><th>Reference</th><th>Received</th><th>Name</th><th>Country</th><th>Assistance</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($enquiries as $e)
        <tr>
          <td><a href="{{ route('visa-enquiries.show', $e) }}"><strong>{{ $e->reference }}</strong></a></td>
          <td>{{ $e->created_at->format('Y-m-d H:i') }}</td>
          <td>{{ $e->full_name }}<br><small class="text-muted">{{ $e->email }} · {{ $e->phone }}</small></td>
          <td>{{ $e->country }}</td>
          <td><small>{{ implode(', ', $e->assistanceLabels()) ?: '—' }}</small></td>
          <td><span class="badge bg-{{ ['new' => 'warning', 'contacted' => 'info', 'in_progress' => 'primary', 'closed' => 'secondary'][$e->status] ?? 'secondary' }}">{{ \App\Models\VisaEnquiry::STATUSES[$e->status] ?? $e->status }}</span></td>
          <td class="text-nowrap"><a class="btn btn-sm btn-primary" href="{{ route('visa-enquiries.show', $e) }}" title="Open"><i class="ri-eye-line"></i></a>
            @can('delete-visa-enquiries')<form class="d-inline" method="post" action="{{ route('visa-enquiries.destroy', $e) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this enquiry?')"><i class="ri-delete-bin-line"></i></button></form>@endcan</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-muted">No visa enquiries yet.</td></tr>
      @endforelse
      </tbody>
    </table></div>
    {{ $enquiries->links('admin.vendor.pagination.bootstrap-5') }}
  </div></div>
</div></div></div>
@include('admin.includes.footer')
