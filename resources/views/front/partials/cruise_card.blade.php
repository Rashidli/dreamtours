{{--
    One cruise card, rendered server-side so the listing is in the HTML rather than only in
    window.DreamTourData. The wishlist button stays on Alpine because it reads localStorage.

    Expects: $c — one entry of $frontendData['cruises'].
--}}
                <article class="group flex flex-col overflow-hidden rounded-4xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-card-hover">
                  <div class="relative">
                    <img src="{{ $c['image'] }}" alt="{{ $c['name'] }}" class="h-52 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                    @if($c['label'])
                      <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm {{ tour_badge_class($c['label']) }}">{{ $c['label'] }}</span>
                    @endif
                    <button
                      type="button"
                      @click="$store.wishlist.toggle('cruise-{{ $c['id'] }}', @js($c['name']))"
                      :aria-pressed="$store.wishlist.has('cruise-{{ $c['id'] }}')"
                      :aria-label="$store.wishlist.has('cruise-{{ $c['id'] }}') ? 'Remove from wishlist' : 'Save to wishlist'"
                      class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 text-ink-500 backdrop-blur transition hover:text-brand-600"
                    >
                      <svg class="size-5" :class="$store.wishlist.has('cruise-{{ $c['id'] }}') && 'text-brand-600'" :fill="$store.wishlist.has('cruise-{{ $c['id'] }}') ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                      </svg>
                    </button>
                    <span class="absolute bottom-3 left-3 rounded-full bg-ink-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{{ $c['duration'] }}</span>
                  </div>

                  <div class="flex flex-1 flex-col p-5">
                    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-brand-600">
                      <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9m0 0 7.5 2.25M12 12 4.5 14.25M4.5 14.25 3 19.5a9 9 0 0 0 18 0l-1.5-5.25" />
                      </svg>
                      <span>{{ $c['line'] }}</span>
                    </p>
                    <h3 class="mt-1 font-display text-lg font-bold text-ink-900">{{ $c['name'] }}</h3>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-500">
                      <svg class="size-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                      </svg>
                      <span>{{ $c['region'] }}</span>
                    </p>
                    <p class="mt-2 line-clamp-2 text-sm text-ink-500">{{ $c['description'] }}</p>

                    @if($c['withTour'])
                      <span class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-sand-100 px-2.5 py-1 text-xs font-medium text-ink-700">
                        <svg class="size-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Can pair with a coach tour
                      </span>
                    @endif

                    <dl class="mt-4 grid grid-cols-1 gap-y-2 text-sm text-ink-600">
                      <div class="flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <dt class="sr-only">Next sailing</dt>
                        <dd>{{ $c['date'] }}</dd>
                      </div>
                      <div class="flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A11.954 11.954 0 0 1 12 15c-1.777 0-3.47.386-4.951 1.079A8.99 8.99 0 0 0 12 21Zm0-18a8.949 8.949 0 0 1 4.951 1.488A11.954 11.954 0 0 0 12 9c-1.777 0-3.47-.386-4.951-1.079A8.99 8.99 0 0 1 12 3Zm-9 9h18" />
                        </svg>
                        <dt class="sr-only">Departure port</dt>
                        <dd>{{ 'Departs ' . $c['port'] }}</dd>
                      </div>
                      <div class="flex items-center gap-2" class="{{ $c['cabinsLeft'] <= 10 && 'font-medium text-brand-600' }}">
                        <svg class="size-4 shrink-0" class="{{ $c['cabinsLeft'] > 10 && 'text-ink-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                        <dt class="sr-only">Cabin availability</dt>
                        <dd>{{ cruise_cabins_label((int) $c['cabinsLeft']) }}</dd>
                      </div>
                    </dl>

                    @if($c['documents'])
                      <details class="mt-4 rounded-2xl bg-sand-50 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-ink-800">Documents you'll need (<span>{{ count($c['documents']) }}</span>)</summary>
                        <ul class="mt-2 space-y-1 text-sm text-ink-600">
                          @foreach($c['documents'] as $doc)
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">&bull;</span><span>{{ $doc }}</span></li>
                          @endforeach
                        </ul>
                      </details>
                    @endif

                    <div class="mt-auto pt-5">
                      <div class="flex items-baseline gap-1.5 border-t border-ink-100 pt-4">
                        @if($c['previousPrice'])
                          <span class="text-sm text-ink-400 line-through">{{ money($c['previousPrice'], $c['currency'] ?? 'CAD') }}</span>
                        @endif
                        <span class="text-xs text-ink-400">From</span>
                        <span class="font-display text-xl font-extrabold text-ink-900">{{ money($c['price'], $c['currency'] ?? 'CAD') }}</span>
                        <span class="text-xs text-ink-400">{{ '/ '.mb_strtolower((string) $c['cabinFrom']).' cabin' }}</span>
                      </div>
                      <div class="mt-4 grid grid-cols-2 gap-2">
<a href="{{ '/tours/' . $c['slug'] }}" class="btn btn-outline btn-sm w-full">View Details</a>
                        <a href="{{ '/tours/' . $c['slug'] . '#booking' }}" class="btn btn-primary btn-sm w-full">Request Quote</a>
                      </div>
                    </div>
                  </div>
                </article>
