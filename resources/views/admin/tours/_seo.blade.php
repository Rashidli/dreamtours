{{-- Search engines: the tour's URL, and the title/description Google shows. Empty fields fall back
     to the tour name and short description, so nothing has to be filled in for a page to work. --}}
@php
  $seoT = $tour->translate('en');
  $seoSite = setting('site_name', 'DreamTour');
  $seoBase = rtrim(config('app.url'), '/').'/tours/';
  $seoSlug = old('en.slug', $seoT?->slug);
@endphp
<div class="border rounded p-3 mb-3" id="seo" data-seo-site="{{ $seoSite }}">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h6 class="mb-1">Search engines (SEO)</h6>
      <p class="text-muted small mb-2">How this tour appears on Google. Leave a field empty to use the tour name and short description.</p>
    </div>
    @if($tour->exists && $seoT?->slug && $tour->status === 'published')
      <a href="{{ $seoBase.$seoT->slug }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm"><i class="ri-external-link-line"></i> Open page</a>
    @endif
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="seo-slug">Page address (slug)</label>
    <div class="input-group">
      <span class="input-group-text text-muted">{{ preg_replace('#^https?://#', '', $seoBase) }}</span>
      <input id="seo-slug" class="form-control @error('en.slug') is-invalid @enderror" name="en[slug]" value="{{ $seoSlug }}" maxlength="200"
             placeholder="{{ $tour->exists ? '' : 'Made from the tour name' }}" data-original="{{ $seoT?->slug }}" autocomplete="off" spellcheck="false">
      @error('en.slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-text">Lower-case words joined by dashes, e.g. <code>niagara-falls-day-trip</code>. Renaming the tour does not change it.</div>
    <div class="alert alert-warning py-2 px-3 small mt-2 mb-0 d-none" id="seo-slug-warning">
      <i class="ri-information-line"></i> The address will change. The old link <code>/tours/{{ $seoT?->slug }}</code> keeps working and sends visitors (and Google) to the new one.
    </div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold d-flex justify-content-between" for="seo-title"><span>SEO title</span><span class="text-muted fw-normal small" data-seo-count="seo-title" data-seo-max="60"></span></label>
    <input id="seo-title" class="form-control @error('en.meta_title') is-invalid @enderror" name="en[meta_title]" value="{{ old('en.meta_title', $seoT?->meta_title) }}" maxlength="255"
           placeholder="{{ ($seoT?->name ?: 'Tour name').' | '.$seoSite }}">
    @error('en.meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">About 50–60 characters. Put the main place or activity first.</div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold d-flex justify-content-between" for="seo-description"><span>SEO description</span><span class="text-muted fw-normal small" data-seo-count="seo-description" data-seo-max="160"></span></label>
    <textarea id="seo-description" class="form-control @error('en.meta_description') is-invalid @enderror" name="en[meta_description]" rows="3" maxlength="1000"
              placeholder="{{ \Illuminate\Support\Str::limit((string) ($seoT?->short_description ?: 'The short description is used when this is empty.'), 160) }}">{{ old('en.meta_description', $seoT?->meta_description) }}</textarea>
    @error('en.meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">About 120–160 characters: what the trip is, the highlights and why to book.</div>
  </div>

  <div class="mb-0">
    <div class="small text-muted mb-1">Google preview</div>
    <div class="border rounded p-3 bg-light" style="max-width:640px;font-family:Arial,sans-serif">
      <div class="small text-success text-truncate" id="seo-preview-url">{{ preg_replace('#^https?://#', '', $seoBase).$seoSlug }}</div>
      <div class="text-truncate" style="color:#1a0dab;font-size:1.15rem" id="seo-preview-title"></div>
      <div class="small text-muted" id="seo-preview-description" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"></div>
    </div>
  </div>
</div>
<script>
(function () {
  const box = document.getElementById('seo'); if (!box) return;
  const $ = (id) => document.getElementById(id);
  const slug = $('seo-slug'), title = $('seo-title'), desc = $('seo-description');
  const name = document.querySelector('[name="en[name]"]'), short = document.querySelector('[name="en[short_description]"]');
  const base = @json(preg_replace('#^https?://#', '', $seoBase)), site = box.dataset.seoSite;
  const clean = (v) => v.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  const cut = (v, n) => v.length > n ? v.slice(0, n - 1).trimEnd() + '…' : v;
  function update() {
    const s = slug.value.trim() ? clean(slug.value) : (slug.dataset.original || clean(name?.value || ''));
    $('seo-preview-url').textContent = base + s;
    $('seo-preview-title').textContent = cut(title.value.trim() || ((name?.value.trim() || 'Tour name') + ' | ' + site), 65);
    $('seo-preview-description').textContent = cut(desc.value.trim() || (short?.value.trim() || ''), 165);
    $('seo-slug-warning').classList.toggle('d-none', !slug.dataset.original || !slug.value.trim() || clean(slug.value) === slug.dataset.original);
    box.querySelectorAll('[data-seo-count]').forEach((el) => {
      const n = $(el.dataset.seoCount).value.trim().length, max = +el.dataset.seoMax;
      el.textContent = n ? n + ' / ' + max : 'empty — uses the default';
      el.classList.toggle('text-danger', n > max);
    });
  }
  slug.addEventListener('blur', () => { if (slug.value.trim()) slug.value = clean(slug.value); update(); });
  [slug, title, desc, name, short].forEach((el) => el && el.addEventListener('input', update));
  update();
})();
</script>
