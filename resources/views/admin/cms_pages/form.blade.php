@include('admin.includes.header')
@php($locales = config('translatable.locales'))
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit SEO</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('cms-pages.index') }}">Pages & SEO</a></li>
                                <li class="breadcrumb-item active">{{ $page->title }}</li>
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

            <form method="post" action="{{ route('cms-pages.update', $page) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Content & SEO</h4>

                                @include('admin.includes.lang_tabs', ['id' => 'page'])

                                <div class="tab-content pt-3">
                                    @foreach($locales as $lang)
                                        @php($t = $page->translate($lang))
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="page-{{ $lang }}" role="tabpanel">
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Title ({{ strtoupper($lang) }}) <span class="text-danger">*</span></label>
                                                    <input class="form-control @error("$lang.title") is-invalid @enderror" type="text"
                                                           name="{{ $lang }}[title]" value="{{ old("$lang.title", $t?->title) }}">
                                                    @error("$lang.title") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Meta title ({{ strtoupper($lang) }})</label>
                                                <input class="form-control @error("$lang.meta_title") is-invalid @enderror" type="text"
                                                       name="{{ $lang }}[meta_title]" value="{{ old("$lang.meta_title", $t?->meta_title) }}">
                                                <small class="text-muted">Falls back to the page title when empty.</small>
                                                @error("$lang.meta_title") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Meta description ({{ strtoupper($lang) }})</label>
                                                <textarea class="form-control @error("$lang.meta_description") is-invalid @enderror" rows="3"
                                                          name="{{ $lang }}[meta_description]">{{ old("$lang.meta_description", $t?->meta_description) }}</textarea>
                                                @error("$lang.meta_description") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Meta keywords ({{ strtoupper($lang) }})</label>
                                                <input class="form-control @error("$lang.meta_keywords") is-invalid @enderror" type="text"
                                                       name="{{ $lang }}[meta_keywords]" value="{{ old("$lang.meta_keywords", $t?->meta_keywords) }}"
                                                       placeholder="comma-separated keywords">
                                                @error("$lang.meta_keywords") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="card-title mb-0">Page sections</h4>
                                    </div>
                                    <ul class="list-group">
                                        @forelse($page->sections as $section)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    {{ $section->title ?: $section->key }}
                                                    <code class="ms-1">{{ $section->key }}</code>
                                                    @unless($section->is_active)<span class="badge bg-danger ms-1">Inactive</span>@endunless
                                                </span>
                                                <a class="btn btn-sm btn-primary" href="{{ route('page-sections.edit', $section) }}" title="Edit">
                                                    <i class="ri-pencil-line"></i>
                                                </a>
                                            </li>
                                        @empty
                                            <li class="list-group-item text-muted">No sections yet.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Page</h4>
                                <p class="mb-1"><code>{{ $page->key }}</code></p>
                                <small class="text-muted">
                                    The URL and template are wired into the code, so they are not
                                    editable here — only the content and SEO are.
                                </small>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Status</h4>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="statusSwitch" value="1"
                                           @checked(old('is_active', $page->exists ? $page->is_active : true))>
                                    <label class="form-check-label" for="statusSwitch">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ri-save-line align-middle me-1"></i> Save
                                    </button>
                                    <a href="{{ route('cms-pages.index') }}" class="btn btn-light">
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
