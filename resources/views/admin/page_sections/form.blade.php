@include('admin.includes.header')
@php($locales = config('translatable.locales'))
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit section</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('page-sections.index') }}">Page sections</a></li>
                                <li class="breadcrumb-item active">{{ $section->title ?: $section->key }}</li>
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

            <form method="post" enctype="multipart/form-data" action="{{ route('page-sections.update', $section) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Content</h4>

                                @include('admin.includes.lang_tabs', ['id' => 'section'])

                                <div class="tab-content pt-3">
                                    @foreach($locales as $lang)
                                        @php($t = $section->translate($lang))
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="section-{{ $lang }}" role="tabpanel">
                                            <div class="mb-3">
                                                <label class="form-label">Eyebrow ({{ strtoupper($lang) }})</label>
                                                <input class="form-control" type="text" name="{{ $lang }}[eyebrow]" value="{{ old("$lang.eyebrow", $t?->eyebrow) }}">
                                                <small class="text-muted">The small line above the heading.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Title ({{ strtoupper($lang) }})</label>
                                                <input class="form-control" type="text" name="{{ $lang }}[title]" value="{{ old("$lang.title", $t?->title) }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Alt text ({{ strtoupper($lang) }})</label>
                                                <textarea class="form-control" rows="3" name="{{ $lang }}[subtitle]">{{ old("$lang.subtitle", $t?->subtitle) }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Text ({{ strtoupper($lang) }})</label>
                                                <textarea class="form-control js-editor" rows="7" name="{{ $lang }}[body]">{{ old("$lang.body", $t?->body) }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Button text ({{ strtoupper($lang) }})</label>
                                                <input class="form-control" type="text" name="{{ $lang }}[button_text]" value="{{ old("$lang.button_text", $t?->button_text) }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Images</h4>
                                <div class="row">
                                    @include('admin.includes.media_field', ['field' => 'image', 'label' => 'Section image', 'cols' => 6, 'current' => $section->getRawOriginal('image'), 'preview' => $section->image])
                                    @include('admin.includes.media_field', ['field' => 'background_image', 'label' => 'Background image', 'cols' => 6, 'current' => $section->getRawOriginal('background_image'), 'preview' => $section->background_image])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Section</h4>
                                <p class="mb-1">{{ $section->page?->title }} · <code>{{ $section->key }}</code></p>
                                <small class="text-muted">
                                    Where this section sits on the page is wired into the code,
                                    so only its content is editable here.
                                </small>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Button</h4>
                                <label class="form-label">Button URL</label>
                                <input class="form-control" type="text" name="button_url" value="{{ old('button_url', $section->button_url) }}" placeholder="/one-day-tours">
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Status</h4>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="statusSwitch" value="1"
                                           @checked(old('is_active', $section->is_active))>
                                    <label class="form-check-label" for="statusSwitch">Active</label>
                                </div>
                                <small class="text-muted">When inactive, the section falls back to the wording in the template.</small>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-3">Advanced options</h4>
                                <textarea class="form-control font-monospace" rows="4" name="options_json">{{ old('options_json', $section->options ? json_encode($section->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                                <small class="text-muted">JSON, for example an extra CSS class. May be left empty.</small>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ri-save-line align-middle me-1"></i> Save
                                    </button>
                                    <a href="{{ route('page-sections.index') }}" class="btn btn-light">
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
