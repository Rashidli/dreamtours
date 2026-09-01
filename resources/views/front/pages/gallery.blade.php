<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Gallery — DreamTour</title>
    <meta name="description" content="Photo albums from past DreamTour departures — filter by trip, country, year, month and category, and open any album in the lightbox." />

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

    <main id="main" x-data="galleryBrowser">
      <!-- ==================================================================
           PAGE HERO + BREADCRUMB  (Gallery — §14)
           ================================================================== -->
      <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <img
          src="{{ $sections->get('hero')?->background_image ?: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?q=80&w=1920&auto=format&fit=crop' }}"
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
            <span class="font-medium text-white">Gallery</span>
          </nav>

          <div class="mt-6 max-w-2xl">
            <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>
            <p class="mt-5 text-sm text-ink-300">
              <span class="font-semibold text-white" x-text="albums.length"></span> albums ·
              <span class="font-semibold text-white" x-text="totalPhotos"></span> photos
            </p>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           FILTER BAR
           ================================================================== -->
      <section class="border-b border-ink-100 bg-white">
        <div class="container-page py-5">
          <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_1fr_1fr]">
            <div class="flex items-center gap-2.5 rounded-xl border border-ink-200 px-3 py-2">
              <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
              </svg>
              <label class="sr-only" for="g-q">Search albums</label>
              <input id="g-q" type="text" x-model="q" placeholder="Search tour, place…" class="w-full bg-transparent text-sm placeholder:text-ink-400 focus:outline-none" />
            </div>
            <div>
              <label class="sr-only" for="g-country">Country</label>
              <select id="g-country" x-model="country" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <option value="">All countries</option>
                <template x-for="c in countries" :key="c"><option :value="c" x-text="c"></option></template>
              </select>
            </div>
            <div>
              <label class="sr-only" for="g-year">Year</label>
              <select id="g-year" x-model="year" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <option value="">All years</option>
                <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
              </select>
            </div>
            <div>
              <label class="sr-only" for="g-month">Month</label>
              <select id="g-month" x-model="month" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <option value="">All months</option>
                <template x-for="m in months" :key="m"><option :value="m" x-text="m"></option></template>
              </select>
            </div>
            <div>
              <label class="sr-only" for="g-cat">Category</label>
              <select id="g-cat" x-model="category" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <option value="">All categories</option>
                <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
              </select>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between text-sm text-ink-500">
            <p>
              <span class="font-semibold text-ink-900" x-text="filtered.length"></span>
              <span x-text="filtered.length === 1 ? 'album' : 'albums'"></span>
            </p>
            <button type="button" @click="reset()" x-show="activeCount" class="font-semibold text-brand-600 hover:text-brand-700">Clear filters</button>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           ALBUM GRID
           ================================================================== -->
      <section class="bg-sand-50 py-12 lg:py-16">
        <div class="container-page">
          <div x-show="!filtered.length" class="rounded-4xl bg-white p-10 text-center ring-1 ring-ink-900/5">
            <p class="font-display text-lg font-bold text-ink-900">No albums match those filters</p>
            <p class="mt-1 text-sm text-ink-500">Try a different year or category.</p>
            <button type="button" @click="reset()" class="btn btn-outline btn-sm mt-4">Clear filters</button>
          </div>

          <div x-show="filtered.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="a in results" :key="a.id">
              <article class="group flex flex-col overflow-hidden rounded-4xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover">
                <button
                  type="button"
                  @click="open(a)"
                  class="relative block aspect-[4/3] w-full overflow-hidden"
                  :aria-label="'Open ' + a.tour + ' photo album'"
                >
                  <img :src="a.cover" :alt="a.tour" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                  <span class="absolute inset-0 bg-linear-to-t from-ink-950/75 via-ink-950/10 to-transparent"></span>
                  <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-ink-800" x-text="a.category"></span>
                  <template x-if="a.videoUrl">
                    <span class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-ink-950/70 px-2 py-1 text-xs font-medium text-white backdrop-blur">
                      <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z" /></svg>
                      Video
                    </span>
                  </template>
                  <span class="absolute bottom-3 left-3 right-3 text-white">
                    <span class="block font-display text-lg font-bold leading-tight" x-text="a.tour"></span>
                    <span class="mt-0.5 block text-xs text-white/85">
                      <span x-text="a.month + ' ' + a.year"></span> ·
                      <span x-text="a.country"></span> ·
                      <span x-text="a.photos.length + ' photos'"></span>
                    </span>
                  </span>
                  <span class="absolute inset-0 grid place-items-center opacity-0 transition-opacity group-hover:opacity-100">
                    <span class="grid size-12 place-items-center rounded-full bg-white/90 text-ink-900">
                      <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 0 5.25-5.25M3.75 12l5.25 5.25M20.25 6.75v10.5" />
                      </svg>
                    </span>
                  </span>
                </button>
                <div class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm">
                  <button type="button" @click="open(a)" class="font-semibold text-brand-600 hover:text-brand-700">View album</button>
                  <a
                    x-show="a.videoUrl"
                    :href="a.videoUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1 text-ink-500 hover:text-ink-900"
                  >
                    Watch recap
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                  </a>
                </div>
              </article>
            </template>
          </div>

          <div x-show="hasMore" class="mt-10 flex justify-center">
            <button type="button" @click="more()" class="btn btn-outline btn-lg">
              Load more albums
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
              </svg>
            </button>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           LIGHTBOX
           ================================================================== -->
      <div
        x-show="activeAlbum"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="close()"
        @keydown.arrow-right.window="next()"
        @keydown.arrow-left.window="prev()"
        class="fixed inset-0 z-70 flex flex-col bg-ink-950/95 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-label="Photo album"
      >
        <div class="flex items-start justify-between gap-4 p-4 text-white sm:p-6">
          <div x-show="activeAlbum">
            <p class="font-display text-base font-bold" x-text="activeAlbum?.tour"></p>
            <p class="mt-0.5 text-xs text-white/70">
              <span x-text="activeAlbum?.month + ' ' + activeAlbum?.year"></span> ·
              <span x-text="(photoIndex + 1) + ' / ' + activeAlbum?.photos.length"></span>
            </p>
          </div>
          <div class="flex items-center gap-2">
            <a
              x-show="activeAlbum?.tourSlug"
              :href="'/one-day-tours?tour=' + activeAlbum?.tourSlug"
              class="hidden rounded-full border border-white/25 px-3 py-1.5 text-sm font-medium text-white hover:bg-white/10 sm:inline-block"
            >
              View this tour
            </a>
            <button type="button" @click="close()" aria-label="Close" class="grid size-10 place-items-center rounded-full text-white hover:bg-white/10">
              <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="relative flex flex-1 items-center justify-center px-4 pb-6" @click.self="close()">
          <button
            type="button"
            @click="prev()"
            aria-label="Previous photo"
            class="absolute left-2 grid size-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:left-6"
          >
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>

          <figure class="flex max-h-full flex-col items-center" @click.self="close()">
            <img
              :src="currentPhoto?.src"
              :alt="currentPhoto?.caption"
              class="max-h-[74vh] w-auto max-w-full rounded-2xl object-contain shadow-2xl"
            />
            <figcaption x-show="currentPhoto?.caption" class="mt-3 max-w-xl text-center text-sm text-white/80" x-text="currentPhoto?.caption"></figcaption>
          </figure>

          <button
            type="button"
            @click="next()"
            aria-label="Next photo"
            class="absolute right-2 grid size-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:right-6"
          >
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>
      </div>

      <!-- ==================================================================
           HELP CTA BAND
           ================================================================== -->
      <section class="bg-brand-600 py-14 text-white">
        <div class="container-page flex flex-col items-center gap-6 text-center lg:flex-row lg:justify-between lg:text-left">
          <div class="flex items-center gap-4">
            <span class="hidden size-14 shrink-0 place-items-center rounded-full bg-white/15 sm:grid">
              <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
              </svg>
            </span>
            <div>
              <p class="font-display text-xl font-extrabold sm:text-2xl">Travelled with us? We'd love your photos.</p>
              <p class="mt-1 text-sm text-white/85">Tag <span class="font-semibold">@dreamtour</span> or send them in — the best ones make the gallery.</p>
            </div>
          </div>
          <div class="flex flex-col items-center gap-3 sm:flex-row">
            <a href="mailto:{{ $siteSettings->get('photos_email') }}" class="btn btn-white">Send your photos</a>
            <a href="//one-day-tours" class="btn btn-ghost">Browse tours</a>
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


