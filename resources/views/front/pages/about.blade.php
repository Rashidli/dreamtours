<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>About Us — DreamTour</title>
    <meta name="description" content="DreamTour is a registered Ontario tour company running day trips, multi-day tours, cruises and trips abroad. Meet the team and see our accreditations." />

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

    <main id="main">
      <!-- ==================================================================
           PAGE HERO + BREADCRUMB  (About Us — §15)
           ================================================================== -->
      <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <img
          src="{{ $sections->get('hero')?->background_image ?: 'https://images.unsplash.com/photo-1517090504586-fde19ea6066f?q=80&w=1920&auto=format&fit=crop' }}"
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
            <span class="font-medium text-white">About Us</span>
          </nav>

          <div class="mt-6 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div>
              <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>

              <ul class="mt-8 grid gap-5 sm:grid-cols-2">
                <li class="flex gap-3">
                  <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                  </svg>
                  <span>
                    <span class="block font-display text-sm font-bold text-white">Trusted &amp; reliable</span>
                    <span class="mt-0.5 block text-sm text-ink-200">Registered, insured and safety-first on every departure.</span>
                  </span>
                </li>
                <li class="flex gap-3">
                  <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                  </svg>
                  <span>
                    <span class="block font-display text-sm font-bold text-white">Local experts</span>
                    <span class="mt-0.5 block text-sm text-ink-200">Guides who actually know the routes, the stops and the timing.</span>
                  </span>
                </li>
                <li class="flex gap-3">
                  <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                  </svg>
                  <span>
                    <span class="block font-display text-sm font-bold text-white">Memorable experiences</span>
                    <span class="mt-0.5 block text-sm text-ink-200">Every trip is planned to leave you with a story worth telling.</span>
                  </span>
                </li>
                <li class="flex gap-3">
                  <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                  </svg>
                  <span>
                    <span class="block font-display text-sm font-bold text-white">Quality service</span>
                    <span class="mt-0.5 block text-sm text-ink-200">A real person looks after your group from booking to drop-off.</span>
                  </span>
                </li>
              </ul>
            </div>

            <div class="relative hidden h-[420px] lg:block" aria-hidden="true">
              <img src="{{ $sections->get('hero')?->image ?: 'https://images.unsplash.com/photo-1522199755839-a2bacb67c546?q=80&w=700&auto=format&fit=crop' }}" alt="" class="absolute left-2 top-2 w-56 -rotate-6 rounded-2xl object-cover shadow-2xl ring-4 ring-white" style="aspect-ratio: 3 / 4" />
              <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?q=80&w=700&auto=format&fit=crop" alt="" class="absolute right-0 top-8 w-56 rotate-3 rounded-2xl object-cover shadow-2xl ring-4 ring-white" style="aspect-ratio: 4 / 3" />
              <img src="https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?q=80&w=700&auto=format&fit=crop" alt="" class="absolute bottom-0 right-8 w-60 -rotate-2 rounded-2xl object-cover shadow-2xl ring-4 ring-white" style="aspect-ratio: 4 / 3" />
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           OUR STORY + MISSION + STATS
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page grid gap-14 lg:grid-cols-2 lg:items-center lg:gap-16">
          <div class="relative">
            <img
              src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?q=80&w=1100&auto=format&fit=crop"
              alt="The DreamTour team on a tour"
              class="w-full rounded-4xl object-cover shadow-card"
              style="aspect-ratio: 5 / 4"
              loading="lazy"
            />
            <div class="absolute -bottom-5 left-5 flex items-center gap-3 rounded-2xl bg-ink-900 px-5 py-4 text-white shadow-xl">
              <svg class="size-6 text-brand-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
              </svg>
              <span class="font-display text-sm font-bold leading-tight">
                Driven by passion.<br />Focused on you.
              </span>
            </div>
          </div>

          <div>
            <p class="eyebrow">Our Story</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Built on Passion. Driven by People.
            </h2>
            <p class="mt-4 text-ink-600">
              DreamTour started in 2021 with a single coach and a short list of
              day trips out of Toronto. The trips sold out, the reviews came in,
              and travellers kept asking where we were going next.
            </p>
            <p class="mt-3 text-ink-600">
              Today we run day trips, multi-day tours, cruises and trips abroad —
              but the idea hasn't changed. We take care of the planning, the
              driving and the details so you can just enjoy the trip.
            </p>
            <div class="mt-6 rounded-2xl border-l-4 border-brand-500 bg-sand-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Our mission</p>
              <p class="mt-1 text-ink-700">
                To make group travel feel personal — easy to book, comfortable to
                take, and worth remembering.
              </p>
            </div>
          </div>
        </div>

        <div class="container-page mt-16">
          <dl x-data="{ stats: window.DreamTourData.about.stats }" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="s in stats" :key="s.label">
              <div class="rounded-4xl bg-sand-50 p-6 text-center ring-1 ring-ink-900/5">
                <dt class="sr-only" x-text="s.label"></dt>
                <dd>
                  <span class="block font-display text-3xl font-extrabold text-brand-600" x-text="s.value"></span>
                  <span class="mt-1 block text-sm text-ink-500" x-text="s.label"></span>
                </dd>
              </div>
            </template>
          </dl>
        </div>
      </section>

      <!-- ==================================================================
           WHY DREAMTOUR  (shared advantages band)
           ================================================================== -->
      <section class="bg-ink-900 py-16 text-white lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow text-brand-400">Why Travel With DreamTour</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-white sm:text-4xl">More Than Just a Tour Company</h2>
            <p class="mt-3 text-ink-200">We handle the planning, the driving and the details — you just show up and enjoy the trip.</p>
          </div>
          <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Comfortable Travel</h3>
              <p class="mt-1 text-sm text-ink-200">Modern vehicles with premium seating and air conditioning for a relaxing journey.</p>
            </div>
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Curated Itineraries</h3>
              <p class="mt-1 text-sm text-ink-200">Handpicked destinations and experiences timed for the best day out.</p>
            </div>
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Great Value</h3>
              <p class="mt-1 text-sm text-ink-200">Competitive pricing with no hidden fees and generous inclusions.</p>
            </div>
            <div>
              <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-brand-400">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-white">Friendly Support</h3>
              <p class="mt-1 text-sm text-ink-200">We're here before, during and after your trip — a real person, every time.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           MEET THE TEAM
           ================================================================== -->
      <section class="bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Meet the Team</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              The People Behind Your Adventures
            </h2>
            <p class="mt-3 text-ink-500">A small team that plans every trip and, more often than not, comes along for it.</p>
          </div>

          <div x-data="{ team: window.DreamTourData.team }" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="m in team" :key="m.id">
              <figure class="flex flex-col rounded-4xl bg-white p-5 text-center ring-1 ring-ink-900/5">
                <img :src="m.photo" :alt="m.name" class="mx-auto size-28 rounded-full object-cover" loading="lazy" />
                <figcaption class="mt-4 flex flex-1 flex-col">
                  <p class="font-display text-lg font-bold text-ink-900" x-text="m.name"></p>
                  <p class="text-sm font-semibold text-brand-600" x-text="m.role"></p>
                  <p class="mt-2 flex-1 text-sm text-ink-500" x-text="m.bio"></p>
                  <div class="mt-4 flex justify-center gap-2">
                    <a :href="m.socials.facebook" target="_blank" rel="noopener" :aria-label="m.name + ' on Facebook'" class="grid size-9 place-items-center rounded-full bg-ink-50 text-ink-500 transition hover:bg-brand-600 hover:text-white">
                      <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.99 3.657 9.128 8.438 9.878v-6.987H7.898V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.987C18.343 21.128 22 16.99 22 12Z" /></svg>
                    </a>
                    <a :href="m.socials.instagram" target="_blank" rel="noopener" :aria-label="m.name + ' on Instagram'" class="grid size-9 place-items-center rounded-full bg-ink-50 text-ink-500 transition hover:bg-brand-600 hover:text-white">
                      <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.72 3.72 0 0 1-1.38-.9 3.72 3.72 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.21 15.58 2.2 15.2 2.2 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.21 8.8 2.2 12 2.2Zm0 3.87A5.93 5.93 0 1 0 12 17.93 5.93 5.93 0 0 0 12 6.07Zm0 1.8a4.13 4.13 0 1 1 0 8.26 4.13 4.13 0 0 1 0-8.26Zm6.03-3.04a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3Z" /></svg>
                    </a>
                    <a :href="'mailto:' + m.socials.email" :aria-label="'Email ' + m.name" class="grid size-9 place-items-center rounded-full bg-ink-50 text-ink-500 transition hover:bg-brand-600 hover:text-white">
                      <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    </a>
                  </div>
                </figcaption>
              </figure>
            </template>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           LICENCES & PARTNERS
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Licensed &amp; Accredited</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Book With Confidence</h2>
            <p class="mt-3 text-ink-500">
              DreamTour is a registered Ontario travel retailer. Payments made for
              your trip are protected under the Ontario Travel Industry
              Compensation Fund.
            </p>
          </div>

          <div x-data="{ partners: window.DreamTourData.about.partners }" class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="p in partners" :key="p.name">
              <div class="rounded-2xl border border-ink-100 p-6 text-center">
                <p class="font-display text-2xl font-extrabold text-ink-900" x-text="p.name"></p>
                <p class="mt-1 text-sm text-ink-500" x-text="p.full"></p>
                <p class="mt-2 text-xs font-medium text-brand-600" x-text="p.note"></p>
              </div>
            </template>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           TRAVELLER REVIEWS
           ================================================================== -->
      <section class="bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">What Our Travellers Say</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Real Experiences. Happy Travellers.
            </h2>
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

          <div x-data="{ reviews: window.DreamTourData.reviews }" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="r in reviews" :key="r.id">
              <figure class="flex h-full flex-col rounded-4xl bg-white p-6 ring-1 ring-ink-900/5">
                <div class="flex gap-0.5" aria-hidden="true">
                  <template x-for="n in 5" :key="n">
                    <svg class="size-4" :class="n <= r.rating ? 'text-brand-500' : 'text-ink-200'" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.59a1 1 0 0 0-1.18 0l-3.56 2.59c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.12L2.1 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69L9.05 2.93Z" />
                    </svg>
                  </template>
                </div>
                <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-ink-700">
                  <span x-text="'“' + r.quote + '”'"></span>
                </blockquote>
                <figcaption class="mt-5 flex items-center gap-3 border-t border-ink-100 pt-4">
                  <span
                    class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-100 font-display text-sm font-bold text-brand-700"
                    x-text="r.name.split(' ').map((w) => w[0]).join('')"
                  ></span>
                  <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink-900" x-text="r.name"></span>
                    <span class="block truncate text-xs text-ink-500" x-text="r.tour"></span>
                  </span>
                </figcaption>
              </figure>
            </template>
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
              <p class="font-display text-xl font-extrabold sm:text-2xl">Have questions? We're here to help.</p>
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


