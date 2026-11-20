{{--
  Flash feedback after a redirect.
  - Success (->with('message', …) or 'success') shows as the toast at the bottom of the screen (rendered by the footer),
    so it is not repeated here.
  - Problems (->with('error', …)) and warnings (->with('warning', …)) stay as a banner at the top until dismissed.
--}}
@php app()->instance('dt.flash.rendered', true); @endphp
@foreach(['error' => ['critical', 'ri-error-warning-fill', 'alert'], 'warning' => ['warning', 'ri-alert-fill', 'alert'], 'info' => ['info', 'ri-information-fill', 'status']] as $key => [$tone, $icon, $role])
  @if(session($key))
    <div class="ui-banner ui-banner-{{ $tone }}" role="{{ $role }}">
      <i class="{{ $icon }} ui-banner-icon" aria-hidden="true"></i>
      <div class="ui-banner-body">{{ session($key) }}</div>
      <button type="button" class="ui-banner-close" aria-label="Dismiss" onclick="this.closest('.ui-banner').remove()"><i class="ri-close-line" aria-hidden="true"></i></button>
    </div>
  @endif
@endforeach
