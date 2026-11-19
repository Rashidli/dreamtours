{{-- Image field with a real preview: upload a file or paste a URL / stored file name.
     Same field names as admin.includes.media_field ($field and {$field}_path).
     $field, $label, $current (raw stored value), $preview (resolved url), $hint (optional), $cols (optional, default 12), $accept --}}
<div class="col-md-{{ $cols ?? 12 }} mb-3 cms-media">
  <label class="fw-semibold" for="cms-media-{{ $field }}">{{ $label }}</label>
  <div class="cms-media-row">
    @if(!empty($preview))
      <a href="{{ $preview }}" target="_blank" rel="noopener" class="cms-media-thumb" title="Open the current image"><img src="{{ $preview }}" alt=""></a>
    @else
      <span class="cms-media-thumb cms-media-thumb-empty"><i class="ri-image-line"></i></span>
    @endif
    <div class="cms-media-inputs">
      <input type="file" class="form-control" id="cms-media-{{ $field }}" name="{{ $field }}" accept="{{ $accept ?? 'image/*,.heic,.heif' }}">
      <input class="form-control mt-2" name="{{ $field }}_path" value="{{ old($field.'_path', $current) }}" placeholder="or paste an image URL / stored file name" aria-label="{{ $label }}: image URL or stored file name">
    </div>
  </div>
  @if(!empty($hint))<div class="form-text">{{ $hint }}</div>@endif
</div>
