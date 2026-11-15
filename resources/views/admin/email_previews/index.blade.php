@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Email previews</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Email previews</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h4 class="card-title mb-1">Automatic emails</h4>
            <p class="text-muted small">Each email is shown as it arrives, filled with the latest real booking or request. Nothing is sent from this page.</p>
            <div class="list-group">
                @foreach($emails as $key => $email)
                    @if($email['available'])
                        <a href="{{ route('email-previews.show', $key) }}" target="email-preview" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span>{{ $email['title'] }}</span><span class="badge bg-light text-body">{{ $email['to'] }}</span>
                        </a>
                    @else
                        <span class="list-group-item d-flex justify-content-between align-items-center text-muted" title="No record to fill it with yet">
                            <span>{{ $email['title'] }}</span><span class="badge bg-light text-muted">no sample yet</span>
                        </span>
                    @endif
                @endforeach
            </div>
            <p class="text-muted small mt-3 mb-0">Office alerts go to the <a href="{{ route('settings.index') }}">Notification email</a> setting.</p>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100"><div class="card-body p-0">
            <iframe name="email-preview" title="Email preview" style="width:100%;height:80vh;border:0;border-radius:.25rem;background:#f5f3ef" src="{{ ($first = $emails->filter(fn ($e) => $e['available'])->keys()->first()) ? route('email-previews.show', $first) : 'about:blank' }}"></iframe>
        </div></div>
    </div>
</div>
</div></div></div>@include('admin.includes.footer')
