{{-- Cookie banner and preferences (choice kept in the dt_consent cookie; see the "consent" store in app.js).
     Re-opened from the footer's "Cookie settings" link. --}}
<script>window.DreamTourConsentConfig = @json(['ga' => (string) setting('google_analytics_id'), 'gtm' => (string) setting('google_tag_manager_id'), 'pixel' => (string) setting('meta_pixel_id')]);</script>
<div x-data x-cloak>
  {{-- Banner until the visitor decides --}}
  <div x-show="!$store.consent.decided && !$store.consent.panel" x-transition.opacity
       class="fixed inset-x-0 bottom-0 z-50 p-3 sm:p-4" role="region" aria-label="Cookie choices">
    <div class="mx-auto flex max-w-4xl flex-col gap-4 rounded-3xl bg-ink-950 p-5 text-sm text-ink-100 shadow-2xl ring-1 ring-white/10 sm:flex-row sm:items-center sm:p-6">
      <p class="flex-1 leading-6">
        We use essential cookies to run this site. With your permission we also remember your preferences (like saved trips) and, if enabled, measure visits to improve the site.
        <a href="{{ setting('privacy_url', '/privacy-policy') }}" class="font-semibold text-white underline">Privacy Policy</a>
      </p>
      <div class="flex flex-wrap gap-2 sm:flex-nowrap">
        <button type="button" @click="$store.consent.openPanel()" class="btn btn-ghost btn-sm">Preferences</button>
        <button type="button" @click="$store.consent.rejectOptional()" class="btn btn-sm bg-white/10 text-white hover:bg-white/20">Reject optional</button>
        <button type="button" @click="$store.consent.acceptAll()" class="btn btn-primary btn-sm">Accept all</button>
      </div>
    </div>
  </div>

  {{-- Preferences panel --}}
  <div x-show="$store.consent.panel" x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="cookie-title" @keydown.escape.window="$store.consent.panel = false">
    <div class="absolute inset-0 bg-ink-950/60" @click="$store.consent.panel = false"></div>
    <div class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white p-6 text-ink-800 shadow-2xl sm:rounded-3xl">
      <h2 id="cookie-title" class="font-display text-xl font-bold text-ink-900">Cookie settings</h2>
      <p class="mt-1 text-sm text-ink-500">Choose what this site may store in your browser. You can change this at any time from “Cookie settings” at the bottom of every page.</p>
      <ul class="mt-5 divide-y divide-ink-100 rounded-2xl ring-1 ring-ink-200">
        <li class="flex items-start justify-between gap-4 p-4">
          <span><b class="text-ink-900">Essential</b><span class="block text-sm text-ink-500">Needed for the site to work: security, forms, bookings and this choice. Always on.</span></span>
          <span class="mt-1 shrink-0 text-xs font-semibold uppercase text-ink-400">Always on</span>
        </li>
        @foreach(['preferences' => ['Preferences', 'Remember your saved trips (favourites) and the sort order you chose, so they are still there next time.'], 'analytics' => ['Analytics', 'Anonymous statistics about how the site is used, to improve it.'], 'marketing' => ['Marketing', 'Measure our advertising and show you relevant DreamTour offers on other sites.']] as $key => [$title, $text])
          <li class="flex items-start justify-between gap-4 p-4">
            <label for="consent-{{ $key }}" class="cursor-pointer"><b class="text-ink-900">{{ $title }}</b><span class="block text-sm text-ink-500">{{ $text }}</span></label>
            <input id="consent-{{ $key }}" type="checkbox" x-model="$store.consent.{{ $key }}" class="mt-1 size-5 shrink-0 accent-brand-600">
          </li>
        @endforeach
      </ul>
      <div class="mt-5 flex flex-wrap justify-end gap-2">
        <button type="button" @click="$store.consent.rejectOptional()" class="btn btn-outline btn-sm">Reject optional</button>
        <button type="button" @click="$store.consent.save()" class="btn btn-outline btn-sm">Save my choices</button>
        <button type="button" @click="$store.consent.acceptAll()" class="btn btn-primary btn-sm">Accept all</button>
      </div>
    </div>
  </div>
</div>
