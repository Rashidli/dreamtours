{{--
  Form problems after a failed validation: one critical banner at the top of the content.
  Each problem links to its field (the footer script focuses it, opening its tab / collapse / modal if needed);
  inline .invalid-feedback under the fields keeps working. The footer renders this itself (dtFallback) when a
  page does not include it, and moves it to the top of the content.
--}}
@if($errors->any())
  @php
    app()->instance('dt.errors.rendered', true);
    $dtProblems = collect($errors->getMessages())->flatMap(fn ($messages, $field) => collect($messages)->map(fn ($m) => [$field, $m]))->unique(fn ($p) => $p[1])->values();
    $dtCount = $dtProblems->count();
  @endphp
  <div class="ui-banner ui-banner-critical ui-banner-form" role="alert" tabindex="-1" data-dt-errors @if(!empty($dtFallback)) data-dt-fallback @endif aria-labelledby="dt-errors-title">
    <i class="ri-error-warning-fill ui-banner-icon" aria-hidden="true"></i>
    <div class="ui-banner-body">
      <h2 class="ui-banner-title" id="dt-errors-title">{{ $dtCount === 1 ? 'There is 1 problem with this form' : 'There are '.$dtCount.' problems with this form' }}</h2>
      <ul class="ui-banner-list">
        @foreach($dtProblems as [$field, $message])<li><a href="#" data-dt-field="{{ $field }}">{{ $message }}</a></li>@endforeach
      </ul>
    </div>
    <button type="button" class="ui-banner-close" aria-label="Dismiss" onclick="this.closest('.ui-banner').remove()"><i class="ri-close-line" aria-hidden="true"></i></button>
  </div>
@endif
