{{-- Save + back buttons for the side panel. $label (default Save), $back = [label, url], $form (optional form id) --}}
<div class="d-grid gap-2 mt-3">
  <button type="submit" class="btn btn-primary btn-lg" @if(!empty($form)) form="{{ $form }}" @endif><i class="ri-save-line align-middle me-1"></i> {{ $label ?? 'Save' }}</button>
  <a class="btn btn-light" href="{{ $back[1] }}"><i class="ri-arrow-left-line align-middle me-1"></i> {{ $back[0] }}</a>
</div>
