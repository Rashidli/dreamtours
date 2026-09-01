<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Private Tours — DreamTour</title>
    <meta name="description" content="Custom private tours across Ontario, Québec and beyond — your own group, vehicle and guide. See tour ideas and request a free quote." />

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

    <main id="main" x-data="privateRequest">
      <!-- ==================================================================
           PAGE HERO + BREADCRUMB  (Private Tours — §4)
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
            <span class="font-medium text-white">Private Tours</span>
          </nav>

          <div class="mt-6 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div>
              <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>

              <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                <li class="flex items-center gap-2.5 text-sm text-ink-100">
                  <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" /></svg>
                  Custom itinerary
                </li>
                <li class="flex items-center gap-2.5 text-sm text-ink-100">
                  <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" /></svg>
                  Private comfort
                </li>
                <li class="flex items-center gap-2.5 text-sm text-ink-100">
                  <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                  Flexible schedule
                </li>
                <li class="flex items-center gap-2.5 text-sm text-ink-100">
                  <svg class="size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                  Personalised service
                </li>
              </ul>

              <div class="mt-8 flex flex-wrap gap-3">
                <a href="#request" class="btn btn-primary">Plan your private tour</a>
                <a href="#ideas" class="btn btn-ghost">See tour ideas</a>
              </div>
            </div>

            <div class="relative mx-auto hidden h-[420px] w-full max-w-md lg:block" aria-hidden="true">
              <img src="{{ $sections->get('hero')?->image ?: 'https://images.unsplash.com/photo-1522199755839-a2bacb67c546?q=80&w=700&auto=format&fit=crop' }}" alt="" class="absolute left-0 top-2 w-52 -rotate-6 rounded-2xl object-cover shadow-2xl ring-4 ring-white" style="aspect-ratio: 3 / 4" />
              <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=700&auto=format&fit=crop" alt="" class="absolute right-0 top-10 w-52 rotate-3 rounded-2xl object-cover shadow-2xl ring-4 ring-white" style="aspect-ratio: 4 / 3" />
              <img src="https://images.unsplash.com/photo-1489447068241-b3490214e879?q=80&w=700&auto=format&fit=crop" alt="" class="absolute bottom-0 left-6 w-56 -rotate-2 rounded-2xl object-cover shadow-2xl ring-4 ring-white" style="aspect-ratio: 4 / 3" />
              <span class="absolute -bottom-4 right-0 grid size-28 place-items-center rounded-full bg-brand-600 p-3 text-center font-display text-[11px] font-bold leading-tight text-white shadow-xl">
                Families · Friends · Corporate · Special occasions
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           WHY CHOOSE PRIVATE
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Why Choose Private Tours</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Travel On Your Terms, In Total Comfort
            </h2>
            <p class="mt-3 text-ink-500">All the planning and driving handled — just your group, going where you want to go.</p>
          </div>

          <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Private &amp; exclusive</h3>
              <p class="mt-1 text-sm text-ink-500">Your group, your vehicle, your experience — no one else on board.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Flexible &amp; customisable</h3>
              <p class="mt-1 text-sm text-ink-500">Choose the destinations, the duration and the pick-up time.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" /></svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Comfortable vehicles</h3>
              <p class="mt-1 text-sm text-ink-500">Modern, clean minivans, minibuses and coaches with premium seating.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Safe &amp; reliable</h3>
              <p class="mt-1 text-sm text-ink-500">Professional, licensed drivers and the same trusted service as our group tours.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           POPULAR PRIVATE TOUR IDEAS
           ================================================================== -->
      <section id="ideas" class="scroll-mt-24 bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Popular Private Tour Ideas</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Inspiration For Your Next Adventure
            </h2>
            <p class="mt-3 text-ink-500">Start from one of these or bring your own idea — every private tour is built from scratch.</p>
          </div>

          <div x-data="{ ideas: window.DreamTourData.privateTours.ideas }" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="idea in ideas" :key="idea.id">
              <article class="group flex flex-col overflow-hidden rounded-4xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover">
                <div class="relative">
                  <img :src="idea.image" :alt="idea.title" class="h-48 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                  <span class="absolute bottom-3 left-3 rounded-full bg-ink-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur" x-text="idea.duration"></span>
                </div>
                <div class="flex flex-1 flex-col p-5">
                  <h3 class="font-display text-lg font-bold text-ink-900" x-text="idea.title"></h3>
                  <p class="mt-2 flex-1 text-sm text-ink-500" x-text="idea.summary"></p>
                  <ul class="mt-3 flex flex-wrap gap-1.5">
                    <template x-for="tag in idea.tags" :key="tag">
                      <li class="rounded-full bg-sand-100 px-2.5 py-1 text-xs font-medium text-ink-600" x-text="tag"></li>
                    </template>
                  </ul>
                  <button type="button" @click="prefill(idea.title)" class="btn btn-primary btn-sm mt-5 w-full">Request a quote</button>
                </div>
              </article>
            </template>
          </div>

          <div class="mt-10 text-center">
            <a href="#request" class="btn btn-outline">Have a different idea? Tell us</a>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           HOW IT WORKS
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">How It Works</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Simple Steps to Your Perfect Trip</h2>
          </div>
          <ol x-data="{ steps: window.DreamTourData.privateTours.steps }" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="(s, i) in steps" :key="i">
              <li class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
                <span class="grid size-10 place-items-center rounded-full bg-brand-600 font-display text-sm font-extrabold text-white" x-text="i + 1"></span>
                <h3 class="mt-4 font-display text-lg font-bold text-ink-900" x-text="s.title"></h3>
                <p class="mt-1 text-sm text-ink-500" x-text="s.text"></p>
              </li>
            </template>
          </ol>
        </div>
      </section>

      <!-- ==================================================================
           REQUEST FORM
           ================================================================== -->
      <section id="request" class="scroll-mt-24 bg-ink-900 py-16 text-white lg:py-24">
        <div class="container-page grid gap-12 lg:grid-cols-2 lg:gap-16">
          <div>
            <p class="eyebrow text-brand-400">Plan Your Private Tour</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-white sm:text-4xl">
              Tell Us What You Have in Mind
            </h2>
            <p class="mt-3 max-w-md text-ink-200">
              Send the basics and a DreamTour planner will come back with an
              itinerary and a quote — usually within one business day. No cost,
              no commitment.
            </p>

            <ul class="mt-8 space-y-3 text-sm text-ink-200">
              <li class="flex gap-3">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                Groups from 2 to 56 — families, friends, clubs and companies.
              </li>
              <li class="flex gap-3">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                Half-day, full-day or multi-day, anywhere we run group tours.
              </li>
              <li class="flex gap-3">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                One price for the whole vehicle — split it however you like.
              </li>
            </ul>

            <div class="mt-8 space-y-2 text-sm">
              <a href="tel:{{ $siteSettings->get('phone_link') }}" class="flex items-center gap-3 font-medium text-white hover:text-brand-400">
                <svg class="size-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                {{ $siteSettings->get('phone') }}
              </a>
              <a href="https://wa.me/{{ $siteSettings->get('whatsapp') }}" target="_blank" rel="noopener" class="flex items-center gap-3 font-medium text-white hover:text-brand-400">
                <svg class="size-5 text-brand-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.3-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2Zm5.8 14.06c-.24.68-1.42 1.3-1.95 1.35-.5.05-1.13.24-3.68-.77-3.1-1.22-5.08-4.4-5.24-4.6-.15-.2-1.25-1.66-1.25-3.17 0-1.5.79-2.24 1.07-2.55.28-.31.6-.38.8-.38.2 0 .4 0 .58.01.19.01.44-.07.68.52.24.6.83 2.07.9 2.22.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.07 1.31 2.36 1.46.29.15.46.12.63-.07.17-.2.73-.85.93-1.14.2-.29.39-.24.66-.15.27.1 1.7.8 1.99.95.29.15.48.22.55.34.07.12.07.71-.17 1.39Z" /></svg>
                Message on WhatsApp
              </a>
            </div>
          </div>

          <form
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
              <p>Thanks — your private tour request has been received. A DreamTour planner will contact you with ideas and a quote.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="p-name" class="mb-1 block text-sm font-medium text-ink-700">Full name</label>
                <input id="p-name" type="text" x-model="form.name" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="p-phone" class="mb-1 block text-sm font-medium text-ink-700">Phone / WhatsApp</label>
                <input id="p-phone" type="tel" x-model="form.phone" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="p-email" class="mb-1 block text-sm font-medium text-ink-700">Email</label>
                <input id="p-email" type="email" x-model="form.email" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="p-size" class="mb-1 block text-sm font-medium text-ink-700">Group size</label>
                <input id="p-size" type="number" min="2" max="56" x-model="form.groupSize" placeholder="e.g. 8" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="p-occasion" class="mb-1 block text-sm font-medium text-ink-700">Occasion</label>
                <select id="p-occasion" x-model="form.occasion" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="">Select…</option>
                  <template x-for="o in occasions" :key="o"><option :value="o" x-text="o"></option></template>
                </select>
              </div>
              <div>
                <label for="p-vehicle" class="mb-1 block text-sm font-medium text-ink-700">Vehicle preference</label>
                <select id="p-vehicle" x-model="form.vehicle" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="">Select…</option>
                  <template x-for="v in vehicles" :key="v"><option :value="v" x-text="v"></option></template>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label for="p-idea" class="mb-1 block text-sm font-medium text-ink-700">Destination or tour idea</label>
                <input id="p-idea" type="text" x-model="form.idea" placeholder="e.g. Niagara Falls, or a wine-country day" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div class="sm:col-span-2">
                <label for="p-dates" class="mb-1 block text-sm font-medium text-ink-700">Preferred dates</label>
                <input id="p-dates" type="text" x-model="form.dates" placeholder="e.g. a Saturday in October, or flexible" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div class="sm:col-span-2">
                <label for="p-msg" class="mb-1 block text-sm font-medium text-ink-700">Anything else? <span class="font-normal text-ink-400">(optional)</span></label>
                <textarea id="p-msg" rows="3" x-model="form.message" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
              </div>
            </div>

            <button type="submit" class="btn btn-primary mt-5 w-full">Request My Quote</button>
            <p class="mt-3 text-center text-xs text-ink-400">Sending this doesn't book anything — it just starts the conversation.</p>
          </form>
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


