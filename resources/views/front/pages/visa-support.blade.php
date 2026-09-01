<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Visa Support — DreamTour</title>
    <meta name="description" content="Visa document preparation and application support for Schengen, the UK, US, China, Japan and more — checklists, form prep, review and appointment booking. Approval is never guaranteed." />

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

    <main id="main" x-data="visaRequest">
      <!-- ==================================================================
           PAGE HERO + BREADCRUMB  (Visa Support — §17)
           ================================================================== -->
      <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <img
          src="{{ $sections->get('hero')?->background_image ?: 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?q=80&w=1920&auto=format&fit=crop' }}"
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
            <span class="font-medium text-white">Visa Support</span>
          </nav>

          <div class="mt-6 max-w-2xl">
            <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
              <a href="#request" class="btn btn-primary">Start a request</a>
              <a href="#countries" class="btn btn-ghost">See countries &amp; fees</a>
            </div>
          </div>
        </div>
      </section>

      <!-- Short disclaimer strip -->
      <div class="bg-sand-100">
        <div class="container-page flex items-start gap-3 py-4 text-sm text-ink-700">
          <svg class="mt-0.5 size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
          </svg>
          <p>
            DreamTour provides document preparation and guidance only. The
            decision to grant or refuse a visa rests entirely with the relevant
            consulate or embassy — <span class="font-semibold text-ink-900">approval is never guaranteed</span>.
          </p>
        </div>
      </div>

      <!-- ==================================================================
           WHAT'S INCLUDED
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">What You Get</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              What's Included in Visa Support
            </h2>
            <p class="mt-3 text-ink-500">
              One flat service fee per applicant, per destination. Consular and
              visa-centre fees are billed separately at cost.
            </p>
          </div>

          <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Eligibility check</h3>
              <p class="mt-1 text-sm text-ink-500">We confirm which visa you need for your passport and residency before you pay anything.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Personalised checklist</h3>
              <p class="mt-1 text-sm text-ink-500">A document list built for your exact situation — nationality, job, family and trip.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Form preparation</h3>
              <p class="mt-1 text-sm text-ink-500">We complete the application and online portals; you review and sign.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Full application review</h3>
              <p class="mt-1 text-sm text-ink-500">Every page and translation is checked for consistency before it's submitted.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Appointment booking</h3>
              <p class="mt-1 text-sm text-ink-500">Biometrics and interview slots booked for you, with a prep briefing beforehand.</p>
            </div>
            <div class="rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5">
              <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                </svg>
              </span>
              <h3 class="mt-4 font-display text-lg font-bold text-ink-900">Invitation &amp; cover letters</h3>
              <p class="mt-1 text-sm text-ink-500">Cover letters and, where allowed, invitation-letter guidance drafted for you.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           HOW IT WORKS
           ================================================================== -->
      <section class="bg-ink-900 py-16 text-white lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow text-brand-400">The Process</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-white sm:text-4xl">How Visa Support Works</h2>
            <p class="mt-3 text-ink-200">Four steps from first enquiry to a decision — most of the work is on us.</p>
          </div>
          <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="(s, i) in window.DreamTourData.visa.steps" :key="i">
              <li class="rounded-4xl bg-white/5 p-6 ring-1 ring-white/10">
                <span class="grid size-10 place-items-center rounded-full bg-brand-600 font-display text-sm font-extrabold text-white" x-text="i + 1"></span>
                <h3 class="mt-4 font-display text-lg font-bold text-white" x-text="s.title"></h3>
                <p class="mt-1 text-sm text-ink-200" x-text="s.text"></p>
              </li>
            </template>
          </ol>
        </div>
      </section>

      <!-- ==================================================================
           COUNTRIES & FEES
           ================================================================== -->
      <section id="countries" class="scroll-mt-24 bg-sand-50 py-16 lg:py-24">
        <div class="container-page">
          <div class="max-w-2xl">
            <p class="eyebrow">Where We Help</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Countries We Assist With
            </h2>
            <p class="mt-3 text-ink-500">
              Processing times are the consulate's current estimates, not a
              promise. Don't see your destination? Ask us — the list keeps growing.
            </p>
          </div>

          <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="s in window.DreamTourData.visa.services" :key="s.country">
              <article class="flex flex-col rounded-4xl bg-white p-6 ring-1 ring-ink-900/5">
                <div class="flex items-center justify-between gap-3">
                  <h3 class="font-display text-lg font-bold text-ink-900" x-text="s.country"></h3>
                  <span class="rounded-full bg-sand-100 px-2.5 py-1 text-xs font-medium text-ink-600" x-text="s.region"></span>
                </div>
                <p class="mt-2 flex-1 text-sm text-ink-500" x-text="s.summary"></p>
                <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-ink-100 pt-4 text-sm">
                  <div>
                    <dt class="text-xs text-ink-400">Processing</dt>
                    <dd class="mt-0.5 font-medium text-ink-800" x-text="s.processing"></dd>
                  </div>
                  <div>
                    <dt class="text-xs text-ink-400">Service fee</dt>
                    <dd class="mt-0.5 font-display font-extrabold text-ink-900" x-text="'$' + s.fee"></dd>
                  </div>
                </dl>
                <button
                  type="button"
                  @click="form.destination = s.country; document.getElementById('request').scrollIntoView({ behavior: 'smooth' })"
                  class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700"
                >
                  Request this visa
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                  </svg>
                </button>
              </article>
            </template>
          </div>
          <p class="mt-6 text-xs text-ink-400">
            Fees shown are per applicant and cover our service only. Government
            consular fees, visa-centre (VAC) fees and courier charges are extra
            and listed on your quote.
          </p>
        </div>
      </section>

      <!-- ==================================================================
           DOCUMENTS
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page lg:grid lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
          <div>
            <p class="eyebrow">The Checklist</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Documents You'll Usually Need
            </h2>
            <p class="mt-3 max-w-md text-ink-500">
              This is the common core. Your personalised checklist may add or
              drop items depending on your nationality, your status in Canada and
              the destination.
            </p>
          </div>
          <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:mt-0">
            <template x-for="(d, i) in window.DreamTourData.visa.documents" :key="i">
              <li class="flex gap-3 rounded-2xl border border-ink-100 p-4">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>
                  <span class="block text-sm font-semibold text-ink-900" x-text="d.name"></span>
                  <span class="mt-0.5 block text-sm text-ink-500" x-text="d.detail"></span>
                </span>
              </li>
            </template>
          </ul>
        </div>
      </section>

      <!-- ==================================================================
           FAQ
           ================================================================== -->
      <section id="faq" class="scroll-mt-24 bg-sand-50 py-16 lg:py-24">
        <div class="container-page max-w-3xl">
          <div class="text-center">
            <p class="eyebrow">Questions</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Visa Support FAQ</h2>
          </div>
          <dl x-data="{ open: 0 }" class="mt-10 divide-y divide-ink-100 overflow-hidden rounded-4xl bg-white ring-1 ring-ink-900/5">
            <template x-for="(f, i) in window.DreamTourData.visa.faqs" :key="i">
              <div class="p-5 sm:p-6">
                <dt>
                  <button
                    type="button"
                    @click="open = open === i ? null : i"
                    :aria-expanded="open === i"
                    class="flex w-full items-center justify-between gap-4 text-left"
                  >
                    <span class="font-display text-base font-bold text-ink-900" x-text="f.q"></span>
                    <svg class="size-5 shrink-0 text-brand-600 transition-transform" :class="open === i && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                  </button>
                </dt>
                <dd x-show="open === i" x-cloak x-transition.opacity class="mt-3 text-sm leading-relaxed text-ink-600" x-text="f.a"></dd>
              </div>
            </template>
          </dl>
        </div>
      </section>

      <!-- ==================================================================
           FULL DISCLAIMER
           ================================================================== -->
      <section class="bg-white py-12">
        <div class="container-page">
          <div class="rounded-4xl border border-brand-100 bg-brand-50/60 p-6 sm:p-8">
            <h2 class="flex items-center gap-2 font-display text-lg font-bold text-ink-900">
              <svg class="size-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
              </svg>
              Important — please read
            </h2>
            <ul class="mt-4 space-y-2 text-sm text-ink-600">
              <li>&bull; A visa is granted or refused solely by the government of the destination country. DreamTour has no influence over the outcome and <span class="font-semibold text-ink-900">cannot guarantee approval</span>.</li>
              <li>&bull; Our service fee pays for the preparation and review work. It is <span class="font-semibold text-ink-900">non-refundable if a visa is refused</span>, but there is no additional service charge to prepare one re-application within 60 days.</li>
              <li>&bull; Government consular fees, visa-application-centre fees and courier costs are set by the authorities, paid separately, and are non-refundable regardless of outcome.</li>
              <li>&bull; Processing times are estimates published by each consulate and can change without notice, especially in peak season.</li>
              <li>&bull; This service is document preparation and administrative assistance only. It is not legal or immigration advice, and we are not a licensed immigration consultancy.</li>
            </ul>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           APPLICATION FORM  (§17 — separate CRM request type)
           ================================================================== -->
      <section id="request" class="scroll-mt-24 bg-ink-900 py-16 text-white lg:py-24">
        <div class="container-page grid gap-12 lg:grid-cols-2 lg:gap-16">
          <div>
            <p class="eyebrow text-brand-400">Start a Request</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-white sm:text-4xl">
              Tell Us About Your Trip
            </h2>
            <p class="mt-3 max-w-md text-ink-200">
              Send the basics and a DreamTour advisor will reply within one
              business day with your document checklist and a full quote,
              including all government fees.
            </p>

            <ul class="mt-8 space-y-3 text-sm text-ink-200">
              <li class="flex gap-3">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                No payment until you've seen the checklist and approved the quote.
              </li>
              <li class="flex gap-3">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                One request can cover a whole family or group.
              </li>
              <li class="flex gap-3">
                <svg class="mt-0.5 size-5 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                Already booked a DreamTour trip? We'll match the paperwork to it.
              </li>
            </ul>

            <div class="mt-8 space-y-2 text-sm">
              <a href="tel:{{ $siteSettings->get('phone_link') }}" class="flex items-center gap-3 font-medium text-white hover:text-brand-400">
                <svg class="size-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                {{ $siteSettings->get('phone') }}
              </a>
              <a href="mailto:{{ $siteSettings->get('visa_email') }}" class="flex items-center gap-3 font-medium text-white hover:text-brand-400">
                <svg class="size-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                {{ $siteSettings->get('visa_email') }}
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
              <p>Your visa support request has been received. A DreamTour advisor will contact you with your document list and a quote.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="v-name" class="mb-1 block text-sm font-medium text-ink-700">Full name</label>
                <input id="v-name" type="text" x-model="form.name" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="v-phone" class="mb-1 block text-sm font-medium text-ink-700">Phone / WhatsApp</label>
                <input id="v-phone" type="tel" x-model="form.phone" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="v-email" class="mb-1 block text-sm font-medium text-ink-700">Email</label>
                <input id="v-email" type="email" x-model="form.email" required class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="v-nat" class="mb-1 block text-sm font-medium text-ink-700">Passport country</label>
                <input id="v-nat" type="text" x-model="form.nationality" required placeholder="e.g. Canada, Philippines" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="v-dest" class="mb-1 block text-sm font-medium text-ink-700">Destination</label>
                <select id="v-dest" x-model="form.destination" required class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                  <option value="">Select a country</option>
                  <template x-for="c in countries" :key="c"><option :value="c" x-text="c"></option></template>
                  <option value="Other">Other / not listed</option>
                </select>
              </div>
              <div>
                <label for="v-date" class="mb-1 block text-sm font-medium text-ink-700">Approx. travel date</label>
                <input id="v-date" type="date" x-model="form.travelDate" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div>
                <label for="v-app" class="mb-1 block text-sm font-medium text-ink-700">Applicants</label>
                <input id="v-app" type="number" min="1" max="20" x-model="form.applicants" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
              </div>
              <div class="sm:col-span-2">
                <label for="v-msg" class="mb-1 block text-sm font-medium text-ink-700">Anything we should know? <span class="font-normal text-ink-400">(optional)</span></label>
                <textarea id="v-msg" rows="3" x-model="form.message" placeholder="Previous refusals, tight timeline, linked DreamTour booking…" class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
              </div>
            </div>

            <button type="submit" class="btn btn-primary mt-5 w-full">Send Visa Request</button>
            <p class="mt-3 text-center text-xs text-ink-400">
              Submitting does not start an application or incur any fee. By sending
              this you agree we may contact you about your request.
            </p>
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
              <p class="font-display text-xl font-extrabold sm:text-2xl">Not sure if you need a visa? Ask us first.</p>
              <p class="mt-1 text-sm text-white/85">Eligibility checks are free — call or message any day, 8:00 AM to 8:00 PM.</p>
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


