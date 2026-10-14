{{-- Policy pages (Privacy Policy, Terms and Conditions). Title from Pages & SEO; text from the page's "content" section. --}}
@php($policy = $sections->get('content'))
<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    @include('front.partials.head_assets')
    <title>{{ $cmsPage->meta_title ?: ($cmsPage->title.' | '.$siteSettings->get('site_name', 'DreamTour')) }}</title>
    @if($cmsPage->meta_description)<meta name="description" content="{{ $cmsPage->meta_description }}" />@endif
    @include('front.partials.data')
  </head>
  <body class="min-h-screen bg-white text-ink-800 antialiased">
    <a href="#main" class="skip-link">Skip to content</a>
    @include('front.partials.header')

    <main id="main">
      <section class="bg-ink-900 text-white">
        <div class="container-page py-12 lg:py-16">
          <nav aria-label="Breadcrumb" class="text-sm text-ink-300">
            <a href="/" class="hover:text-white">Home</a> <span aria-hidden="true">/</span> <span class="text-white">{{ $cmsPage->title }}</span>
          </nav>
          <h1 class="mt-3 font-display text-4xl font-extrabold text-white sm:text-5xl">{{ $policy?->title ?: $cmsPage->title }}</h1>
          @if($policy?->subtitle)<p class="mt-4 max-w-2xl text-ink-100">{{ $policy->subtitle }}</p>@endif
        </div>
      </section>

      <section class="py-12 lg:py-16">
        <div class="container-page">
          {{-- Rich text from the admin editor, so it renders as markup rather than escaped HTML. --}}
          <article class="rich-text mx-auto max-w-3xl">{!! $policy?->body ?: '<p>This page is being prepared.</p>' !!}</article>
          <p class="mx-auto mt-10 max-w-3xl text-sm text-ink-500">Last updated {{ ($policy?->updated_at ?? $cmsPage->updated_at)?->format('F j, Y') }}</p>
        </div>
      </section>
    </main>

    @include('front.partials.footer')
  </body>
</html>
