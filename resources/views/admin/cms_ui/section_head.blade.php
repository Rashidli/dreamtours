{{-- Form section heading (tours form pattern): icon tile + title + one-line hint. $icon, $title, $hint (optional) --}}
<div class="tf-head"><span class="tf-icon"><i class="{{ $icon }}"></i></span><div><h5>{{ $title }}</h5>@if(!empty($hint))<p>{!! $hint !!}</p>@endif</div></div>
