{{-- Page title + step indicator for the import pages. $step: 1 upload, 2 map, 3 preview, 4 done. --}}
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
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(['Upload file', 'Match columns', 'Check rows', 'Done'] as $i => $label)
        <span class="badge rounded-pill {{ $i + 1 === $step ? 'bg-primary' : ($i + 1 < $step ? 'bg-success' : 'bg-light text-muted') }} px-3 py-2">{{ $i + 1 }}. {{ $label }}</span>
    @endforeach
</div>
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
