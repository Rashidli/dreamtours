{{-- Browser-tab icon — admin-managed via Global settings → Brand → Favicon; falls back
     to the bundled DreamTour mark so the tab is never blank — and, since every page
     includes this, the link-preview tags. --}}
@php($siteFavicon = setting_image('favicon') ?: '/favicon.png')
<link rel="icon" href="{{ $siteFavicon }}" />
<link rel="apple-touch-icon" href="{{ $siteFavicon }}" />
@include('front.partials.social_meta')
