<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $cmsPage->meta_title ?: $cmsPage->title }}</title>
    <meta
      name="description"
      content="{{ $cmsPage->meta_description ?: 'DreamTour — day trips, multi-day tours, cruises and private journeys.' }}"
    />
    @if($cmsPage->meta_keywords)<meta name="keywords" content="{{ $cmsPage->meta_keywords }}" />@endif

    @include('front.partials.favicon')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <!-- Compiled Tailwind stylesheet -->
    <link rel="stylesheet" href="/dreamtour/dist/main.css?v={{ @filemtime(public_path('dreamtour/dist/main.css')) }}" />

    <!-- Scripts: database-backed page data + Alpine components load before Alpine core -->
    @include('front.partials.data')
    <script defer src="/dreamtour/src/js/app.js?v={{ @filemtime(public_path('dreamtour/src/js/app.js')) }}"></script>
    <script defer src="/dreamtour/src/js/vendor/alpine.min.js"></script>
  </head>

  <body class="min-h-screen bg-white text-ink-800 antialiased">
    <a href="#main" class="skip-link">Skip to content</a>

    <!-- ====================================================================
         SITE HEADER  (Home §3.1 — logo, nav, CTAs, phone / WhatsApp / email,
         language switcher). Markup is duplicated per page by project choice.
         ==================================================================== -->
    @include('front.partials.header')

    <main id="main">
      <!-- ==================================================================
           SECTION 2 — HERO CAROUSEL, THEN TOUR SEARCH  (Home)
           ================================================================== -->
      @php
        $heroSection = $sections->get('hero');
        $heroOptions = is_array($heroSection?->options) ? $heroSection->options : [];
        $heroBackground = $heroSection?->background_image ?: 'https://images.unsplash.com/photo-1439853949127-fa647821eba0?q=80&w=1920&auto=format&fit=crop';
        $heroTitle = $heroSection?->title ?: 'Find Your Next Adventure';
        // Slides come from admin: Company content → hero_slide (order, active). Without any, the hero background is shown alone.
        $slides = collect($frontendData['heroSlides'] ?? [])->filter(fn ($slide) => filled($slide['image']))->values();
        if ($slides->isEmpty()) {
          $slides = collect([['image' => $heroBackground, 'place' => '', 'caption' => '', 'url' => '']]);
        }
      @endphp
      @if($showSection('hero'))
      <section
        x-data="carousel({ count: {{ $slides->count() }}, interval: 6500 })"
        @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()"
        @keydown.left="prev()" @keydown.right="next()"
        @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)"
        class="relative isolate overflow-hidden bg-ink-950 text-white {{ $heroOptions['section_class'] ?? '' }}"
        aria-roledescription="carousel" aria-label="Featured trips"
      >
        <div class="relative h-[72svh] max-h-[640px] min-h-[460px] sm:h-[78vh] sm:max-h-[780px] sm:min-h-[540px]">
          <!-- Slides -->
          <div class="absolute inset-0 -z-10" :aria-live="stopped ? 'polite' : 'off'">
            @foreach($slides as $i => $slide)
              <div x-show="index === {{ $i }}" @if($i > 0) x-cloak @endif
                   x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                   x-transition:leave="transition-opacity duration-700" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                   class="absolute inset-0" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $slides->count() }}{{ $slide['place'] ? ': '.$slide['place'] : '' }}">
                <img src="{{ $slide['image'] }}" alt="{{ ($slide['alt'] ?? '') ?: collect([$slide['place'], $slide['caption']])->filter()->implode(' — ') }}"
                     class="size-full object-cover" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
              </div>
            @endforeach
            <!-- Shade only the bottom and left edges so the copy reads without darkening the subject -->
            <div class="absolute inset-0 bg-gradient-to-t from-ink-950/90 via-ink-950/25 to-ink-950/10"></div>
            <div class="absolute inset-0 hidden bg-gradient-to-r from-ink-950/65 via-ink-950/10 to-transparent lg:block"></div>
          </div>

          <!-- Copy -->
          <div class="container-page flex h-full flex-col justify-end pb-24 sm:pb-24">
            <div class="max-w-2xl">
              @if($heroSection?->eyebrow)
                <p class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white/85 sm:text-base">{{ $heroSection->eyebrow }}</p>
              @endif
              <h1 class="mt-3 font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-white drop-shadow-sm sm:text-5xl lg:text-6xl">
                <span class="block">{{ str($heroTitle)->beforeLast(' ') }}</span>
                <span class="block text-brand-500">{{ str($heroTitle)->afterLast(' ') }}</span>
              </h1>
              @if($heroSection?->subtitle)
                <p class="mt-4 max-w-xl text-base text-ink-100 sm:text-lg">{{ $heroSection->subtitle }}</p>
              @endif
              @foreach($slides as $i => $slide)
                @if($slide['place'])
                  <p x-show="index === {{ $i }}" @if($i > 0) x-cloak @endif class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-white"><svg class="size-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>{{ $slide['place'] }}</span>
                    @if($slide['caption'])<span class="text-ink-100">{{ $slide['caption'] }}</span>@endif
                    @if($slide['url'])<a href="{{ $slide['url'] }}" class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur hover:bg-white/25">View tour →</a>@endif
                  </p>
                @endif
              @endforeach
            </div>
          </div>

          <!-- Controls -->
          @if($slides->count() > 1)
            <div class="absolute inset-x-0 bottom-6">
              <div class="container-page flex items-center justify-center gap-3 sm:justify-end">
                <button type="button" @click="prev()" class="grid size-10 place-items-center rounded-full bg-white/85 text-xl text-ink-900 shadow hover:bg-white" aria-label="Previous slide">‹</button>
                <div class="flex items-center gap-1.5">
                  @foreach($slides as $i => $slide)
                    <button type="button" @click="go({{ $i }})" class="grid h-6 place-items-center" :aria-current="index === {{ $i }}" aria-label="Show slide {{ $i + 1 }}{{ $slide['place'] ? ': '.$slide['place'] : '' }}">
                      <span class="block h-2 rounded-full transition-all" :class="index === {{ $i }} ? 'w-6 bg-white' : 'w-2 bg-white/55 hover:bg-white/85'"></span>
                    </button>
                  @endforeach
                </div>
                <button type="button" @click="next()" class="grid size-10 place-items-center rounded-full bg-white/85 text-xl text-ink-900 shadow hover:bg-white" aria-label="Next slide">›</button>
                <button type="button" @click="toggle()" class="grid size-10 place-items-center rounded-full bg-ink-950/50 text-white ring-1 ring-white/40 hover:bg-ink-950/70" :aria-label="stopped ? 'Play slideshow' : 'Pause slideshow'">
                  <svg x-show="!stopped" class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>
                  <svg x-show="stopped" x-cloak class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                </button>
              </div>
            </div>
          @endif
        </div>
      </section>
      @endif

      <!-- Trip search — its own band below the carousel so it never covers the image -->
      <section class="bg-ink-950 pb-10 pt-6 text-white sm:pb-12 sm:pt-8" aria-labelledby="hero-search-title">
        <div class="container-page">
          <h2 id="hero-search-title" class="sr-only">Search tours</h2>
          <!-- Search bar -->
          <form
            data-hide-wa-float
            x-data="heroSearch"
            @submit.prevent="submit"
            @keydown.escape="typeOpen = false"
            class="mx-auto max-w-5xl divide-y divide-ink-100 rounded-2xl bg-white p-2 text-ink-800 shadow-card sm:flex sm:items-stretch sm:divide-x sm:divide-y-0"
          >
            <!-- Destination -->
            <div class="flex flex-1 items-center gap-2.5 px-3 py-2.5">
              <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
              </svg>
              <label class="sr-only" for="hero-destination">Where to?</label>
              <input
                id="hero-destination"
                type="text"
                x-model="destination"
                placeholder="Where to?"
                class="w-full bg-transparent text-sm placeholder:text-ink-400 focus:outline-none"
              />
            </div>

            <!-- Date -->
            <div class="flex flex-1 items-center gap-2.5 px-3 py-2.5">
              <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
              </svg>
              <label class="sr-only" for="hero-date">Any date</label>
              <input
                id="hero-date"
                type="date"
                x-model="date"
                class="w-full bg-transparent text-sm text-ink-600 focus:outline-none"
              />
            </div>

            <!-- Tour type -->
            <div class="relative flex flex-1 items-center px-1 py-1 sm:py-0" @click.outside="typeOpen = false">
              <button
                type="button"
                @click="typeOpen = !typeOpen"
                :aria-expanded="typeOpen"
                class="flex w-full items-center gap-2.5 rounded-xl px-2 py-2.5 text-left text-sm hover:bg-ink-50"
              >
                <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <span class="flex-1 truncate" :class="!type && 'text-ink-400'" x-text="typeLabel"></span>
                <svg class="size-4 shrink-0 text-ink-400 transition-transform" :class="typeOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
              <div
                x-show="typeOpen"
                x-cloak
                x-transition
                class="absolute left-0 top-full z-20 mt-2 w-56 rounded-xl bg-white p-1 shadow-xl ring-1 ring-ink-900/10"
              >
                <template x-for="t in types" :key="t">
                  <button
                    type="button"
                    @click="pickType(t)"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-ink-50"
                    :class="typeLabel === t && 'font-semibold text-brand-600'"
                    x-text="t"
                  ></button>
                </template>
              </div>
            </div>

            <!-- Submit -->
            <div class="pt-2 sm:flex sm:items-center sm:pl-2 sm:pt-0">
              <button type="submit" class="btn btn-primary w-full sm:w-auto">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                {{ $sections->get('hero')?->button_text ?: 'Find Tours' }}
              </button>
            </div>
          </form>
          <!-- Trust badges -->
          <ul class="mt-6 flex flex-wrap justify-center gap-x-7 gap-y-3 text-sm">
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" />
              </svg>
              Comfortable Travel
            </li>
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
              </svg>
              Expertly Planned
            </li>
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
              Best Price Guarantee
            </li>
          </ul>
        </div>
      </section>

      <!-- ==================================================================
           SECTION 3 — UPCOMING TOURS  (Home §3)
           ================================================================== -->
      @if($showSection('featured_tours'))
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-end">
            <div>
              <p class="eyebrow">{{ $sections->get('featured_tours')?->eyebrow ?: 'Upcoming Departures' }}</p>
              <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('featured_tours')?->title ?: 'Upcoming Tours' }}</h2>
              <p class="mt-3 max-w-lg text-ink-500">{{ $sections->get('featured_tours')?->subtitle ?: 'Reserve your seat on our next departures. Dates, prices and availability are updated regularly.' }}</p>
            </div>
            <a href="/one-day-tours" class="btn btn-outline btn-sm hidden shrink-0 sm:inline-flex">
              View all tours
            </a>
          </div>

          <div x-data="tourList" class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(collect($frontendData['tours'])->where('home', true) as $t)
              @include('front.partials.tour_card', ['t' => $t])
            @endforeach
          </div>

          <div class="mt-12 flex justify-center">
            <a href="/one-day-tours" class="btn btn-primary btn-lg">
              View More Tours
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
              </svg>
            </a>
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
      <!-- ==================================================================
           SECTION 4 — TOUR CATEGORIES  (Home §3 / §4)
           ================================================================== -->
      @if($showSection('tour_types'))
      <section class="bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">{{ $sections->get('tour_types')?->eyebrow ?: 'Browse by Category' }}</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('tour_types')?->title ?: 'Find the Right Kind of Trip' }}</h2>
            <p class="mt-3 text-ink-500">{{ $sections->get('tour_types')?->subtitle ?: 'Choose an admin-managed tour category and explore available journeys.' }}</p>
          </div>
          <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($frontendData['categories'] as $category)
              <a href="{{ $category['href'] }}" class="group overflow-hidden rounded-4xl bg-white ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover">
                @if($category['image'])<img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" class="h-40 w-full object-cover">@endif
                <div class="p-6">
                  <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">{{ $category['tourCount'] . ' tours' }}</p>
                  <h3 class="mt-2 font-display text-lg font-bold text-ink-900">{{ $category['name'] }}</h3>
                  <p class="mt-1 text-sm text-ink-500">{{ $category['description'] }}</p>
                  <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">Explore</span>
                </div>
              </a>
            @endforeach
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 5 — POPULAR DESTINATIONS  (Home §5)
           ================================================================== -->
      @if($showSection('destinations') && filled($frontendData['destinations']))
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-end">
            <div>
              <p class="eyebrow">{{ $sections->get('destinations')?->eyebrow ?: 'Where People Go' }}</p>
              <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('destinations')?->title ?: 'Popular Destinations' }}</h2>
              <p class="mt-3 max-w-lg text-ink-500">{{ $sections->get('destinations')?->subtitle ?: 'The regions our travellers keep coming back to — across Ontario, Québec and just over the border.' }}</p>
            </div>
            <a href="/one-day-tours" class="btn btn-outline btn-sm hidden shrink-0 sm:inline-flex">
              Browse all tours
            </a>
          </div>

          <div
            x-data="{ destinations: window.DreamTourData.destinations }"
            class="mt-10 grid gap-5 sm:grid-cols-2 lg:auto-rows-55 lg:grid-cols-3"
          >
            @foreach($frontendData['destinations'] as $d)
              <a
                href="{{ $d['href'] }}"
                aria-label="{{ 'View tours in ' . $d['name'] }}"
                class="group relative isolate flex flex-col justify-end overflow-hidden rounded-4xl bg-ink-900 p-5 ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover"
                class="{{ $loop->first ? 'min-h-64 lg:col-span-2 lg:row-span-2' : 'min-h-56' }}"
              >
                <img
                  src="{{ $d['image'] }}"
                  alt=""
                  aria-hidden="true"
                  loading="lazy"
                  class="absolute inset-0 -z-10 size-full object-cover transition duration-500 group-hover:scale-105"
                />
                <span
                  class="absolute inset-0 -z-10 bg-linear-to-t from-ink-950/85 via-ink-950/25 to-transparent"
                ></span>

                <span
                  class="inline-flex w-fit items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium text-white backdrop-blur"
                >
                  <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                  </svg>
                  <span>{{ $d['region'] }}</span>
                </span>

                <h3
                  class="mt-3 font-display font-bold text-white"
                  class="{{ $loop->first ? 'text-2xl sm:text-3xl' : 'text-xl' }}"
                >{{ $d['name'] }}</h3>

                @if($loop->first)<p class="mt-2 max-w-md text-sm text-white/80">{{ $d['description'] }}</p>@endif

                <div class="mt-3 flex items-center gap-2 text-sm font-semibold text-white">
                  <span>{{ $d['tourCount'] . ($d['tourCount'] === 1 ? ' tour' : ' tours') }}</span>
                  <svg class="size-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                  </svg>
                </div>
              </a>
            @endforeach
          </div>

          <div class="mt-8 sm:hidden">
            <a href="/one-day-tours" class="btn btn-outline btn-sm w-full">
              Browse all tours
            </a>
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 6 — WHY DREAMTOUR  (Home §5 — advantages)
           ================================================================== -->
      @if($showSection('why_choose_us'))
      <section class="bg-ink-900 py-16 text-white lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow text-brand-400">{{ $sections->get('why_choose_us')?->eyebrow ?: 'Why DreamTour' }}</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-white sm:text-4xl">{{ $sections->get('why_choose_us')?->title ?: 'Travel Made Easy & Enjoyable' }}</h2>
            <p class="mt-3 text-ink-200">{{ $sections->get('why_choose_us')?->subtitle ?: 'We handle the planning, the driving and the details — you just show up and enjoy the trip.' }}</p>
          </div>

          <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Comfortable Transport</h3>
              <p class="mt-1 text-sm text-ink-200">
                Modern coaches and minivans with air conditioning, reclining
                seats and room to stretch out.
              </p>
            </div>
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Curated Itineraries</h3>
              <p class="mt-1 text-sm text-ink-200">
                Every stop is chosen and timed by guides who know the route —
                more sights, less waiting around.
              </p>
            </div>
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Easy Pick-up Points</h3>
              <p class="mt-1 text-sm text-ink-200">
                Board close to home from convenient locations right across the
                Greater Toronto Area.
              </p>
            </div>
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Friendly Service</h3>
              <p class="mt-1 text-sm text-ink-200">
                A real person confirms every booking and looks after your group
                from pickup to drop-off.
              </p>
            </div>
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 7 — UPCOMING DATES  (Home §5)
           ================================================================== -->
      @if($showSection('departures'))
      <section class="bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-end">
            <div>
              <p class="eyebrow">{{ $sections->get('departures')?->eyebrow ?: 'Plan Ahead' }}</p>
              <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('departures')?->title ?: 'Upcoming Departure Dates' }}</h2>
              <p class="mt-3 max-w-lg text-ink-500">{{ $sections->get('departures')?->subtitle ?: 'The next few departures at a glance. Seats are confirmed on a first-come basis.' }}</p>
            </div>
            <a href="/one-day-tours" class="btn btn-outline btn-sm hidden shrink-0 sm:inline-flex">
              Full schedule
            </a>
          </div>

          <ul class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(collect($frontendData['tours'])->where('home', true)->sortBy('dateValue') as $t)
              @php($departsAt = $t['dateValue'] ? \Carbon\Carbon::parse($t['dateValue']) : null)
              <li class="min-w-0">
                <a href="/tours/{{ $t['slug'] }}" class="group flex items-center gap-4 rounded-2xl bg-white p-4 ring-1 ring-ink-900/5 transition hover:-translate-y-0.5 hover:shadow-card">
                  <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-brand-50 text-center text-brand-700">
                    <span class="block font-display text-lg font-extrabold leading-none">{{ $departsAt?->format('j') }}</span>
                    <span class="mt-0.5 block text-[11px] font-semibold uppercase">{{ $departsAt?->format('M') }}</span>
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate font-display text-sm font-bold text-ink-900">{{ $t['name'] }}</span>
                    <span class="mt-0.5 block text-xs text-ink-500">
                      <span>{{ $t['duration'] }}</span> ·
                      <span class="{{ $t['seatsLeft'] === 0 ? 'text-brand-600 font-medium' : '' }}">{{ $t['seatsLeft'] === 0 ? 'Sold out' : $t['seatsLeft'].' seats left' }}</span>
                    </span>
                  </span>
                  <svg class="size-4 shrink-0 text-ink-300 transition-transform group-hover:translate-x-1 group-hover:text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                  </svg>
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 8 — CUSTOMER REVIEWS  (Home §5)
           ================================================================== -->
      @if($showSection('reviews') && filled($frontendData['reviews']))
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">{{ $sections->get('reviews')?->eyebrow ?: 'Traveller Reviews' }}</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('reviews')?->title ?: 'Trips People Loved' }}</h2>
            <div
              x-data="{ s: window.DreamTourData.reviewsSummary }"
              class="mt-4 inline-flex items-center gap-2 text-sm text-ink-500"
            >
              <span class="flex gap-0.5 text-brand-500">
                <template x-for="n in 5" :key="n">
                  <svg class="size-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.59a1 1 0 0 0-1.18 0l-3.56 2.59c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.12L2.1 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69L9.05 2.93Z" />
                  </svg>
                </template>
              </span>
              <span><strong class="font-semibold text-ink-900" x-text="s.rating.toFixed(1)"></strong> / 5 · <span x-text="s.count + '+ verified reviews'"></span></span>
            </div>
          </div>

          <div
            x-data="{ reviews: window.DreamTourData.reviews }"
            class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4"
          >
            @foreach($frontendData['reviews'] as $r)
              <figure class="flex h-full flex-col rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
                <div class="flex gap-0.5" aria-hidden="true">
                  @for($n = 1; $n <= 5; $n++)
                    <svg class="size-4 {{ $n <= $r['rating'] ? 'text-brand-500' : 'text-ink-200' }}" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.11l1.36 4.18c.3.92-.75 1.69-1.54 1.12l-3.55-2.58a1 1 0 0 0-1.18 0l-3.55 2.58c-.79.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.11L2.4 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69L9.05 2.93Z" />
                    </svg>
                  @endfor
                </div>
                <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-ink-700">
                  <span>{{ '“'.$r['text'].'”' }}</span>
                </blockquote>
                <figcaption class="mt-5 flex items-center gap-3 border-t border-ink-100 pt-4">
                  <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-100 font-display text-sm font-bold text-brand-700">{{ collect(explode(' ', (string) $r['name']))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</span>
                  <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink-900">{{ $r['name'] }}</span>
                    <span class="block truncate text-xs text-ink-500">{{ $r['role'] }}</span>
                  </span>
                </figcaption>
              </figure>
            @endforeach
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 9 — FROM PREVIOUS TOURS  (Home §5 — gallery preview)
           ================================================================== -->
      @if($showSection('gallery_preview'))
      <section class="bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-end">
            <div>
              <p class="eyebrow">{{ $sections->get('gallery_preview')?->eyebrow ?: 'Gallery' }}</p>
              <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('gallery_preview')?->title ?: 'Moments From Past Trips' }}</h2>
              <p class="mt-3 max-w-lg text-ink-500">{{ $sections->get('gallery_preview')?->subtitle ?: 'Real photos from recent departures — this is where you could be next season.' }}</p>
            </div>
            <a href="/gallery" class="btn btn-outline btn-sm hidden shrink-0 sm:inline-flex">
              View full gallery
            </a>
          </div>

          <div
            x-data="{ photos: window.DreamTourData.gallery }"
            class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4"
          >
            @foreach($frontendData['gallery'] as $p)
              <figure class="group relative overflow-hidden rounded-2xl">
                <img
                  src="{{ $p['thumb'] ?: $p['src'] }}"
                  alt="{{ $p['caption'] }}"
                  loading="lazy"
                  class="aspect-square w-full object-cover transition duration-500 group-hover:scale-105"
                />
                <figcaption class="pointer-events-none absolute inset-x-0 bottom-0 bg-linear-to-t from-ink-950/80 to-transparent p-3 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                  <span>{{ $p['caption'] }}</span>
                </figcaption>
              </figure>
            @endforeach
          </div>

          <div class="mt-8 sm:hidden">
            <a href="/gallery" class="btn btn-outline btn-sm w-full">View full gallery</a>
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           VISA SUPPORT INTRODUCTION  (admin: Page sections → home / visa_intro)
           ================================================================== -->
      @if($showSection('visa_intro'))
      @php($visaIntro = $sections->get('visa_intro'))
      <section class="bg-white py-14 lg:py-20">
        <div class="container-page">
          <div class="grid items-center gap-8 overflow-hidden rounded-4xl bg-sand-50 p-7 ring-1 ring-ink-900/5 sm:p-10 lg:grid-cols-[auto_1fr_auto] lg:gap-10 lg:p-12">
            <span class="grid size-16 place-items-center rounded-2xl bg-brand-600 text-white shadow-card" aria-hidden="true">
              <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            </span>
            <div class="max-w-2xl">
              <p class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-brand-600">{{ $visaIntro?->eyebrow ?: 'Visa Support' }}</p>
              <h2 class="mt-2 font-display text-2xl font-extrabold text-ink-900 sm:text-3xl">{{ $visaIntro?->title ?: 'Need help with your visa?' }}</h2>
              <p class="mt-3 text-ink-600">{{ $visaIntro?->subtitle ?: 'We help you complete application forms, check your documents for completeness and book visa appointments.' }}</p>
            </div>
            <a href="{{ $visaIntro?->button_url ?: '/visa-support' }}" class="btn btn-primary btn-lg justify-self-start lg:justify-self-end">{{ $visaIntro?->button_text ?: 'More Information' }} →</a>
          </div>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 10 — QUICK BOOKING REQUEST + CONTACT  (Home §5)
           ================================================================== -->
      @if($showSection('request_form'))
      <section id="request" class="bg-ink-900 py-16 text-white lg:py-24">
        <div class="container-page grid gap-12 lg:grid-cols-2 lg:gap-16">
          <!-- Contact info + social links -->
          <div>
            <p class="eyebrow text-brand-400">{{ $sections->get('request_form')?->eyebrow ?: 'Quick Request' }}</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-white sm:text-4xl">{{ $sections->get('request_form')?->title ?: 'Not Sure Which Tour? Ask Us.' }}</h2>
            <p class="mt-3 max-w-md text-ink-200">{{ $sections->get('request_form')?->subtitle ?: 'Send a quick request and a DreamTour representative will call or message you to help plan the trip — no obligation.' }}</p>

            <dl class="mt-8 space-y-4 text-sm">
              <div class="flex items-center gap-3">
                <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                <dt class="sr-only">Phone</dt>
                <dd><a href="tel:{{ $siteSettings->get('phone_link') }}" class="font-medium text-white hover:text-brand-400">{{ $siteSettings->get('phone') }}</a></dd>
              </div>
              <div class="flex items-center gap-3">
                <svg class="size-5 shrink-0 text-brand-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.3-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2Zm5.8 14.06c-.24.68-1.42 1.3-1.95 1.35-.5.05-1.13.24-3.68-.77-3.1-1.22-5.08-4.4-5.24-4.6-.15-.2-1.25-1.66-1.25-3.17 0-1.5.79-2.24 1.07-2.55.28-.31.6-.38.8-.38.2 0 .4 0 .58.01.19.01.44-.07.68.52.24.6.83 2.07.9 2.22.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.07 1.31 2.36 1.46.29.15.46.12.63-.07.17-.2.73-.85.93-1.14.2-.29.39-.24.66-.15.27.1 1.7.8 1.99.95.29.15.48.22.55.34.07.12.07.71-.17 1.39Z" />
                </svg>
                <dt class="sr-only">WhatsApp</dt>
                <dd><a href="https://wa.me/{{ $siteSettings->get('whatsapp') }}" target="_blank" rel="noopener" class="font-medium text-white hover:text-brand-400">Message on WhatsApp</a></dd>
              </div>
              <div class="flex items-center gap-3">
                <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
                <dt class="sr-only">Email</dt>
                <dd><a href="mailto:{{ $siteSettings->get('email') }}" class="font-medium text-white hover:text-brand-400">{{ $siteSettings->get('email') }}</a></dd>
              </div>
              <div class="flex items-center gap-3">
                <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <dt class="sr-only">Hours</dt>
                <dd class="text-ink-200">Mon–Sun · 8:00 AM – 8:00 PM</dd>
              </div>
            </dl>

            <div class="mt-8">
              <p class="text-xs font-semibold uppercase tracking-wider text-ink-300">Follow us</p>
              <div class="mt-3 flex gap-3">
                <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="DreamTour on Facebook" class="grid size-10 place-items-center rounded-full bg-white/10 text-white transition hover:bg-brand-600">
                  <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.99 3.657 9.128 8.438 9.878v-6.987H7.898V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.987C18.343 21.128 22 16.99 22 12Z" /></svg>
                </a>
                <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="DreamTour on Instagram" class="grid size-10 place-items-center rounded-full bg-white/10 text-white transition hover:bg-brand-600">
                  <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.72 3.72 0 0 1-1.38-.9 3.72 3.72 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.21 15.58 2.2 15.2 2.2 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.21 8.8 2.2 12 2.2Zm0 1.8c-3.15 0-3.5.01-4.74.07-.98.04-1.5.2-1.86.35-.47.18-.8.4-1.15.75-.35.35-.57.68-.75 1.15-.14.36-.3.88-.35 1.86C3.01 8.5 3 8.85 3 12s.01 3.5.07 4.74c.04.98.2 1.5.35 1.86.18.47.4.8.75 1.15.35.35.68.57 1.15.75.36.14.88.3 1.86.35C8.5 20.99 8.85 21 12 21s3.5-.01 4.74-.07c.98-.04 1.5-.2 1.86-.35.47-.18.8-.4 1.15-.75.35-.35.57-.68.75-1.15.14-.36.3-.88.35-1.86.06-1.24.07-1.59.07-4.74s-.01-3.5-.07-4.74c-.04-.98-.2-1.5-.35-1.86a3.1 3.1 0 0 0-.75-1.15 3.1 3.1 0 0 0-1.15-.75c-.36-.14-.88-.3-1.86-.35C15.5 4.01 15.15 4 12 4Zm0 3.07A4.93 4.93 0 1 1 12 16.93 4.93 4.93 0 0 1 12 7.07Zm0 1.8a3.13 3.13 0 1 0 0 6.26 3.13 3.13 0 0 0 0-6.26Zm5.03-3.04a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3Z" /></svg>
                </a>
                <a href="https://tiktok.com" target="_blank" rel="noopener" aria-label="DreamTour on TikTok" class="grid size-10 place-items-center rounded-full bg-white/10 text-white transition hover:bg-brand-600">
                  <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 3c.3 2.1 1.5 3.6 3.5 3.9v2.5c-1.3.1-2.5-.2-3.6-.8v6.1c0 3.1-2.5 5.6-5.6 5.6S5.2 17.8 5.2 14.7 7.7 9.1 10.8 9.1c.3 0 .6.02.9.07v2.6a3 3 0 0 0-.9-.14 2.9 2.9 0 1 0 2.9 2.9V3h2.8Z" /></svg>
                </a>
                <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="DreamTour on YouTube" class="grid size-10 place-items-center rounded-full bg-white/10 text-white transition hover:bg-brand-600">
                  <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.51A3.02 3.02 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3.02 3.02 0 0 0 2.12 2.14c1.88.51 9.38.51 9.38.51s7.5 0 9.38-.51a3.02 3.02 0 0 0 2.12-2.14A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.2 3.6-6.2 3.6Z" /></svg>
                </a>
              </div>
            </div>
          </div>

          <!-- Request form -->
          <form
            x-data="quickRequest"
            @submit.prevent="submit"
            class="rounded-4xl bg-white p-6 text-ink-800 shadow-card sm:p-8"
          >
            <div
              x-show="sent"
              x-cloak
              x-transition
              class="mb-6 flex items-start gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
            >
              <svg class="mt-0.5 size-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
              </svg>
              <p>Your request has been received. A DreamTour representative will contact you to confirm your booking.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="qr-name" class="mb-1 block text-sm font-medium text-ink-700">Full name</label>
                <input id="qr-name" type="text" x-model="form.name" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="qr-phone" class="mb-1 block text-sm font-medium text-ink-700">Phone</label>
                <input id="qr-phone" type="tel" x-model="form.phone" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="qr-email" class="mb-1 block text-sm font-medium text-ink-700">Email</label>
                <input id="qr-email" type="email" x-model="form.email" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="qr-people" class="mb-1 block text-sm font-medium text-ink-700">Travellers</label>
                <input id="qr-people" type="number" min="1" max="56" x-model="form.people" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div class="sm:col-span-2">
                <label for="qr-tour" class="mb-1 block text-sm font-medium text-ink-700">Tour of interest</label>
                <select id="qr-tour" x-model="form.tour" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="">Not sure yet — help me choose</option>
                  <template x-for="name in tours" :key="name">
                    <option :value="name" x-text="name"></option>
                  </template>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label for="qr-message" class="mb-1 block text-sm font-medium text-ink-700">Message <span class="font-normal text-ink-400">(optional)</span></label>
                <textarea id="qr-message" rows="3" x-model="form.message" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
              </div>
            </div>

            <button type="submit" class="btn btn-primary mt-5 w-full">Send Request</button>
            <p class="mt-3 text-center text-xs text-ink-400">
              We'll only use your details to contact you about this trip.
            </p>
          </form>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 11 — PICK-UP POINTS  (Home §5)
           ================================================================== -->
      @if($showSection('pickup_points'))
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-16">
          <div>
            <p class="eyebrow">{{ $sections->get('pickup_points')?->eyebrow ?: 'Pick-up Points' }}</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">{{ $sections->get('pickup_points')?->title ?: 'Convenient Locations Across the GTA' }}</h2>
            <p class="mt-3 max-w-md text-ink-500">{{ $sections->get('pickup_points')?->subtitle ?: 'Choose the stop nearest you when you book. Most tours also offer a central downtown departure.' }}</p>
            <a href="/contact" class="btn btn-primary btn-sm mt-6 inline-flex">
              View all pick-up points
            </a>
          </div>

          <ul
            x-data="{ points: window.DreamTourData.pickupPoints }"
            class="grid gap-3 sm:grid-cols-2"
          >
            @foreach($frontendData['pickupPoints'] as $pt)
              <li class="flex items-center gap-3 rounded-2xl border border-ink-100 bg-white px-4 py-3">
                <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <span class="min-w-0">
                  <span class="block truncate text-sm font-semibold text-ink-900">{{ $pt['name'] }}</span>
                  <span class="block text-xs text-ink-500">{{ $pt['area'] }}</span>
                </span>
              </li>
            @endforeach
          </ul>
        </div>
      </section>
      @endif

      <!-- ==================================================================
           SECTION 12 — HELP CTA BAND  (Home §5)
           ================================================================== -->
      @if($showSection('cta'))
      <section class="bg-brand-600 py-14 text-white">
        <div class="container-page flex flex-col items-center gap-6 text-center lg:flex-row lg:justify-between lg:text-left">
          <div class="flex items-center gap-4">
            <span class="hidden size-14 shrink-0 place-items-center rounded-full bg-white/15 sm:grid">
              <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 0 0-9 9v3.75A2.25 2.25 0 0 0 5.25 18H6a.75.75 0 0 0 .75-.75v-4.5A.75.75 0 0 0 6 12h-.75A9 9 0 0 1 18.75 12H18a.75.75 0 0 0-.75.75v4.5c0 .414.336.75.75.75h.75m0 0v.75A2.25 2.25 0 0 1 16.5 21H12" />
              </svg>
            </span>
            <div>
              <p class="font-display text-xl font-extrabold sm:text-2xl">{{ $sections->get('cta')?->title ?: 'Have questions? We\'re here to help.' }}</p>
              <p class="mt-1 text-sm text-white/85">{{ $sections->get('cta')?->subtitle ?: 'Call or message us any day — 8:00 AM to 8:00 PM.' }}</p>
            </div>
          </div>
          <div class="flex flex-col items-center gap-3 sm:flex-row">
            <a href="tel:{{ $siteSettings->get('phone_link') }}" class="font-display text-2xl font-extrabold tracking-tight hover:text-white/90 sm:text-3xl">{{ $siteSettings->get('phone') }}</a>
            <a href="/contact" class="btn btn-white">Contact Us</a>
          </div>
        </div>
      </section>
      @endif
    </main>

    <!-- ====================================================================
         SITE FOOTER  (Home §5 — company info, quick links, contact, socials)
         ==================================================================== -->
    @include('front.partials.footer')

    <!-- Toast region -->
    <div
      x-data
      class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4"
      aria-live="polite"
    >
      <template x-for="t in $store.toast.items" :key="t.id">
        <div
          x-transition
          class="pointer-events-auto flex items-center gap-3 rounded-xl bg-ink-900 px-4 py-3 text-sm font-medium text-white shadow-xl"
        >
          <svg class="size-4 shrink-0 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
          </svg>
          <span x-text="t.message"></span>
        </div>
      </template>
    </div>
  </body>
</html>


