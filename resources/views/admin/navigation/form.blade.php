@include('admin.includes.header')
@php($locales = config('translatable.locales'))
@php($groups = ['header' => 'Header', 'footer_company' => 'Footer: Company', 'footer_support' => 'Footer: Support', 'footer_legal' => 'Footer: Legal'])
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $item->exists ? 'Edit link' : 'New link' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('navigation-items.index') }}">Navigation & footer</a></li>
                                <li class="breadcrumb-item active">{{ $item->exists ? $item->title : 'New' }}</li>
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

            <form method="post" action="{{ $item->exists ? route('navigation-items.update', $item) : route('navigation-items.store') }}">
                @csrf
                @if($item->exists) @method('PUT') @endif

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Link</h4>

                                @include('admin.includes.lang_tabs', ['id' => 'nav'])

                                <div class="tab-content pt-3">
                                    @foreach($locales as $lang)
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="nav-{{ $lang }}" role="tabpanel">
                                            <div class="mb-3">
                                                <label class="form-label">Title ({{ strtoupper($lang) }}) <span class="text-danger">*</span></label>
                                                <input class="form-control @error("$lang.title") is-invalid @enderror" type="text"
                                                       name="{{ $lang }}[title]" value="{{ old("$lang.title", $item->translate($lang)?->title) }}">
                                                @error("$lang.title") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">URL <span class="text-danger">*</span></label>
                                    <input class="form-control @error('url') is-invalid @enderror" type="text" name="url"
                                           value="{{ old('url', $item->url) }}" placeholder="/one-day-tours">
                                    @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Internal links start with “/”; external links need the full address.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Placement</h4>
                                <div class="mb-3">
                                    <label class="form-label">Group <span class="text-danger">*</span></label>
                                    <select class="form-select" name="location">
                                        @foreach($groups as $value => $label)
                                            <option value="{{ $value }}" @selected(old('location', $item->location ?: 'header') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Parent link</label>
                                    <select class="form-select" name="parent_id">
                                        <option value="">None</option>
                                        @foreach($parents as $parent)
                                            <option value="{{ $parent->id }}" @selected(old('parent_id', $item->parent_id) == $parent->id)>
                                                {{ $parent->title }} ({{ $groups[$parent->location] ?? $parent->location }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Opens in</label>
                                    <select class="form-select" name="target">
                                        <option value="_self" @selected(old('target', $item->target) === '_self')>Same tab</option>
                                        <option value="_blank" @selected(old('target', $item->target) === '_blank')>New tab</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Status</h4>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="statusSwitch" value="1"
                                           @checked(old('is_active', $item->exists ? $item->is_active : true))>
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
                                    <a href="{{ route('navigation-items.index') }}" class="btn btn-light">
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
