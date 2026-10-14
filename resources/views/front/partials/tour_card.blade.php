{{--
    One tour card, rendered server-side so the catalogue is in the HTML rather than only in
    window.DreamTourData. The wishlist button stays on Alpine because it reads localStorage,
    which only exists in the browser.

    Expects: $t — one entry of $frontendData['tours'].
--}}
<article class="group flex w-full flex-col overflow-hidden rounded-4xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover">
  <div class="relative">
    <img src="{{ $t['image'] }}" alt="{{ $t['name'] }}" class="h-52 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />

    @if($t['label'])
      <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm {{ tour_badge_class($t['label']) }}">{{ $t['label'] }}</span>
    @endif

    <button
      type="button"
      @click="$store.wishlist.toggle('{{ $t['id'] }}', @js($t['name']))"
      :aria-pressed="$store.wishlist.has('{{ $t['id'] }}')"
      :aria-label="$store.wishlist.has('{{ $t['id'] }}') ? 'Remove from wishlist' : 'Save to wishlist'"
      class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 text-ink-500 backdrop-blur transition hover:text-brand-600"
    >
      <svg
        class="size-5"
        :class="$store.wishlist.has('{{ $t['id'] }}') && 'text-brand-600'"
        :fill="$store.wishlist.has('{{ $t['id'] }}') ? 'currentColor' : 'none'"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
      </svg>
    </button>

    <span class="absolute bottom-3 left-3 rounded-full bg-ink-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{{ $t['duration'] }}</span>
  </div>

  <div class="flex flex-1 flex-col p-5">
    <h3 class="font-display text-lg font-bold text-ink-900"><a href="/tours/{{ $t['slug'] }}" class="hover:text-brand-600">{{ $t['name'] }}</a></h3>
    @if(!empty($t['rating']))
      <p class="mt-1 flex items-center gap-1 text-xs text-ink-500"><span class="text-amber-400" aria-hidden="true">★</span><span class="font-semibold text-ink-800">{{ number_format($t['rating'], 1) }}</span><span>({{ $t['reviewCount'] }} {{ $t['reviewCount'] === 1 ? 'review' : 'reviews' }})</span></p>
    @endif
    <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-500">
      <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
      </svg>
      <span>{{ $t['destination'] }}</span>
    </p>

    @if(!empty($t['description']))
      <p class="mt-3 line-clamp-2 text-sm text-ink-500">{{ $t['description'] }}</p>
    @endif

    <dl class="mt-4 grid grid-cols-1 gap-y-2 text-sm text-ink-600">
      <div class="flex items-center gap-2">
        <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
        <dt class="sr-only">Departure date</dt>
        <dd>{{ $t['date'] ?: 'New dates coming soon' }}@if(($t['departureCount'] ?? 0) > 1) <span class="text-ink-400">+ {{ $t['departureCount'] - 1 }} more {{ $t['departureCount'] > 2 ? 'dates' : 'date' }}</span>@endif</dd>
      </div>
      <div class="flex items-center gap-2">
        <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m6-13.5v13.5m-6-13.5V18.75m0-13.5H5.625c-.621 0-1.125.504-1.125 1.125v9m0 0h6" />
        </svg>
        <dt class="sr-only">Transportation</dt>
        <dd>{{ $t['transport'] }}</dd>
      </div>
      <div class="flex items-center gap-2 {{ $t['seatsLeft'] <= 5 ? 'text-brand-600 font-medium' : '' }}">
        <svg class="size-4 shrink-0 {{ $t['seatsLeft'] > 5 ? 'text-ink-400' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        <dt class="sr-only">Availability</dt>
        <dd>{{ tour_seats_label((int) $t['seatsLeft']) }}</dd>
      </div>
    </dl>

    <div class="mt-auto pt-5">
      <div class="flex items-baseline gap-1.5 border-t border-ink-100 pt-4">
        @if($t['previousPrice'])
          <span class="text-sm text-ink-400 line-through">{{ money($t['previousPrice'], $t['currency']) }}</span>
        @endif
        @if($t['price'] > 0)
          <span class="text-xs text-ink-400">From</span>
          <span class="font-display text-xl font-extrabold text-ink-900">{{ money($t['price'], $t['currency']) }}</span>
          <span class="text-xs text-ink-400">{{ $siteSettings->get('price_tax_note') }} / person{{ !empty($t['roomPricing']) ? ' (room share)' : '' }}</span>
        @else
          <span class="font-display text-base font-bold text-ink-900">Price on request</span>
        @endif
      </div>

      <div class="mt-4 grid grid-cols-2 gap-2">
        <a href="/tours/{{ $t['slug'] }}" class="btn btn-outline btn-sm w-full">View Details</a>
        @if(!empty($t['isPrivate']) || empty($t['date']))
          <a href="/tours/{{ $t['slug'] }}#booking" class="btn btn-primary btn-sm w-full">Request</a>
        @elseif($t['seatsLeft'] > 0)
          <a href="/tours/{{ $t['slug'] }}/book" class="btn btn-primary btn-sm w-full">Book Now</a>
        @else
          <span class="btn btn-sm w-full cursor-not-allowed bg-ink-100 text-ink-400">Sold Out</span>
        @endif
      </div>
    </div>
  </div>
</article>
