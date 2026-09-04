{{-- Media field: upload a file or paste a URL / stored file name.
     $field (required), $label (required), $current (raw stored value), $preview (resolved url), $cols, $accept --}}
<div class="col-md-{{ $cols ?? 6 }} mb-3">
  <label>{{ $label }}</label>
  <input type="file" class="form-control" name="{{ $field }}" accept="{{ $accept ?? 'image/*' }}">
  <input class="form-control mt-2" name="{{ $field }}_path" value="{{ old($field.'_path', $current) }}" placeholder="or paste an image URL / stored file name">
  @if(!empty($preview))
    <a href="{{ $preview }}" target="_blank" rel="noopener"><img src="{{ $preview }}" alt="" class="mt-2" style="height:64px;width:auto;border-radius:6px;object-fit:cover"></a>
  @endif
</div>
