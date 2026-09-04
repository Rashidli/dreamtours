<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Cruises — DreamTour</title>
    <meta name="description" content="Hand-picked cruises from the Caribbean to the Norwegian fjords, on their own or paired with a DreamTour coach trip. Filter by region, cruise line, length and price." />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <!-- Compiled Tailwind stylesheet -->
    <link rel="stylesheet" href="/dreamtour/dist/main.css" />

    <!-- Scripts: database-backed page data + Alpine components load before Alpine core -->
    @include('front.partials.data')
    <script defer src="/dreamtour/src/js/app.js"></script>
    <script defer src="/dreamtour/src/js/vendor/alpine.min.js"></script>
  </head>

  <body class="min-h-screen bg-white text-ink-800 antialiased">
    <a href="#main" class="skip-link">Skip to content</a>

    @include('front.partials.header')

    <main id="main" x-data="cruiseList">
      <!-- ==================================================================
           PAGE HERO + BREADCRUMB  (Cruises — §18)
           ================================================================== -->
      <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <img
          src="{{ $sections->get('hero')?->background_image ?: 'https://images.unsplash.com/photo-1548574505-5e239809ee19?q=80&w=1920&auto=format&fit=crop' }}"
          alt=""
          aria-hidden="true"
          class="absolute inset-0 -z-10 size-full object-cover"
          loading="eager"
        />
        <div class="absolute inset-0 -z-10 bg-linear-to-r from-ink-950 via-ink-950/92 to-ink-950/55"></div>
        <div class="absolute inset-0 -z-10 bg-linear-to-t from-ink-950/80 via-transparent to-ink-950/40"></div>

        <div class="container-page py-10 sm:py-12 lg:py-16">
          <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-ink-200">
            <a href="//" class="hover:text-white">Home</a>
            <svg class="size-4 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
            <span class="font-medium text-white">Cruises</span>
          </nav>

          <div class="mt-6 max-w-2xl">
            <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>
          </div>

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
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
              </svg>
              <label class="sr-only" for="s-dest">Where to?</label>
              <input id="s-dest" type="text" x-model="q" placeholder="Region, port or cruise line" class="w-full bg-transparent text-sm placeholder:text-ink-400 focus:outline-none" />
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
              Find Cruises
            </button>
          </form>

          <ul class="mt-7 flex flex-wrap gap-x-7 gap-y-3 text-sm">
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9m0 0 7.5 2.25M12 12 4.5 14.25M4.5 14.25 3 19.5a9 9 0 0 0 18 0l-1.5-5.25M12 6.75h.008v.008H12V6.75Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 19.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" />
              </svg>
              Major cruise lines
            </li>
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" />
              </svg>
              Add a coach transfer or tour
            </li>
            <li class="flex items-center gap-2 text-ink-100">
              <svg class="size-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
              We handle the booking &amp; documents
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
                <h2 class="font-display text-base font-bold text-ink-900">Filter cruises</h2>
                <button type="button" @click="reset()" x-show="activeCount" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Clear all</button>
              </div>

              <div class="mt-5">
                <label for="f-q" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Search</label>
                <input id="f-q" type="text" x-model="q" placeholder="Region, port or line" class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>

              <fieldset class="mt-5">
                <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">Destination</legend>
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
                <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">Cruise line</legend>
                <div class="space-y-1.5">
                  <template x-for="l in lineOptions" :key="l">
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                      <input type="checkbox" :value="l" x-model="lines" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                      <span x-text="l"></span>
                    </label>
                  </template>
                </div>
              </fieldset>

              <fieldset class="mt-5">
                <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500">Length</legend>
                <div class="space-y-1.5">
                  <template x-for="d in durationOptions" :key="d">
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                      <input type="checkbox" :value="d" x-model="durations" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                      <span x-text="d"></span>
                    </label>
                  </template>
                </div>
              </fieldset>

              <div class="mt-5">
                <label for="f-month" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500">Sailing month</label>
                <select id="f-month" x-model="month" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="">All months</option>
                  <template x-for="m in months" :key="m"><option :value="m" x-text="m"></option></template>
                </select>
              </div>

              <div class="mt-5">
                <div class="mb-1.5 flex items-center justify-between">
                  <span class="text-xs font-semibold uppercase tracking-wider text-ink-500">Max price</span>
                  <span class="text-sm font-semibold text-ink-900" x-text="'$' + priceMax"></span>
                </div>
                <input type="range" :min="priceFloor" :max="priceCeiling" step="25" x-model.number="priceMax" class="w-full accent-brand-600" aria-label="Maximum price per person" />
                <div class="mt-1 flex justify-between text-[11px] text-ink-400">
                  <span x-text="'$' + priceFloor"></span>
                  <span x-text="'$' + priceCeiling + '+'"></span>
                </div>
              </div>
            </div>
          </aside>

          <div id="results" class="mt-8 scroll-mt-24 lg:mt-0">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <h2 class="font-display text-2xl font-extrabold text-ink-900 sm:text-3xl">Cruises</h2>
                <p class="mt-1 text-sm text-ink-500">
                  Showing <span class="font-semibold text-ink-900" x-text="Math.min(results.length, filtered.length)"></span>
                  of <span x-text="filtered.length"></span>
                  <span x-text="filtered.length === 1 ? 'sailing' : 'sailings'"></span>
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
              <p class="font-display text-lg font-bold text-ink-900">No cruises match those filters</p>
              <p class="mt-1 text-sm text-ink-500">Try another region or a wider price range.</p>
              <button type="button" @click="reset()" class="btn btn-outline btn-sm mt-4">Clear all filters</button>
            </div>

            <div x-show="filtered.length" class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
              <template x-for="c in results" :key="c.id">
                <article class="group flex flex-col overflow-hidden rounded-4xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover">
                  <div class="relative">
                    <img :src="c.image" :alt="c.name" class="h-52 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                    <span
                      x-show="c.label"
                      class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm"
                      :class="badgeClass(c.label)"
                      x-text="c.label"
                    ></span>
                    <button
                      type="button"
                      @click="$store.wishlist.toggle('cruise-' + c.id, c.name)"
                      :aria-pressed="$store.wishlist.has('cruise-' + c.id)"
                      :aria-label="$store.wishlist.has('cruise-' + c.id) ? 'Remove from wishlist' : 'Save to wishlist'"
                      class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 text-ink-500 backdrop-blur transition hover:text-brand-600"
                    >
                      <svg class="size-5" :class="$store.wishlist.has('cruise-' + c.id) && 'text-brand-600'" :fill="$store.wishlist.has('cruise-' + c.id) ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                      </svg>
                    </button>
                    <span class="absolute bottom-3 left-3 rounded-full bg-ink-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur" x-text="c.duration"></span>
                  </div>

                  <div class="flex flex-1 flex-col p-5">
                    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-brand-600">
                      <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9m0 0 7.5 2.25M12 12 4.5 14.25M4.5 14.25 3 19.5a9 9 0 0 0 18 0l-1.5-5.25" />
                      </svg>
                      <span x-text="c.line"></span>
                    </p>
                    <h3 class="mt-1 font-display text-lg font-bold text-ink-900" x-text="c.name"></h3>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-500">
                      <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                      </svg>
                      <span x-text="c.region"></span>
                    </p>
                    <p class="mt-2 line-clamp-2 text-sm text-ink-500" x-text="c.description"></p>

                    <template x-if="c.withTour">
                      <span class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-sand-100 px-2.5 py-1 text-xs font-medium text-ink-700">
                        <svg class="size-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Can pair with a coach tour
                      </span>
                    </template>

                    <dl class="mt-4 grid grid-cols-1 gap-y-2 text-sm text-ink-600">
                      <div class="flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <dt class="sr-only">Next sailing</dt>
                        <dd x-text="c.date"></dd>
                      </div>
                      <div class="flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A11.954 11.954 0 0 1 12 15c-1.777 0-3.47.386-4.951 1.079A8.99 8.99 0 0 0 12 21Zm0-18a8.949 8.949 0 0 1 4.951 1.488A11.954 11.954 0 0 0 12 9c-1.777 0-3.47-.386-4.951-1.079A8.99 8.99 0 0 1 12 3Zm-9 9h18" />
                        </svg>
                        <dt class="sr-only">Departure port</dt>
                        <dd x-text="'Departs ' + c.port"></dd>
                      </div>
                      <div class="flex items-center gap-2" :class="c.cabinsLeft <= 10 && 'font-medium text-brand-600'">
                        <svg class="size-4 shrink-0" :class="c.cabinsLeft > 10 && 'text-ink-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                        <dt class="sr-only">Cabin availability</dt>
                        <dd x-text="cabinsLabel(c)"></dd>
                      </div>
                    </dl>

                    <template x-if="c.documents && c.documents.length">
                      <details class="mt-4 rounded-2xl bg-sand-50 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-ink-800">Documents you'll need (<span x-text="c.documents.length"></span>)</summary>
                        <ul class="mt-2 space-y-1 text-sm text-ink-600">
                          <template x-for="(doc, di) in c.documents" :key="di">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">&bull;</span><span x-text="doc"></span></li>
                          </template>
                        </ul>
                      </details>
                    </template>

                    <div class="mt-auto pt-5">
                      <div class="flex items-baseline gap-1.5 border-t border-ink-100 pt-4">
                        <template x-if="c.previousPrice">
                          <span class="text-sm text-ink-400 line-through" x-text="'$' + c.previousPrice"></span>
                        </template>
                        <span class="text-xs text-ink-400">From</span>
                        <span class="font-display text-xl font-extrabold text-ink-900" x-text="'$' + c.price"></span>
                        <span class="text-xs text-ink-400" x-text="'/ ' + c.cabinFrom.toLowerCase() + ' cabin'"></span>
                      </div>
                      <div class="mt-4 grid grid-cols-2 gap-2">
<a :href="'/tours/' + c.slug" class="btn btn-outline btn-sm w-full">View Details</a>
                        <a :href="'/tours/' + c.slug + '#booking'" class="btn btn-primary btn-sm w-full">Request Quote</a>
                      </div>
                    </div>
                  </div>
                </article>
              </template>
            </div>

            <div x-show="hasMore" class="mt-10 flex justify-center">
              <button type="button" @click="more()" class="btn btn-outline btn-lg">
                Load more cruises
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
            </div>

            <p class="mt-8 text-xs text-ink-400">
              Prices are per person in the lead cabin category, based on double
              occupancy, and exclude port charges, gratuities and airfare. Final
              pricing is confirmed on your quote.
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
              <p class="font-display text-xl font-extrabold sm:text-2xl">Want a cabin recommendation? Just ask.</p>
              <p class="mt-1 text-sm text-white/85">Call or message us any day — 8:00 AM to 8:00 PM.</p>
            </div>
          </div>
          <div class="flex flex-col items-center gap-3 sm:flex-row">
            <a href="tel:{{ $siteSettings->get('phone_link') }}" class="font-display text-2xl font-extrabold tracking-tight hover:text-white/90 sm:text-3xl">{{ $siteSettings->get('phone') }}</a>
            <a href="//contact" class="btn btn-white">Contact Us</a>
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


