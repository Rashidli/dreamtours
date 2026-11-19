@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.form_title', ['title' => $faq->exists ? 'Edit FAQ' : 'New FAQ', 'parent' => ['FAQ', route('faqs.index')], 'current' => $faq->exists ? 'Edit' : 'New',
  'badge' => $faq->exists ? ($faq->is_active ? ['Active', 'success'] : ['Inactive', 'secondary']) : null])
@include('admin.includes.flash')
@include('admin.includes.validation_errors')
<form method="post" class="tf-form" action="{{ $faq->exists ? route('faqs.update', $faq) : route('faqs.store') }}">@csrf @if($faq->exists)@method('PUT')@endif
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card"><div class="card-body">
      @include('admin.cms_ui.section_head', ['icon' => 'ri-question-answer-line', 'title' => 'Question & answer', 'hint' => 'Write it the way a customer would ask it, and answer in plain words.'])
      <div class="mb-3"><label class="fw-semibold tf-req" for="faq-question">Question</label><input id="faq-question" class="form-control" name="en[question]" value="{{ old('en.question', $faq->translate('en')?->question) }}" required placeholder="e.g. Is travel insurance included?"></div>
      <div class="mb-0"><label class="fw-semibold tf-req" for="faq-answer">Answer</label><textarea id="faq-answer" class="form-control" rows="7" name="en[answer]" required>{{ old('en.answer', $faq->translate('en')?->answer) }}</textarea><div class="form-text">Plain text. Line breaks are kept on the website.</div></div>
    </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-body">
      <h5 class="cms-side-title">Placement</h5>
      <div class="mb-3"><label class="fw-semibold" for="faq-group">Group</label><select id="faq-group" class="form-select" name="group">@foreach(\App\Models\Faq::GROUPS as $key => $label)<option value="{{ $key }}" @selected(old('group', $faq->group ?? 'trip') === $key)>{{ $label }}</option>@endforeach</select><div class="form-text">Trip FAQs show on the Contact page; Visa Support FAQs on the Visa Support page.</div></div>
      <div class="mb-2"><label class="fw-semibold" for="faq-order">Order</label><input id="faq-order" class="form-control" type="number" min="0" name="order" value="{{ old('order', $faq->exists ? $faq->order : '') }}" placeholder="last"><div class="form-text">Lower numbers come first. Leave empty to add it at the end.</div></div>
      <div class="cms-switches">
        @include('admin.cms_ui.status_switch', ['id' => 'faq-active', 'checked' => old('is_active', $faq->exists ? $faq->is_active : true), 'label' => 'Active', 'hint' => 'Shown on the website.'])
      </div>
      @include('admin.cms_ui.save_actions', ['label' => $faq->exists ? 'Save changes' : 'Create FAQ', 'back' => ['All FAQs', route('faqs.index')]])
    </div></div>
  </div>
</div>
</form>
</div></div></div>
@include('admin.includes.footer')
