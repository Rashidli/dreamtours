{{-- Browser-tab icon. Admin-managed via Global settings → Brand → Favicon; falls back
     to the bundled DreamTour mark so the tab is never blank. --}}
@php($siteFavicon = setting_image('favicon') ?: '/favicon.png')
<link rel="icon" href="{{ $siteFavicon }}" />
<link rel="apple-touch-icon" href="{{ $siteFavicon }}" />
