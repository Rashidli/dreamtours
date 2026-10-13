@include('admin.includes.header')
@php($locales = config('translatable.locales'))
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit text</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('words.index') }}">Site copy</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @include('admin.includes.validation_errors')

            <form method="post" action="{{ route('words.update', $word) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Text</h4>

                                @include('admin.includes.lang_tabs', ['id' => 'word'])

                                <div class="tab-content pt-3">
                                    @foreach($locales as $lang)
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="word-{{ $lang }}" role="tabpanel">
                                            <label class="form-label">Text ({{ strtoupper($lang) }}) <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error("$lang.title") is-invalid @enderror" rows="3"
                                                      name="{{ $lang }}[title]">{{ old("$lang.title", $word->translate($lang)?->title) }}</textarea>
                                            @error("$lang.title") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    @endforeach
                                </div>

                                <small class="text-muted">
                                    This text is swapped in place on the site; its position does not change.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ri-save-line align-middle me-1"></i> Save
                                    </button>
                                    <a href="{{ route('words.index') }}" class="btn btn-light">
                                        <i class="ri-arrow-left-line align-middle me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@include('admin.includes.footer')
