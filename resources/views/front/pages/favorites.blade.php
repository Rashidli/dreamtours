<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $cmsPage->meta_title ?: ($cmsPage->title ?: 'My Favourites — DreamTour') }}</title>
  @include('front.partials.favicon')
    <meta name="description" content="{{ $cmsPage->meta_description ?: 'The tours and cruises you saved on this device.' }}" />
    @if($cmsPage->meta_keywords)<meta name="keywords" content="{{ $cmsPage->meta_keywords }}" />@endif
    <meta name="robots" content="noindex" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/dreamtour/dist/main.css?v={{ @filemtime(public_path('dreamtour/dist/main.css')) }}" />

    @include('front.partials.data')
    <script defer src="/dreamtour/src/js/app.js?v={{ @filemtime(public_path('dreamtour/src/js/app.js')) }}"></script>
    <script defer src="/dreamtour/src/js/vendor/alpine.min.js"></script>
  </head>

  <body class="min-h-screen bg-white text-ink-800 antialiased">
    <a href="#main" class="skip-link">Skip to content</a>

    @include('front.partials.header')

    <main id="main" x-data="favouritesPage">
      <section class="bg-ink-900 text-white">
        <div class="container-page py-14 lg:py-20">
          <nav aria-label="Breadcrumb" class="text-sm text-ink-300">
            <a href="/" class="hover:text-white">Home</a> <span aria-hidden="true">/</span> <span class="text-white">Favourites</span>
          </nav>
          <h1 class="mt-3 font-display text-4xl font-extrabold sm:text-5xl text-white">{{ $sections->get('hero')?->title ?: 'My Favourites' }}</h1>
          <p class="mt-4 max-w-2xl text-ink-100">{{ $sections->get('hero')?->subtitle ?: 'The tours and cruises you saved with the heart button. They are kept in this browser on this device — they are not tied to an account.' }}</p>
        </div>
      </section>

      <section class="py-14 lg:py-20">
        <div class="container-page">
          <!-- Empty state -->
          <template x-if="total === 0">
            <div class="mx-auto max-w-xl rounded-4xl bg-sand-50 p-10 text-center ring-1 ring-ink-900/5">
              <span class="mx-auto grid size-14 place-items-center rounded-full bg-white text-brand-600 shadow-sm">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </span>
              <h2 class="mt-5 font-display text-2xl font-bold text-ink-900">Nothing saved yet</h2>
              <p class="mt-2 text-ink-500">
                Tap the heart on any tour or cruise and it will show up here.
              </p>
              <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="/one-day-tours" class="btn btn-primary">Browse tours</a>
                <a href="/cruises" class="btn btn-outline">Browse cruises</a>
              </div>
            </div>
          </template>

          <template x-if="total > 0">
            <div>
              <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-500">
                  <span x-text="total"></span> <span x-text="total === 1 ? 'item' : 'items'"></span> saved
                  <template x-if="unavailable > 0">
                    <span class="text-ink-400">
                      (<span x-text="unavailable"></span> no longer available)
                    </span>
                  </template>
                </p>
                <button type="button" @click="clear()" class="text-sm font-semibold text-ink-500 underline hover:text-brand-600">
                  Clear all
                </button>
              </div>

              <!-- Saved tours -->
              <template x-if="tours.length">
                <div class="mt-8">
                  <h2 class="font-display text-2xl font-bold text-ink-900">Tours</h2>
                  <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="t in tours" :key="t.id">
                      <article class="group flex flex-col overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-ink-900/5">
                        <div class="relative">
                          <img :src="t.image" :alt="t.name" class="h-48 w-full object-cover" loading="lazy" />
                          <template x-if="t.label">
                            <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm" :class="badgeClass(t.label)" x-text="t.label"></span>
                          </template>
                          <button
                            type="button"
                            @click="$store.wishlist.toggle(t.id, t.name)"
                            :aria-label="'Remove ' + t.name + ' from favourites'"
                            class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 text-brand-600 backdrop-blur transition hover:text-ink-900"
                          >
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                          </button>
                          <span class="absolute bottom-3 left-3 rounded-full bg-ink-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur" x-text="t.duration"></span>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                          <h3 class="font-display text-lg font-bold text-ink-900" x-text="t.name"></h3>
                          <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-500">
                            <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <span x-text="t.destination"></span>
                          </p>
                          <dl class="mt-4 grid grid-cols-1 gap-y-2 text-sm text-ink-600">
                            <div class="flex items-center gap-2">
                              <dt class="sr-only">Departure date</dt>
                              <dd x-text="t.date"></dd>
                            </div>
                            <div class="flex items-center gap-2" :class="t.seatsLeft <= 5 && 'font-medium text-brand-600'">
                              <dt class="sr-only">Availability</dt>
                              <dd x-text="seatsLabel(t)"></dd>
                            </div>
                          </dl>
                          <div class="mt-auto pt-5">
                            <div class="flex items-baseline gap-1.5 border-t border-ink-100 pt-4">
                              <template x-if="t.previousPrice">
                                <span class="text-sm text-ink-400 line-through" x-text="'CA$' + t.previousPrice"></span>
                              </template>
                              <span class="text-xs text-ink-400">From</span>
                              <span class="font-display text-xl font-extrabold text-ink-900" x-text="'CA$' + t.price"></span>
                              <span class="text-xs text-ink-400">/ person</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                              <a :href="'/tours/' + t.slug" class="btn btn-outline btn-sm w-full">View Details</a>
                              <template x-if="t.seatsLeft > 0">
                                <a :href="'/tours/' + t.slug + '#booking'" class="btn btn-primary btn-sm w-full">Book Now</a>
                              </template>
                              <template x-if="t.seatsLeft === 0">
                                <span class="btn btn-sm w-full cursor-not-allowed bg-ink-100 text-ink-400">Sold Out</span>
                              </template>
                            </div>
                          </div>
                        </div>
                      </article>
                    </template>
                  </div>
                </div>
              </template>

              <!-- Saved cruises -->
              <template x-if="cruises.length">
                <div class="mt-12">
                  <h2 class="font-display text-2xl font-bold text-ink-900">Cruises</h2>
                  <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="c in cruises" :key="c.id">
                      <article class="group flex flex-col overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-ink-900/5">
                        <div class="relative">
                          <img :src="c.image" :alt="c.name" class="h-48 w-full object-cover" loading="lazy" />
                          <button
                            type="button"
                            @click="$store.wishlist.toggle('cruise-' + c.id, c.name)"
                            :aria-label="'Remove ' + c.name + ' from favourites'"
                            class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 text-brand-600 backdrop-blur transition hover:text-ink-900"
                          >
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                          </button>
                          <span class="absolute bottom-3 left-3 rounded-full bg-ink-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur" x-text="c.duration"></span>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                          <p class="text-xs font-semibold uppercase tracking-wider text-brand-600" x-text="c.line"></p>
                          <h3 class="mt-1 font-display text-lg font-bold text-ink-900" x-text="c.name"></h3>
                          <p class="mt-1 text-sm text-ink-500" x-text="c.region"></p>
                          <dl class="mt-4 text-sm text-ink-600">
                            <dt class="sr-only">Next sailing</dt>
                            <dd x-text="c.date"></dd>
                          </dl>
                          <div class="mt-auto pt-5">
                            <div class="flex items-baseline gap-1.5 border-t border-ink-100 pt-4">
                              <span class="text-xs text-ink-400">From</span>
                              <span class="font-display text-xl font-extrabold text-ink-900" x-text="'CA$' + c.price"></span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                              <a :href="'/tours/' + c.slug" class="btn btn-outline btn-sm w-full">View Details</a>
                              <a :href="'/tours/' + c.slug + '#booking'" class="btn btn-primary btn-sm w-full">Book Now</a>
                            </div>
                          </div>
                        </div>
                      </article>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </template>
        </div>
      </section>
    </main>

    @include('front.partials.footer')
  </body>
</html>
