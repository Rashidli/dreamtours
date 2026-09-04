@php
  $companyName = $siteSettings->get('site_name', 'DreamTour');
  $address     = $siteSettings->get('address');
  $hours       = $siteSettings->get('working_hours');
  $mapRaw      = trim((string) $siteSettings->get('map_embed'));
  preg_match('/src=["\']([^"\']+)["\']/i', $mapRaw, $m);
  $mapSrc      = $m[1] ?? (str_starts_with($mapRaw, 'http') ? $mapRaw : null);
  $mapLink     = $address
      ? 'https://www.google.com/maps/search/'.rawurlencode($address)
      : 'https://www.google.com/maps/search/pick-up+points+Greater+Toronto+Area';
@endphp
<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Contact Us — DreamTour</title>
    <meta name="description" content="Get in touch with DreamTour — phone, WhatsApp, email, pick-up points across the GTA, a message form and answers to common questions." />

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
           PAGE HERO + BREADCRUMB  (Contact Us — §16)
           ================================================================== -->
      <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <img
          src="{{ $sections->get('hero')?->background_image ?: 'https://images.unsplash.com/photo-1521295121783-8a321d551ad2?q=80&w=1920&auto=format&fit=crop' }}"
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
            <span class="font-medium text-white">Contact Us</span>
          </nav>

          <div class="mt-6 max-w-2xl">
            <p class="eyebrow text-brand-400">{{ $sections->get('hero')?->eyebrow }}</p>
            <h1 class="mt-2 font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl">{{ $sections->get('hero')?->title ?: $cmsPage->title }}</h1>
            <p class="mt-4 max-w-lg text-base text-ink-100 sm:text-lg">{{ $sections->get('hero')?->subtitle }}</p>
          </div>

          <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <li class="flex gap-3">
              <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 0 0-9 9v3.75A2.25 2.25 0 0 0 5.25 18H6a.75.75 0 0 0 .75-.75v-4.5A.75.75 0 0 0 6 12h-.75A9 9 0 0 1 18.75 12H18a.75.75 0 0 0-.75.75v4.5c0 .414.336.75.75.75h.75m0 0v.75A2.25 2.25 0 0 1 16.5 21H12" />
              </svg>
              <span>
                <span class="block font-display text-sm font-bold text-white">Quick response</span>
                <span class="mt-0.5 block text-sm text-ink-200">We reply as soon as we can, usually the same day.</span>
              </span>
            </li>
            <li class="flex gap-3">
              <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
              </svg>
              <span>
                <span class="block font-display text-sm font-bold text-white">Friendly support</span>
                <span class="mt-0.5 block text-sm text-ink-200">A real person, here to make your trip easy.</span>
              </span>
            </li>
            <li class="flex gap-3">
              <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
              <span>
                <span class="block font-display text-sm font-bold text-white">Trusted team</span>
                <span class="mt-0.5 block text-sm text-ink-200">Your safety and satisfaction come first.</span>
              </span>
            </li>
            <li class="flex gap-3">
              <svg class="size-6 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
              </svg>
              <span>
                <span class="block font-display text-sm font-bold text-white">Local experts</span>
                <span class="mt-0.5 block text-sm text-ink-200">Proudly serving Ontario &amp; Québec.</span>
              </span>
            </li>
          </ul>
        </div>
      </section>

      <!-- ==================================================================
           MESSAGE FORM + CONTACT INFORMATION
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page grid gap-12 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16">
          <!-- Form -->
          <div>
            <p class="eyebrow">Send Us a Message</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              We'd Love to Hear From You
            </h2>
            <p class="mt-3 text-ink-500">
              Fill out the form and our team will get back to you shortly. Every
              message is logged as a request so nothing gets missed.
            </p>

            <form
              x-data="contactForm"
              @submit.prevent="submit"
              class="mt-8 rounded-4xl bg-sand-50 p-6 ring-1 ring-ink-900/5 sm:p-8"
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
                <p>Thanks — your message has been sent. A DreamTour representative will get back to you shortly.</p>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label for="c-name" class="mb-1 block text-sm font-medium text-ink-700">Full name <span class="text-brand-600">*</span></label>
                  <input id="c-name" type="text" x-model="form.name" required class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                  <label for="c-email" class="mb-1 block text-sm font-medium text-ink-700">Email address <span class="text-brand-600">*</span></label>
                  <input id="c-email" type="email" x-model="form.email" required class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                  <label for="c-phone" class="mb-1 block text-sm font-medium text-ink-700">Phone number <span class="text-brand-600">*</span></label>
                  <input id="c-phone" type="tel" x-model="form.phone" required class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                  <label for="c-pickup" class="mb-1 block text-sm font-medium text-ink-700">Preferred pick-up location</label>
                  <select id="c-pickup" x-model="form.pickup" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">Not sure yet</option>
                    <template x-for="p in pickupPoints" :key="p"><option :value="p" x-text="p"></option></template>
                  </select>
                </div>
                <div class="sm:col-span-2">
                  <label for="c-subject" class="mb-1 block text-sm font-medium text-ink-700">Subject</label>
                  <input id="c-subject" type="text" x-model="form.subject" placeholder="Tour question, booking help, private tour…" class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div class="sm:col-span-2">
                  <label for="c-message" class="mb-1 block text-sm font-medium text-ink-700">Your message <span class="text-brand-600">*</span></label>
                  <textarea id="c-message" rows="4" x-model="form.message" required class="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                </div>
              </div>

              <button type="submit" class="btn btn-primary mt-5 w-full">
                Send Message
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5" />
                </svg>
              </button>
              <p class="mt-3 text-center text-xs text-ink-400">We typically reply within one business day.</p>
            </form>
          </div>

          <!-- Contact information -->
          <div>
            <p class="eyebrow">Contact Information</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Reach Us Anytime</h2>

            <dl class="mt-8 space-y-5">
              <div class="flex gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                  <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                  </svg>
                </span>
                <div>
                  <dt class="sr-only">Phone</dt>
                  <dd>
                    <a href="tel:{{ $siteSettings->get('phone_link') }}" class="font-display text-lg font-bold text-ink-900 hover:text-brand-600">{{ $siteSettings->get('phone') }}</a>
                    <p class="text-sm text-ink-500">{{ $hours }}</p>
                  </dd>
                </div>
              </div>
              <div class="flex gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                  <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.3-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2Zm5.8 14.06c-.24.68-1.42 1.3-1.95 1.35-.5.05-1.13.24-3.68-.77-3.1-1.22-5.08-4.4-5.24-4.6-.15-.2-1.25-1.66-1.25-3.17 0-1.5.79-2.24 1.07-2.55.28-.31.6-.38.8-.38.2 0 .4 0 .58.01.19.01.44-.07.68.52.24.6.83 2.07.9 2.22.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.07 1.31 2.36 1.46.29.15.46.12.63-.07.17-.2.73-.85.93-1.14.2-.29.39-.24.66-.15.27.1 1.7.8 1.99.95.29.15.48.22.55.34.07.12.07.71-.17 1.39Z" />
                  </svg>
                </span>
                <div>
                  <dt class="sr-only">WhatsApp</dt>
                  <dd>
                    <a href="https://wa.me/{{ $siteSettings->get('whatsapp') }}" target="_blank" rel="noopener" class="font-display text-lg font-bold text-ink-900 hover:text-brand-600">Message on WhatsApp</a>
                    <p class="text-sm text-ink-500">Fastest for a quick question</p>
                  </dd>
                </div>
              </div>
              <div class="flex gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                  <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                  </svg>
                </span>
                <div>
                  <dt class="sr-only">Email</dt>
                  <dd>
                    <a href="mailto:{{ $siteSettings->get('email') }}" class="font-display text-lg font-bold text-ink-900 hover:text-brand-600">{{ $siteSettings->get('email') }}</a>
                    <p class="text-sm text-ink-500">We reply within 24 hours</p>
                  </dd>
                </div>
              </div>
              <div class="flex gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                  <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                  </svg>
                </span>
                <div>
                  <dt class="sr-only">Address</dt>
                  <dd>
                    <p class="font-display text-lg font-bold text-ink-900">{{ $companyName }}</p>
                    @if($address)
                      <a href="{{ $mapLink }}" target="_blank" rel="noopener" class="text-sm text-ink-500 hover:text-brand-600">{{ $address }}</a>
                    @else
                      <p class="text-sm text-ink-500">GTA pick-up points and beyond</p>
                    @endif
                  </dd>
                </div>
              </div>
              @if($hours)
              <div class="flex gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                  <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
                </span>
                <div>
                  <dt class="sr-only">Business hours</dt>
                  <dd>
                    <p class="font-display text-lg font-bold text-ink-900">{{ $hours }}</p>
                    <p class="text-sm text-ink-500">Office and phone line</p>
                  </dd>
                </div>
              </div>
              @endif
            </dl>

            <div class="mt-8">
              <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">Follow us</p>
              <div class="mt-3 flex gap-3">
                @foreach($socials as $social)
                  <a href="{{ $social->link }}" target="_blank" rel="noopener" aria-label="{{ $siteSettings->get('site_name','DreamTour') }} on {{ $social->title }}" class="grid size-10 place-items-center rounded-full bg-ink-900 text-sm font-bold text-white transition hover:bg-brand-600">{{ str($social->title)->substr(0,1) }}</a>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           MAP + PICK-UP POINTS
           ================================================================== -->
      <section class="bg-sand-50 py-16 lg:py-24">
        <div class="container-page grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-16">
          <!-- Map: a real embed once one is configured, otherwise the stylised fallback -->
          @if($mapSrc)
            <div class="overflow-hidden rounded-4xl ring-1 ring-ink-900/5" style="aspect-ratio: 4 / 3">
              <iframe src="{{ $mapSrc }}" title="{{ $companyName }} on the map" class="size-full border-0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          @else
            <div class="relative overflow-hidden rounded-4xl bg-ink-50 ring-1 ring-ink-900/5" style="aspect-ratio: 4 / 3">
              <div class="absolute inset-0" style="background-image: repeating-linear-gradient(0deg,#0b112012 0 1px,transparent 1px 44px), repeating-linear-gradient(90deg,#0b112012 0 1px,transparent 1px 44px)"></div>
              <div class="absolute -right-16 -bottom-16 size-64 rounded-full bg-sky-200/60"></div>
              <div class="absolute left-0 top-1/2 h-1.5 w-full -translate-y-1/2 -rotate-6 bg-white/70"></div>
              <div class="absolute left-1/2 top-0 h-full w-1.5 -translate-x-1/2 rotate-12 bg-white/70"></div>

              <span class="absolute left-[18%] top-[30%] flex flex-col items-center">
                <svg class="size-7 text-brand-600 drop-shadow" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                <span class="mt-0.5 rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-semibold text-ink-800">Brampton</span>
              </span>
              <span class="absolute left-[36%] top-[20%] flex flex-col items-center">
                <svg class="size-7 text-brand-600 drop-shadow" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                <span class="mt-0.5 rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-semibold text-ink-800">Vaughan</span>
              </span>
              <span class="absolute left-[70%] top-[34%] flex flex-col items-center">
                <svg class="size-7 text-brand-600 drop-shadow" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                <span class="mt-0.5 rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-semibold text-ink-800">Scarborough</span>
              </span>
              <span class="absolute left-[46%] top-[58%] flex flex-col items-center">
                <svg class="size-9 text-ink-900 drop-shadow" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                <span class="mt-0.5 rounded bg-ink-900 px-1.5 py-0.5 text-[11px] font-bold text-white">Toronto</span>
              </span>

              <a
                href="{{ $mapLink }}"
                target="_blank"
                rel="noopener"
                class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-ink-900 shadow-md hover:text-brand-600"
              >
                Open in Maps
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
              </a>
            </div>
          @endif

          <!-- Pick-up points -->
          <div>
            <p class="eyebrow">Popular Pick-up Points</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">
              Convenient Locations Across the GTA
            </h2>
            <p class="mt-3 max-w-md text-ink-500">
              Choose the stop nearest you when you book. Most tours also offer a
              central downtown departure.
            </p>

            <ul x-data="{ points: window.DreamTourData.pickupPoints }" class="mt-6 grid gap-3 sm:grid-cols-2">
              <template x-for="pt in points" :key="pt.id">
                <li class="flex items-center gap-3 rounded-2xl border border-ink-100 bg-white px-4 py-3">
                  <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                  </svg>
                  <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink-900" x-text="pt.name"></span>
                    <span class="block text-xs text-ink-500" x-text="pt.area"></span>
                  </span>
                </li>
              </template>
            </ul>
            <a href="//one-day-tours" class="btn btn-primary btn-sm mt-6 inline-flex">Browse tours &amp; pick your stop</a>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           FAQ
           ================================================================== -->
      <section class="bg-white py-16 lg:py-24">
        <div class="container-page">
          <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">FAQ</p>
            <h2 class="mt-2 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Frequently Asked Questions</h2>
          </div>
          <div x-data="{ open: null }" class="mx-auto mt-10 grid max-w-4xl gap-3 md:grid-cols-2">
            <template x-for="(f, i) in window.DreamTourData.faqs" :key="i">
              <div class="h-fit rounded-2xl border border-ink-100 bg-white p-5">
                <button
                  type="button"
                  @click="open = open === i ? null : i"
                  :aria-expanded="open === i"
                  class="flex w-full items-center justify-between gap-4 text-left"
                >
                  <span class="font-display text-sm font-bold text-ink-900" x-text="f.q"></span>
                  <svg class="size-5 shrink-0 text-brand-600 transition-transform" :class="open === i && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                  </svg>
                </button>
                <p x-show="open === i" x-cloak x-transition.opacity class="mt-3 text-sm leading-relaxed text-ink-600" x-text="f.a"></p>
              </div>
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
              <p class="font-display text-xl font-extrabold sm:text-2xl">Need help right now?</p>
              <p class="mt-1 text-sm text-white/85">Call or message us any day — 8:00 AM to 8:00 PM.</p>
            </div>
          </div>
          <div class="flex flex-col items-center gap-3 sm:flex-row">
            <a href="tel:{{ $siteSettings->get('phone_link') }}" class="font-display text-2xl font-extrabold tracking-tight hover:text-white/90 sm:text-3xl">{{ $siteSettings->get('phone') }}</a>
            <a href="https://wa.me/{{ $siteSettings->get('whatsapp') }}" target="_blank" rel="noopener" class="btn btn-white">Message on WhatsApp</a>
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


