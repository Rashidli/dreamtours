<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $cmsPage->meta_title ?: ($cmsPage->title ?: 'International Tours — DreamTour') }}</title>
    <meta name="description" content="{{ $cmsPage->meta_description ?: 'Escorted group trips beyond Canada — Europe, Asia, China, the Philippines, Azerbaijan and the US. Filter by region, trip length, date and price.' }}" />
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

    @include('front.partials.header')

    <main id="main" x-data="tourFilter('international')">
      <!-- ==================================================================
           PAGE HERO + BREADCRUMB  (International Tours — §4 / §5)
           ================================================================== -->
      <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <img
          src="{{ $sections->get('hero')?->background_image ?: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?q=80&w=1920&auto=format&fit=crop' }}"
          alt=""
          aria-hidden="true"
          class="absolute inset-0 -z-10 size-full object-cover"
          loading="eager"
        />
        <div class="absolute inset-0 -z-10 bg-linear-to-r from-ink-950 via-ink-950/92 to-ink-950/55"></div>
        <div class="absolute inset-0 -z-10 bg-linear-to-t from-ink-950/80 via-transparent to-ink-950/40"></div>

        <div class="container-page py-10 sm:py-12 lg:py-16">
          <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-ink-200">
            <a href="/" class="hover:text-white">Home</a>
            <svg class="size-4 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
            <span class="font-medium text-white">International Tours</span>
          </nav>

          <div class="mt-6 max-w-2xl">
            <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl text-white">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>
          </div>

          <!-- Region quick-select -->
          <div class="mt-6 flex flex-wrap gap-2">
            <template x-for="r in regionOptions" :key="r">
              <button
                type="button"
                @click="regions = regions.includes(r) ? [] : [r]; document.getElementById('results').scrollIntoView({ behavior: 'smooth' })"
                class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                :class="regions.includes(r) ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/25 text-ink-100 hover:bg-white/10'"
                x-text="r"
              ></button>
            </template>
          </div>

          <form
            @submit.prevent="document.getElementById('results').scrollIntoView({ behavior: 'smooth' })"
            class="mt-6 grid max-w-3xl gap-2 rounded-2xl bg-white p-2 text-ink-800 shadow-card sm:grid-cols-[1.3fr_1fr_1fr_auto] sm:items-stretch"
          >
            <div class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 sm:py-2">
              <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A11.954 11.954 0 0 1 12 15c-1.777 0-3.47.386-4.951 1.079A8.99 8.99 0 0 0 12 21Zm0-18a8.949 8.949 0 0 1 4.951 1.488A11.954 11.954 0 0 0 12 9c-1.777 0-3.47-.386-4.951-1.079A8.99 8.99 0 0 1 12 3Zm-9 9h18" />
              </svg>
              <label class="sr-only" for="s-dest">Where to?</label>
              <input id="s-dest" type="text" x-model="q" placeholder="Country or city" class="w-full bg-transparent text-sm placeholder:text-ink-400 focus:outline-none" />
            </div>
            <div class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 sm:border-l sm:border-ink-100 sm:py-2">
              <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
              </svg>
              <label class="sr-only" for="s-date">Earliest date</label>
              <input id="s-date" type="date" x-model="date" class="w-full bg-transparent text-sm text-ink-600 focus:outline-none" />
            </div>
            <div class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 sm:border-l sm:border-ink-100 sm:py-2">
              <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              <label class="sr-only" for="s-month">Month</label>
              <select id="s-month" x-model="month" class="w-full bg-transparent text-sm focus:outline-none">
                <option value="">Any month</option>
                <template x-for="m in months" :key="m"><option :value="m" x-text="m"></option></template>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-full sm:w-auto">
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
              </svg>
              Find Tours
            </button>
          </form>

          <ul class="mt-7 flex flex-wrap gap-x-7 gap-y-3 text-sm">
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5" />
              </svg>
              Flights &amp; hotels arranged
            </li>
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A11.954 11.954 0 0 1 12 15c-1.777 0-3.47.386-4.951 1.079A8.99 8.99 0 0 0 12 21Zm0-18a8.949 8.949 0 0 1 4.951 1.488A11.954 11.954 0 0 0 12 9c-1.777 0-3.47-.386-4.951-1.079A8.99 8.99 0 0 1 12 3Zm-9 9h18" />
              </svg>
              English-speaking guides
            </li>
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
              </svg>
              Small groups
            </li>
          </ul>
        </div>
      </section>

      <!-- ==================================================================
           LISTING — filter sidebar + results grid
           ================================================================== -->
      <section class="bg-sand-50 py-10 lg:py-14">
        <div class="container-page lg:grid lg:grid-cols-[280px_1fr] lg:gap-10">
          <aside class="lg:sticky lg:top-24 lg:self-start">
            <button
              type="button"
              @click="filtersOpen = !filtersOpen"
              :aria-expanded="filtersOpen"
              class="flex w-full items-center justify-between rounded-2xl border border-ink-200 bg-white px-4 py-3 font-display text-sm font-semibold text-ink-900 lg:hidden"
            >
              <span class="flex items-center gap-2">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6 12h12m-9 5.25h6" />
                </svg>
                Filters
                <span x-show="activeCount" class="grid size-5 place-items-center rounded-full bg-brand-600 text-xs text-white" x-text="activeCount"></span>
              </span>
              <svg class="size-4 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
              </svg>
            </button>

            <div
              class="mt-3 hidden rounded-2xl bg-white p-5 ring-1 ring-ink-900/5 lg:mt-0 lg:block"
              :class="filtersOpen && '!block'"
            >
              <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-bold text-ink-900">Filter tours</h2>
                <button type="button" @click="reset()" x-show="activeCount" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Clear all</button>
              </div>

              <div class="mt-5">
                <label for="f-q" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Search</label>
                <input id="f-q" type="text" x-model="q" placeholder="Country or city" class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>

              <fieldset class="mt-5">
                <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">Country / region</legend>
                <div class="space-y-1.5">
                  <template x-for="r in regionOptions" :key="r">
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                      <input type="checkbox" :value="r" x-model="regions" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                      <span x-text="r"></span>
                    </label>
                  </template>
                </div>
              </fieldset>

              <fieldset class="mt-5">
                <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">Trip length</legend>
                <div class="space-y-1.5">
                  <template x-for="d in durationOptions" :key="d">
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                      <input type="checkbox" :value="d" x-model="durations" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                      <span x-text="d"></span>
                    </label>
                  </template>
                </div>
              </fieldset>

              <fieldset class="mt-5"><legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">City or destination</legend><div class="space-y-1.5"><template x-for="destination in destinationOptions" :key="destination"><label class="flex items-center gap-2.5 text-sm text-ink-700"><input type="checkbox" :value="destination" x-model="destinations" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"><span x-text="destination"></span></label></template></div></fieldset>
              <div class="mt-5"><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Specific date</label><input type="date" x-model="date" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm"></div>
              <div class="mt-5"><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Minimum available seats</label><input type="number" min="0" x-model.number="minSeats" class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm"></div>
              <div class="mt-5"><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Age category</label><select x-model="age" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm"><option value="">All ages</option><template x-for="option in ageOptions" :key="option"><option :value="option" x-text="option"></option></template></select></div>
              <div class="mt-5"><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Availability</label><select x-model="availability" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm"><option value="">All statuses</option><template x-for="option in availabilityOptions" :key="option"><option :value="option" x-text="option.replace('_',' ')"></option></template></select></div>
              <div class="mt-5"><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Tour format</label><select x-model="tourKind" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm"><option value="">All formats</option><option value="group">Group tour</option><option value="private">Private tour</option><option value="cruise">Cruise tour</option></select></div>
              <div class="mt-5">
                <label for="f-month" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Departure month</label>
                <select id="f-month" x-model="month" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="">All months</option>
                  <template x-for="m in months" :key="m"><option :value="m" x-text="m"></option></template>
                </select>
              </div>

              <fieldset class="mt-5"><legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">Transportation</legend><div class="space-y-1.5"><template x-for="vehicle in vehicleOptions" :key="vehicle"><label class="flex items-center gap-2.5 text-sm text-ink-700"><input type="checkbox" :value="vehicle" x-model="vehicles" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"><span x-text="vehicle"></span></label></template></div></fieldset>
              <div class="mt-5">
                <div class="mb-1.5 flex items-center justify-between">
                  <span class="text-xs font-semibold uppercase tracking-wider text-ink-500">Max price</span>
                  <span class="text-sm font-semibold text-ink-900" x-text="'CA$' + priceMax"></span>
                </div>
                <input type="range" :min="priceFloor" :max="priceCeiling" step="50" x-model.number="priceMax" class="w-full accent-brand-600" aria-label="Maximum price per person" />
                <div class="mt-1 flex justify-between text-[11px] text-ink-400">
                  <span x-text="'CA$' + priceFloor"></span>
                  <span x-text="'CA$' + priceCeiling + '+'"></span>
                </div>
              </div>

              <label class="mt-5 flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" x-model="hideSoldOut" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                Hide sold-out tours
              </label>
            </div>
          </aside>

          <div id="results" class="mt-8 scroll-mt-24 lg:mt-0">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <h2 class="font-display text-2xl font-extrabold text-ink-900 sm:text-3xl">{{ $sections->get('catalogue')?->title ?: 'International Tours' }}</h2>
                <p class="mt-1 text-sm text-ink-500">
                  Showing <span class="font-semibold text-ink-900" x-text="Math.min(results.length, filtered.length)"></span>
                  of <span x-text="filtered.length"></span>
                  <span x-text="filtered.length === 1 ? 'tour' : 'tours'"></span>
                </p>
              </div>
              <div class="flex items-center gap-2">
                <label for="sort" class="text-sm text-ink-500">Sort by</label>
                <select id="sort" x-model="sort" class="rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="date">Upcoming first</option>
                  <option value="price-asc">Price: low to high</option>
                  <option value="price-desc">Price: high to low</option>
                </select>
              </div>
            </div>

            <div x-show="!filtered.length" class="mt-10 rounded-4xl bg-white p-10 text-center ring-1 ring-ink-900/5">
              <p class="font-display text-lg font-bold text-ink-900">No tours match those filters</p>
              <p class="mt-1 text-sm text-ink-500">Try another region or widen your dates and price range.</p>
              <button type="button" @click="reset()" class="btn btn-outline btn-sm mt-4">Clear all filters</button>
            </div>

            <div x-show="filtered.length" class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
              @foreach(collect($frontendData['tours'])->filter(fn ($x) => in_array('international', $x['listings'], true)) as $t)
                <div x-show="visibleIds.includes('{{ $t['id'] }}')" :style="{ order: orderOf('{{ $t['id'] }}') }" class="flex">
                  @include('front.partials.tour_card', ['t' => $t])
                </div>
              @endforeach
            </div>

            <div x-show="hasMore" class="mt-10 flex justify-center">
              <button type="button" @click="more()" class="btn btn-outline btn-lg">
                Load more tours
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
            </div>

            <p class="mt-8 text-xs text-ink-400">
              Prices are per person, land only, based on double occupancy. International
              airfare can be added to any package — ask us for a quote.
            </p>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           HELP CTA BAND
           ================================================================== -->
      <section class="bg-brand-600 py-14 text-white">
        <div class="container-page flex flex-col items-center gap-6 text-center lg:flex-row lg:justify-between lg:text-left">
          <div class="flex items-center gap-4">
            <span class="hidden size-14 shrink-0 place-items-center rounded-full bg-white/15 sm:grid">
              <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 0 0-9 9v3.75A2.25 2.25 0 0 0 5.25 18H6a.75.75 0 0 0 .75-.75v-4.5A.75.75 0 0 0 6 12h-.75A9 9 0 0 1 18.75 12H18a.75.75 0 0 0-.75.75v4.5c0 .414.336.75.75.75h.75m0 0v.75A2.25 2.25 0 0 1 16.5 21H12" />
              </svg>
            </span>
            <div>
              <p class="font-display text-xl font-extrabold sm:text-2xl">{{ $sections->get('cta')?->title ?: 'Dreaming of a trip abroad? Let\'s plan it.' }}</p>
              <p class="mt-1 text-sm text-white/85">{{ $sections->get('cta')?->subtitle ?: 'Call or message us any day — 8:00 AM to 8:00 PM.' }}</p>
            </div>
          </div>
          <div class="flex flex-col items-center gap-3 sm:flex-row">
            <a href="tel:{{ $siteSettings->get('phone_link') }}" class="font-display text-2xl font-extrabold tracking-tight hover:text-white/90 sm:text-3xl">{{ $siteSettings->get('phone') }}</a>
            <a href="/contact" class="btn btn-white">Contact Us</a>
          </div>
        </div>
      </section>
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


