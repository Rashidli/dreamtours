{{-- Shared <head> assets for pages that are not generated from the CMS page templates. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('front.partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/dreamtour/dist/main.css?v={{ @filemtime(public_path('dreamtour/dist/main.css')) }}">
<script defer src="/dreamtour/src/js/app.js?v={{ @filemtime(public_path('dreamtour/src/js/app.js')) }}"></script>
<script defer src="/dreamtour/src/js/vendor/alpine.min.js"></script>
