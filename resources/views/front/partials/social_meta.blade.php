{{-- Link previews for Facebook, Instagram, WhatsApp, Telegram, X, LinkedIn and iMessage.
     A page may set $ogTitle, $ogDescription and $ogImage before including the head;
     otherwise the CMS page's meta fields and the logo card are used. --}}
@php
  $ogSite = setting('site_name', 'DreamTour');
  $ogTitle = $ogTitle ?? (isset($cmsPage) ? ($cmsPage->meta_title ?: $cmsPage->title) : null) ?: $ogSite;
  $ogDescription = $ogDescription ?? (isset($cmsPage) ? $cmsPage->meta_description : null) ?: 'DreamTour — day trips, multi-day tours, cruises and private journeys.';
  $ogBrand = empty($ogImage);
  $ogImage = ($ogImage ?? null) ?: \App\Support\SocialImage::brand() ?: url(setting_image('favicon') ?: '/favicon.png');
@endphp
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $ogSite }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ str($ogDescription)->squish()->limit(200) }}">
<meta property="og:image" content="{{ $ogImage }}">
@if(str_ends_with(parse_url($ogImage, PHP_URL_PATH) ?? '', '.jpg') && str_contains($ogImage, '/storage/social/'))
<meta property="og:image:width" content="{{ \App\Support\SocialImage::WIDTH }}">
<meta property="og:image:height" content="{{ \App\Support\SocialImage::HEIGHT }}">
<meta property="og:image:type" content="image/jpeg">
@endif
<meta property="og:image:alt" content="{{ $ogBrand ? $ogSite.' logo' : $ogTitle }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ str($ogDescription)->squish()->limit(200) }}">
<meta name="twitter:image" content="{{ $ogImage }}">
