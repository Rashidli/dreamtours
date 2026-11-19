{{-- Page title + step indicator for the import pages. $step: 1 upload, 2 map, 3 preview, 4 done. --}}
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-customers.css') }}?v={{ @filemtime(public_path('assets/css/admin-customers.css')) }}">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Import customers</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item active">Import</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<ol class="cs-steps" aria-label="Import steps">
    @foreach(['Upload file', 'Match columns', 'Check rows', 'Done'] as $i => $label)
        <li class="cs-step {{ $i + 1 === $step ? 'is-current' : ($i + 1 < $step ? 'is-done' : '') }}" @if($i + 1 === $step) aria-current="step" @endif><span class="cs-step-dot">@if($i + 1 < $step)<i class="ri-check-line"></i>@else{{ $i + 1 }}@endif</span><span class="cs-step-label">{{ $label }}</span></li>
    @endforeach
</ol>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
