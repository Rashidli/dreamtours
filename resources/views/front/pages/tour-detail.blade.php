<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  @include('front.partials.head_assets')
  <title>{{ $tour->name }} | {{ $siteSettings->get('site_name', 'DreamTour') }}</title>
  <meta name="description" content="{{ $tour->short_description }}">
  <meta property="og:title" content="{{ $tour->name }}">
  <meta property="og:description" content="{{ $tour->short_description }}">
  @if($tour->main_image)<meta property="og:image" content="{{ $tour->main_image }}">@endif
</head>
@php
  $currency = $nextDeparture?->currency ?: 'CAD';
  $taxNote = $siteSettings->get('price_tax_note');
  $fromPrice = $nextDeparture?->from_price;
  $category = $tour->categories->first();
  $categoryUrl = match($category?->slug) { 'multi-day' => '/multi-day-tours', 'international' => '/international-tours', 'cruises' => '/cruises', 'private' => '/private-tours', default => '/one-day-tours' };
  $included = $tour->contentItems->where('type', 'included');
  $excluded = $tour->contentItems->where('type', 'excluded');
  $goodToKnow = collect(['what_to_bring' => 'What to bring', 'document_requirement' => 'Documents', 'children' => 'Travelling with children', 'seniors' => 'Information for seniors'])
    ->map(fn ($title, $type) => ['title' => $title, 'items' => $tour->contentItems->where('type', $type)])
    ->filter(fn ($group) => $group['items']->isNotEmpty());
  $faqs = $tour->contentItems->where('type', 'faq');
  $policies = collect(['deposit_policy' => 'Deposit policy', 'cancellation_policy' => 'Cancellation policy', 'refund_policy' => 'Refund policy', 'boarding_rules' => 'Boarding rules', 'baggage_rules' => 'Baggage', 'important_notes' => 'Important notes'])
    ->filter(fn ($title, $field) => filled($tour->{$field}));
  $phone = $siteSettings->get('phone');
  $phoneLink = $siteSettings->get('phone_link') ?: preg_replace('/[^\d+]/', '', (string) $phone);
  $whatsapp = preg_replace('/\D+/', '', (string) $siteSettings->get('whatsapp'));
  $shareUrl = urlencode(url()->current());
  $shareText = urlencode($tour->name);
  $time = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('g:i A') : null;
  $dateRange = $nextDeparture
    ? ($tour->duration_days > 1 && $nextDeparture->returns_at
        ? $nextDeparture->departs_at->format('M j').' – '.$nextDeparture->returns_at->format('M j, Y')
        : $nextDeparture->departs_at->format('D, M j, Y'))
    : null;
@endphp
<body class="bg-white text-ink-800 antialiased has-bottom-bar">
  <a href="#main" class="skip-link">Skip to content</a>
  @include('front.partials.header')

  <main id="main" x-data="tourRequest({ tourId: {{ $tour->id }}, tourName: @js($tour->name) })">

    {{-- ================= Title ================= --}}
    <section class="border-b border-ink-100 bg-sand-50">
      <div class="container-page py-6 lg:py-8">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-ink-500">
          <a href="/" class="hover:text-brand-600">Home</a><span aria-hidden="true">/</span>
          @if($category)<a href="{{ $categoryUrl }}" class="hover:text-brand-600">{{ $category->name }}</a><span aria-hidden="true">/</span>@endif
          <span class="font-medium text-ink-800">{{ $tour->name }}</span>
        </nav>
        <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <div class="flex flex-wrap gap-2">
              @foreach($tour->categories as $cat)<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-ink-600 ring-1 ring-ink-900/10">{{ $cat->name }}</span>@endforeach
              @if($tour->badge)<span class="rounded-full px-3 py-1 text-xs font-semibold {{ tour_badge_class(str($tour->badge)->replace('_', ' ')->title()->replace('New', 'New Tour')->toString()) }}">{{ str($tour->badge)->replace('_', ' ')->title() }}</span>@endif
            </div>
            <h1 class="mt-3 font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl lg:text-5xl">{{ $tour->name }}</h1>
            <ul class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-600">
              <li class="flex items-center gap-1.5"><span aria-hidden="true">🕒</span>{{ $tour->duration_days > 1 ? $tour->duration_nights.' '.str('night')->plural($tour->duration_nights).' – '.$tour->duration_days.' days' : 'Full-day tour' }}</li>
              @if($tour->destination)<li class="flex items-center gap-1.5"><span aria-hidden="true">📍</span>{{ $tour->destination->name }}@if($tour->destination->parent), {{ $tour->destination->parent->name }}@endif</li>@endif
              @if($dateRange)<li class="flex items-center gap-1.5"><span aria-hidden="true">📅</span>{{ $dateRange }}@if($tour->departures->count() > 1) <a href="#prices" class="font-semibold text-brand-600 hover:underline">+{{ $tour->departures->count() - 1 }} more {{ str('date')->plural($tour->departures->count() - 1) }}</a>@endif</li>@endif
              @if($rating)<li><a href="#reviews" class="flex items-center gap-1.5 hover:text-brand-600"><span class="text-amber-400" aria-hidden="true">★★★★★</span><b class="text-ink-900">{{ number_format($rating, 1) }}</b> ({{ $tour->reviews->count() }} {{ str('review')->plural($tour->reviews->count()) }})</a></li>@endif
            </ul>
          </div>
          <div x-data="{ copied: false }" class="flex items-center gap-2">
            <span class="mr-1 text-sm text-ink-500">Share</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white text-sm font-bold text-[#1877F2] ring-1 ring-ink-900/10 hover:bg-ink-50" aria-label="Share on Facebook">f</a>
            <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white text-[#25D366] ring-1 ring-ink-900/10 hover:bg-ink-50" aria-label="Share on WhatsApp"><svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2c-5.5 0-9.96 4.46-9.96 9.96 0 1.76.46 3.48 1.34 5L2 22l5.2-1.36a9.9 9.9 0 0 0 4.84 1.24h.01c5.5 0 9.96-4.46 9.96-9.96S17.54 2 12.04 2Z"/></svg></a>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white text-sm font-bold text-ink-900 ring-1 ring-ink-900/10 hover:bg-ink-50" aria-label="Share on X">𝕏</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white text-xs font-bold text-[#0A66C2] ring-1 ring-ink-900/10 hover:bg-ink-50" aria-label="Share on LinkedIn">in</a>
            <button type="button" @click="navigator.clipboard?.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)" class="grid size-9 place-items-center rounded-full bg-white ring-1 ring-ink-900/10 hover:bg-ink-50" aria-label="Copy link">
              <span x-show="!copied" aria-hidden="true">🔗</span><span x-show="copied" x-cloak class="text-xs font-bold text-emerald-600">✓</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    {{-- ================= Photo carousel ================= --}}
    @if($photos->isNotEmpty())
      <section class="bg-sand-50 pb-6 lg:pb-10" x-data="carousel({ count: {{ $photos->count() }}, interval: 0 })" @keydown.arrow-right.window="next()" @keydown.arrow-left.window="prev()">
        <div class="container-page">
          <div class="relative overflow-hidden rounded-3xl bg-ink-900 shadow-card" @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
            <div class="aspect-[4/3] sm:aspect-[16/8]">
              @foreach($photos as $i => $photo)
                <img src="{{ $photo }}" alt="{{ $tour->name }} — photo {{ $i + 1 }}" x-show="index === {{ $i }}" @if($i > 0) x-cloak loading="lazy" @endif
                     x-transition.opacity.duration.300ms class="absolute inset-0 size-full object-cover">
              @endforeach
            </div>
            @if($photos->count() > 1)
              <button type="button" @click="prev()" class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl text-ink-900 shadow-lg hover:bg-white" aria-label="Previous photo">‹</button>
              <button type="button" @click="next()" class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl text-ink-900 shadow-lg hover:bg-white" aria-label="Next photo">›</button>
              <span class="absolute bottom-3 right-3 rounded-full bg-ink-950/70 px-3 py-1 text-xs font-medium text-white" x-text="(index + 1) + ' / {{ $photos->count() }}'"></span>
            @endif
          </div>
          @if($photos->count() > 1)
            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
              @foreach($photos as $i => $photo)
                <button type="button" @click="go({{ $i }})" class="h-16 w-24 shrink-0 overflow-hidden rounded-xl ring-2 transition sm:h-20 sm:w-28" :class="index === {{ $i }} ? 'ring-brand-600' : 'ring-transparent opacity-70 hover:opacity-100'" aria-label="Show photo {{ $i + 1 }}">
                  <img src="{{ $photo }}" alt="" class="size-full object-cover" loading="lazy">
                </button>
              @endforeach
            </div>
          @endif
        </div>
      </section>
    @endif

    <section class="py-10 lg:py-14">
      <div class="container-page grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] xl:gap-14">
        <div class="min-w-0 space-y-12">

          @if(session('message'))<div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">{{ session('message') }}</div>@endif

          {{-- Overview --}}
          <div>
            <p class="text-lg font-medium text-ink-800">{{ $tour->short_description }}</p>
            <div class="mt-4 space-y-4 leading-7 text-ink-600">
              @foreach(preg_split('/\n\s*\n/', trim((string) $tour->description)) as $paragraph)
                @if(trim($paragraph) !== '')<p>{!! nl2br(e(trim($paragraph))) !!}</p>@endif
              @endforeach
            </div>
          </div>

          {{-- Tour info --}}
          <div>
            <h2 class="font-display text-2xl font-bold text-ink-900">Tour Info</h2>
            <dl class="mt-5 grid gap-px overflow-hidden rounded-3xl bg-ink-100 ring-1 ring-ink-100 sm:grid-cols-2">
              @php
                $info = array_filter([
                  'Date' => $nextDeparture?->departs_at->format('j F Y'),
                  'Duration' => $tour->duration_days > 1 ? $tour->duration_nights.' '.str('Night')->plural($tour->duration_nights).', '.$tour->duration_days.' Days' : '1 Day (full day)',
                  'Places covered' => trim(($tour->destination?->name ?? '').($tour->duration_nights ? ' · '.$tour->duration_nights.' '.str('night')->plural($tour->duration_nights) : '')),
                  'Departure time' => $nextDeparture?->departs_at->format('g:i A'),
                  'Return' => $nextDeparture?->returns_at?->format('D, M j · g:i A'),
                  'Departure location' => $pickupPoints->first()?->name.($pickupPoints->count() > 1 ? ' + '.($pickupPoints->count() - 1).' more' : ''),
                  'Transportation' => $tour->is_cruise ? 'Cruise ship' : $tour->transportations->pluck('name')->join(', '),
                  'Availability' => $nextDeparture ? ($nextDeparture->available_seats > 0 ? $nextDeparture->available_seats.' of '.$nextDeparture->capacity.' seats left' : 'Sold out') : null,
                  'Age' => $tour->age_restriction,
                  'Activity level' => $tour->activity_level,
                  'Package ID' => 'DT-'.str_pad((string) $tour->id, 4, '0', STR_PAD_LEFT),
                ], fn ($value) => filled($value));
              @endphp
              @foreach($info as $label => $value)
                <div class="flex items-baseline justify-between gap-4 bg-white px-5 py-3.5 sm:last:odd:col-span-2">
                  <dt class="text-sm text-ink-500">{{ $label }}</dt>
                  <dd class="text-right text-sm font-semibold text-ink-900">{{ $value }}</dd>
                </div>
              @endforeach
            </dl>
          </div>

          {{-- Dates: calendar of departures; the selected date shows its prices, seats and pickup points --}}
          @if($tour->departures->isNotEmpty())
            @php($selectedDeparture = $nextDeparture ?? $tour->departures->first())
            <div id="prices" class="scroll-mt-28" x-data="{ selected: {{ $selectedDeparture->id }} }" @departure-selected="selected = $event.detail">
              <h2 class="font-display text-2xl font-bold text-ink-900">Choose your date</h2>
              <p class="mt-1 text-sm text-ink-500">Pick an available date in the calendar to see its price, pickup points and seats left.</p>
              <div class="mt-5 grid gap-5 md:grid-cols-2">
                @include('front.partials.departure_calendar', ['calendarDepartures' => \App\Support\DepartureCalendar::payload($tour->departures), 'calendarSelected' => $selectedDeparture->id])
                <div>
                  @foreach($tour->departures as $departure)
                    <div x-show="selected === {{ $departure->id }}" @if($departure->id !== $selectedDeparture->id) x-cloak @endif class="overflow-hidden rounded-3xl ring-1 ring-ink-100">
                      <div class="bg-ink-900 px-5 py-3 text-white">
                        <p class="font-semibold">{{ \App\Support\DepartureCalendar::range($departure) }}</p>
                        <p class="mt-0.5 text-sm {{ $departure->is_bookable && $departure->available_seats <= 6 ? 'text-amber-300' : 'text-ink-200' }}">
                          Departs {{ $departure->departs_at->format('g:i A') }} ·
                          {{ $departure->is_bookable ? $departure->available_seats.' seats left' : $departure->status_label }}
                        </p>
                      </div>
                      <ul class="divide-y divide-ink-100 bg-white">
                        @forelse($departure->prices as $price)
                          <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <span class="text-sm text-ink-600">
                              @if($price->is_room) Per person in a <b class="text-ink-900">{{ str($price->label)->replace(' room', '') }} room</b>
                              @elseif($price->code === 'child') Per <b class="text-ink-900">child under 12</b>
                              @elseif($price->code === 'senior') Per <b class="text-ink-900">senior (65+)</b>
                              @else Per <b class="text-ink-900">adult</b> @endif
                            </span>
                            <span class="whitespace-nowrap font-display text-lg font-extrabold text-ink-900">{{ money($price->price, $departure->currency) }} <small class="text-xs font-medium text-ink-400">{{ $taxNote }}</small></span>
                          </li>
                        @empty
                          <li class="flex items-center justify-between px-5 py-3.5"><span class="text-sm text-ink-600">Per person</span><span class="font-display text-lg font-extrabold">{{ money($departure->price, $departure->currency) }} <small class="text-xs font-medium text-ink-400">{{ $taxNote }}</small></span></li>
                        @endforelse
                      </ul>
                      @php($datePickups = $departure->availablePickupPoints())
                      @if($datePickups->isNotEmpty())
                        <div class="border-t border-ink-100 bg-sand-50 px-5 py-3.5">
                          <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Pickup points for this date</p>
                          <ul class="mt-2 space-y-1.5 text-sm">
                            @foreach($datePickups as $point)
                              <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">●</span><span><b class="text-ink-900">{{ $point->name }}</b>@if($point->address)<span class="text-ink-500"> — {{ $point->address }}</span>@endif</span></li>
                            @endforeach
                          </ul>
                        </div>
                      @endif
                      <div class="border-t border-ink-100 bg-white p-4">
                        @if($departure->is_bookable && !$tour->is_private)
                          <a href="{{ route('front.tours.book', ['slug' => $tour->slug, 'departure' => $departure->id]) }}" class="btn btn-primary w-full">Book Now — {{ $departure->departs_at->format('M j') }}</a>
                        @else
                          <span class="btn w-full cursor-not-allowed bg-ink-100 text-ink-400">{{ $departure->status_label }}</span>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              @if($tour->deposit_per_person)
                <p class="mt-3 text-sm text-ink-500">Reserve with a deposit of <b class="text-ink-800">{{ money($tour->deposit_per_person, $currency) }} per person</b>. <a href="#policies" class="text-brand-600 hover:underline">Deposit policy</a></p>
              @endif
            </div>
          @endif

          {{-- Included / not included --}}
          @if($included->isNotEmpty() || $excluded->isNotEmpty())
            <div>
              <h2 class="font-display text-2xl font-bold text-ink-900">What's included</h2>
              <div class="mt-5 grid gap-4 md:grid-cols-2">
                @if($included->isNotEmpty())
                  <div class="rounded-3xl bg-emerald-50/60 p-5 ring-1 ring-emerald-100">
                    <h3 class="font-display font-bold text-emerald-800">Included in the price</h3>
                    <ul class="mt-4 space-y-3">
                      @foreach($included as $item)
                        <li class="flex gap-3">
                          <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-lg shadow-sm" aria-hidden="true">{{ $item->icon ?: '✓' }}</span>
                          <span><span class="block text-sm font-semibold text-ink-900">{{ $item->title }}</span>@if($item->description)<span class="block text-xs text-ink-500">{{ $item->description }}</span>@endif</span>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
                @if($excluded->isNotEmpty())
                  <div class="rounded-3xl bg-rose-50/60 p-5 ring-1 ring-rose-100">
                    <h3 class="font-display font-bold text-rose-800">Not included</h3>
                    <ul class="mt-4 space-y-3">
                      @foreach($excluded as $item)
                        <li class="flex gap-3">
                          <span class="relative grid size-9 shrink-0 place-items-center rounded-xl bg-white text-lg shadow-sm" aria-hidden="true">{{ $item->icon ?: '•' }}<span class="absolute -right-1 -top-1 grid size-4 place-items-center rounded-full bg-rose-600 text-[10px] font-bold leading-none text-white">✕</span></span>
                          <span><span class="block text-sm font-semibold text-ink-900">{{ $item->title }}</span>@if($item->description)<span class="block text-xs text-ink-500">{{ $item->description }}</span>@endif</span>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              </div>
            </div>
          @endif

          {{-- Itinerary --}}
          @if($tour->itineraryDays->isNotEmpty())
            <div x-data="{ open: [0] }">
              <div class="flex items-end justify-between gap-4">
                <h2 class="font-display text-2xl font-bold text-ink-900">Itinerary</h2>
                @if($tour->itineraryDays->count() > 1)
                  <button type="button" class="text-sm font-semibold text-brand-600 hover:underline" @click="open = open.length === {{ $tour->itineraryDays->count() }} ? [] : [...Array({{ $tour->itineraryDays->count() }}).keys()]" x-text="open.length === {{ $tour->itineraryDays->count() }} ? 'Collapse all' : 'Expand all'"></button>
                @endif
              </div>
              @if($nextDeparture && $tour->duration_days > 1)<p class="mt-1 text-sm text-ink-500">Dates shown for the {{ $nextDeparture->departs_at->format('F j') }} departure.</p>@endif
              <div class="mt-5 space-y-3">
                @foreach($tour->itineraryDays as $d => $day)
                  <article class="overflow-hidden rounded-3xl bg-white ring-1 ring-ink-100">
                    <button type="button" class="flex w-full items-center gap-4 px-5 py-4 text-left hover:bg-sand-50" @click="open.includes({{ $d }}) ? open = open.filter(x => x !== {{ $d }}) : open.push({{ $d }})" :aria-expanded="open.includes({{ $d }})">
                      <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-600 text-center font-display text-white"><span class="leading-none"><small class="block text-[10px] font-semibold uppercase">Day</small><b class="text-lg">{{ $day->day_number }}</b></span></span>
                      <span class="min-w-0 flex-1">
                        <span class="block font-display text-lg font-bold text-ink-900">{{ $day->title }}</span>
                        @if($nextDeparture)<span class="block text-sm text-ink-500">{{ $nextDeparture->departs_at->copy()->addDays($day->day_number - 1)->format('l, F jS, Y') }}</span>@endif
                      </span>
                      <span class="text-xl text-ink-400 transition" :class="open.includes({{ $d }}) && 'rotate-180'" aria-hidden="true">⌄</span>
                    </button>
                    <div x-show="open.includes({{ $d }})" @if($d > 0) x-cloak @endif class="border-t border-ink-100 px-5 pb-5 pt-4">
                      @if($day->description)<p class="mb-4 text-sm text-ink-600">{{ $day->description }}</p>@endif
                      <ol class="relative space-y-5 border-l-2 border-dashed border-ink-200 pl-6">
                        @foreach($day->items as $item)
                          <li class="relative">
                            <span class="absolute -left-[31px] top-1 size-3.5 rounded-full border-2 border-white bg-brand-600 ring-2 ring-brand-200" aria-hidden="true"></span>
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                              @if($item->arrival_time)<span class="font-display text-sm font-bold text-brand-600">{{ $time($item->arrival_time) }}@if($item->departure_time) – {{ $time($item->departure_time) }}@endif</span>@endif
                              <h3 class="font-semibold text-ink-900">{{ $item->location_name }}</h3>
                              @if($item->duration_minutes)<span class="text-xs text-ink-400">{{ $item->duration_minutes >= 60 ? floor($item->duration_minutes / 60).' h'.($item->duration_minutes % 60 ? ' '.($item->duration_minutes % 60).' min' : '') : $item->duration_minutes.' min' }}</span>@endif
                            </div>
                            @if($item->entrance_included || $item->is_optional || $item->own_expense)
                              <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @if($item->entrance_included)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">🎟️ Ticket included</span>@endif
                                @if($item->is_optional)<span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">Optional</span>@endif
                                @if($item->own_expense)<span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">On your own account</span>@endif
                              </div>
                            @endif
                            @if($item->description)<p class="mt-1.5 text-sm text-ink-600">{{ $item->description }}</p>@endif
                            @if($item->sights)<p class="mt-1 text-sm text-ink-600"><b class="text-ink-800">You will see:</b> {{ $item->sights }}</p>@endif
                            @if($item->activities)<p class="mt-1 text-sm text-ink-600"><b class="text-ink-800">Activities:</b> {{ $item->activities }}</p>@endif
                            @if($item->notes)<p class="mt-1 text-sm text-ink-500"><b class="text-ink-700">Note:</b> {{ $item->notes }}</p>@endif
                            @if($item->address || $item->video_url)
                              <p class="mt-1 flex flex-wrap gap-x-4 text-sm">
                                @if($item->address)<a class="text-brand-600 hover:underline" href="{{ $item->map_url ?: 'https://maps.google.com/?q='.urlencode($item->address) }}" target="_blank" rel="noopener">📍 {{ $item->address }}</a>@endif
                                @if($item->video_url)<a class="text-brand-600 hover:underline" href="{{ $item->video_url }}" target="_blank" rel="noopener">▶ Watch video</a>@endif
                              </p>
                            @endif
                            @if(!empty($item->images))
                              <div class="mt-3 flex gap-2 overflow-x-auto">@foreach($item->images as $image)<img src="{{ $image }}" alt="{{ $item->location_name }}" class="h-24 w-36 shrink-0 rounded-xl object-cover" loading="lazy">@endforeach</div>
                            @endif
                          </li>
                        @endforeach
                      </ol>
                    </div>
                  </article>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Pickup points --}}
          @if($pickupPoints->isNotEmpty() && !$tour->is_cruise)
            <div id="pickup-points" class="scroll-mt-28">
              <h2 class="font-display text-2xl font-bold text-ink-900">Pickup points</h2>
              <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach($pickupPoints as $point)
                  <li class="rounded-2xl bg-sand-50 p-4">
                    <b class="text-ink-900">{{ $point->name }}</b>
                    <p class="mt-0.5 text-sm text-ink-500">{{ $point->address }}</p>
                    @if($point->instructions)<p class="mt-1 text-xs text-ink-500">{{ $point->instructions }}</p>@endif
                    @if($point->map_url)<a href="{{ $point->map_url }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:underline">Open in Maps</a>@endif
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Good to know --}}
          @if($goodToKnow->isNotEmpty())
            <div>
              <h2 class="font-display text-2xl font-bold text-ink-900">Good to know</h2>
              <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @foreach($goodToKnow as $group)
                  <div class="rounded-3xl bg-sand-50 p-5">
                    <h3 class="font-display font-bold text-ink-900">{{ $group['title'] }}</h3>
                    <ul class="mt-3 space-y-2.5">
                      @foreach($group['items'] as $item)
                        <li class="text-sm"><b class="text-ink-800">{{ $item->title }}</b>@if($item->description)<span class="block text-ink-500">{{ $item->description }}</span>@endif</li>
                      @endforeach
                    </ul>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Policies --}}
          @if($policies->isNotEmpty())
            <div id="policies" class="scroll-mt-28">
              <h2 class="font-display text-2xl font-bold text-ink-900">Policies</h2>
              <div class="mt-5 divide-y divide-ink-100 overflow-hidden rounded-3xl ring-1 ring-ink-100">
                @foreach($policies as $field => $title)
                  <details class="group bg-white" @if($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold text-ink-900 hover:bg-sand-50">{{ $title }}<span class="text-ink-400 transition group-open:rotate-180" aria-hidden="true">⌄</span></summary>
                    <div class="px-5 pb-5 text-sm leading-6 text-ink-600">{!! nl2br(e($tour->{$field})) !!}</div>
                  </details>
                @endforeach
              </div>
            </div>
          @endif

          {{-- FAQ --}}
          @if($faqs->isNotEmpty())
            <div>
              <h2 class="font-display text-2xl font-bold text-ink-900">Frequently asked questions</h2>
              <div class="mt-5 space-y-3">
                @foreach($faqs as $faq)
                  <details class="group rounded-2xl bg-sand-50 px-5 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink-900">{{ $faq->title }}<span class="text-ink-400 transition group-open:rotate-180" aria-hidden="true">⌄</span></summary>
                    <p class="mt-2 text-sm text-ink-600">{{ $faq->description }}</p>
                  </details>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Videos from previous trips --}}
          @if($videos->isNotEmpty())
            <div>
              <h2 class="font-display text-2xl font-bold text-ink-900">Videos</h2>
              <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach($videos as $video)<a href="{{ $video->path }}" target="_blank" rel="noopener" class="grid min-h-32 place-items-center rounded-2xl bg-ink-900 p-4 text-center font-semibold text-white hover:bg-ink-800">▶ {{ $video->caption ?: 'Watch video' }}</a>@endforeach
              </div>
            </div>
          @endif

          {{-- Reviews --}}
          <div id="reviews" class="scroll-mt-28" x-data="{ writing: {{ $errors->hasAny(['name', 'email', 'rating', 'content', 'travel_date']) ? 'true' : 'false' }}, rating: {{ (int) old('rating', 5) }} }">
            <div class="flex flex-wrap items-end justify-between gap-4">
              <div>
                <h2 class="font-display text-2xl font-bold text-ink-900">Reviews from {{ $tour->name }} travellers</h2>
                @if($rating)
                  <p class="mt-2 flex items-center gap-2 text-sm text-ink-600"><span class="text-lg tracking-tight text-amber-400" aria-hidden="true">{{ str_repeat('★', (int) round($rating)) }}<span class="text-ink-200">{{ str_repeat('★', 5 - (int) round($rating)) }}</span></span><b class="text-ink-900">{{ number_format($rating, 1) }} out of 5</b> · {{ $tour->reviews->count() }} {{ str('review')->plural($tour->reviews->count()) }}</p>
                @endif
              </div>
              <button type="button" class="btn btn-outline btn-sm" @click="writing = !writing" x-text="writing ? 'Cancel' : 'Write a review'">Write a review</button>
            </div>

            @if(session('review_message'))<div class="mt-4 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('review_message') }}</div>@endif

            <form x-show="writing" x-cloak method="post" action="{{ route('front.reviews.store', $tour->slug) }}" class="mt-5 grid gap-3 rounded-3xl bg-sand-50 p-5 sm:grid-cols-2">
              @csrf
              <p class="text-sm text-ink-500 sm:col-span-2">Travelled with us on this tour? Tell other travellers about it. Reviews are published after a quick check by our team.</p>
              @if($errors->hasAny(['name', 'email', 'rating', 'content', 'travel_date']))<div class="rounded-xl bg-red-50 p-3 text-sm text-red-800 sm:col-span-2">{{ $errors->first() }}</div>@endif
              <div class="sm:col-span-2">
                <span class="mb-1 block text-sm font-semibold text-ink-800">Your rating</span>
                <input type="hidden" name="rating" :value="rating">
                <div class="flex gap-1 text-3xl">
                  @for($star = 1; $star <= 5; $star++)
                    <button type="button" @click="rating = {{ $star }}" :class="rating >= {{ $star }} ? 'text-amber-400' : 'text-ink-200'" aria-label="{{ $star }} {{ str('star')->plural($star) }}">★</button>
                  @endfor
                </div>
              </div>
              <input class="rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm" name="name" value="{{ old('name') }}" placeholder="Your name *" maxlength="100">
              <input class="rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm" name="email" type="email" value="{{ old('email') }}" placeholder="Email (not published) *">
              <input class="rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm" name="city" value="{{ old('city') }}" placeholder="City (optional)">
              <label class="flex items-center gap-2 rounded-xl border border-ink-200 bg-white px-3 py-2 text-sm text-ink-500">Travel date <input type="date" name="travel_date" value="{{ old('travel_date') }}" max="{{ now()->toDateString() }}" class="flex-1 bg-transparent text-ink-800 focus:outline-none"></label>
              <textarea class="rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm sm:col-span-2" name="content" rows="4" placeholder="What did you enjoy? Anything future travellers should know? *">{{ old('content') }}</textarea>
              <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
              <div class="sm:col-span-2"><button class="btn btn-primary">Send review</button></div>
            </form>

            @if($tour->reviews->isNotEmpty())
              <div class="mt-6 grid gap-4 md:grid-cols-2" x-data="{ all: false }">
                @foreach($tour->reviews as $r => $review)
                  <article class="rounded-3xl bg-white p-5 ring-1 ring-ink-100" @if($r >= 4) x-show="all" x-cloak @endif>
                    <div class="flex items-center gap-3">
                      @if($review->image)<img src="{{ $review->image }}" alt="" class="size-11 rounded-full object-cover">@else<span class="grid size-11 place-items-center rounded-full bg-brand-50 font-display font-bold text-brand-700">{{ str($review->name)->substr(0, 1) }}</span>@endif
                      <div class="min-w-0">
                        <p class="font-semibold text-ink-900">{{ $review->name }}</p>
                        <p class="text-xs text-ink-500">{{ collect([$review->position, $review->travel_date ? 'travelled '.$review->travel_date->format('M Y') : null])->filter()->join(' · ') }}</p>
                      </div>
                      <span class="ml-auto text-sm tracking-tight text-amber-400" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}<span class="text-ink-200">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-ink-600">{{ $review->content }}</p>
                  </article>
                @endforeach
                @if($tour->reviews->count() > 4)
                  <div class="md:col-span-2"><button type="button" class="btn btn-outline btn-sm" x-show="!all" @click="all = true">Show all {{ $tour->reviews->count() }} reviews</button></div>
                @endif
              </div>
            @else
              <p class="mt-4 text-sm text-ink-500">No reviews yet — be the first to share your experience.</p>
            @endif
          </div>
        </div>

        {{-- ================= Sidebar ================= --}}
        <aside id="booking" class="scroll-mt-28">
          <div class="space-y-4 lg:sticky lg:top-28">
            <div class="rounded-3xl bg-white p-6 shadow-card ring-1 ring-ink-900/5">
              @if($tour->is_private)
                <p class="text-sm text-ink-500">Private tour</p>
                <p class="mt-1 font-display text-2xl font-extrabold text-ink-900">Price on request</p>
                <p class="mt-2 text-sm text-ink-500">Tell us your dates and group size and we will send a personal quote.</p>
                <button type="button" class="btn btn-primary btn-lg mt-5 w-full" @click="show('private_tour')">Request a quote</button>
              @elseif(!$nextDeparture)
                <p class="text-sm text-ink-500">Scheduled trip</p>
                <p class="mt-1 font-display text-2xl font-extrabold text-ink-900">New dates coming soon</p>
                <p class="mt-2 text-sm text-ink-500">There are no open departure dates right now. Leave your number and we will tell you when booking opens.</p>
                <button type="button" class="btn btn-primary btn-lg mt-5 w-full" @click="show('callback')">Ask about dates</button>
              @else
                <p class="text-sm text-ink-500">Price starts at</p>
                <p class="mt-0.5 font-display text-3xl font-extrabold text-ink-900">{{ money($fromPrice, $currency) }} <span class="text-sm font-semibold text-ink-400">{{ $taxNote }}</span></p>
                <p class="text-xs text-ink-500">per person{{ $nextDeparture->has_room_pricing ? ' · based on room sharing' : '' }}</p>
                @if($rating)<a href="#reviews" class="mt-2 inline-flex items-center gap-1.5 text-sm"><span class="text-amber-400" aria-hidden="true">★</span><b>{{ number_format($rating, 1) }}</b><span class="text-ink-500">({{ $tour->reviews->count() }})</span></a>@endif
                <div class="mt-4 space-y-1.5 rounded-2xl bg-sand-50 p-4 text-sm">
                  <p class="flex justify-between gap-2"><span class="text-ink-500">Next departure</span><b class="text-right">{{ $nextDeparture->departs_at->format('D, M j') }}</b></p>
                  <p class="flex justify-between gap-2"><span class="text-ink-500">Seats left</span><b class="{{ $nextDeparture->available_seats <= 6 ? 'text-brand-600' : '' }}">{{ $nextDeparture->available_seats }}</b></p>
                  @if($tour->deposit_per_person)<p class="flex justify-between gap-2"><span class="text-ink-500">Deposit</span><b>{{ money($tour->deposit_per_person, $currency) }} / person</b></p>@endif
                </div>
                @if($canBook)
                  <a href="{{ route('front.tours.book', ['slug' => $tour->slug, 'departure' => $nextDeparture->id]) }}" class="btn btn-primary btn-lg mt-5 w-full">Book Now</a>
                  @if($tour->departures->count() > 1)<a href="#prices" class="mt-2 block text-center text-sm font-semibold text-brand-600 hover:underline">See all {{ $tour->departures->count() }} dates</a>@endif
                @else
                  <span class="btn btn-lg mt-5 w-full cursor-not-allowed bg-ink-100 text-ink-400">Sold Out</span>
                @endif
                <button type="button" class="btn btn-outline mt-2 w-full" @click="show('invoice')">Invoice Request</button>
              @endif
            </div>

            <div class="rounded-3xl bg-ink-900 p-6 text-white">
              <p class="font-display text-lg font-bold">Need help?</p>
              <ul class="mt-3 space-y-2.5 text-sm">
                @if($phone)<li><a href="tel:{{ $phoneLink }}" class="flex items-center gap-2 text-ink-100 hover:text-white"><span aria-hidden="true">📞</span>Call us on: <b class="text-white">{{ $phone }}</b></a></li>@endif
                @if($siteSettings->get('working_hours'))<li class="flex items-center gap-2 text-ink-200"><span aria-hidden="true">🕘</span>{{ $siteSettings->get('working_hours') }}</li>@endif
                @if($whatsapp)<li><a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hi! I have a question about '.$tour->name.'.') }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-ink-100 hover:text-white"><span aria-hidden="true">💬</span>{{ $siteSettings->get('whatsapp_label') ?: 'Chat on WhatsApp' }}</a></li>@endif
              </ul>
              <button type="button" class="btn btn-ghost btn-sm mt-4 w-full" @click="show('callback')">Let Us Call You</button>
            </div>
          </div>
        </aside>
      </div>
    </section>

    {{-- Mobile booking bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-100 bg-white/95 px-4 py-3 backdrop-blur lg:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
      <div class="flex items-center justify-between gap-3">
        @if($tour->is_private)
          <p class="font-display font-bold text-ink-900">Price on request</p>
          <button type="button" class="btn btn-primary" @click="show('private_tour')">Request a quote</button>
        @elseif(!$nextDeparture)
          <p class="font-display font-bold text-ink-900">New dates coming soon</p>
          <button type="button" class="btn btn-primary" @click="show('callback')">Ask about dates</button>
        @else
          <p class="leading-tight"><span class="block text-xs text-ink-500">From</span><b class="font-display text-lg text-ink-900">{{ money($fromPrice, $currency) }}</b> <small class="text-xs text-ink-400">{{ $taxNote }}</small></p>
          @if($canBook)<a href="#prices" class="btn btn-primary">Choose a date</a>@else<span class="btn bg-ink-100 text-ink-400">Sold Out</span>@endif
        @endif
      </div>
    </div>

    {{-- Request dialog --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" @keydown.escape.window="open = false">
      <div class="absolute inset-0 bg-ink-950/60" @click="open = false"></div>
      <div class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl" x-transition>
        <button type="button" class="absolute right-4 top-4 grid size-9 place-items-center rounded-full text-xl text-ink-500 hover:bg-ink-50" @click="open = false" aria-label="Close">×</button>
        <h2 class="pr-8 font-display text-xl font-bold text-ink-900" x-text="titles[mode]"></h2>
        <p class="mt-1 text-sm text-ink-500">{{ $tour->name }}</p>
        <div x-show="sent" class="mt-6 rounded-2xl bg-emerald-50 p-5 text-emerald-800">
          <p class="font-semibold">Thank you — we received your request.</p>
          <p class="mt-1 text-sm" x-text="mode === 'callback' ? 'We will call you back as soon as possible.' : 'A DreamTour representative will contact you shortly.'"></p>
          <button type="button" class="btn btn-primary btn-sm mt-4" @click="open = false">Close</button>
        </div>
        <form x-show="!sent" @submit.prevent="submit" class="mt-5 grid gap-3 sm:grid-cols-2">
          <input x-ref="firstField" x-model="form.name" class="rounded-xl border border-ink-200 px-3 py-2.5 text-sm sm:col-span-2" placeholder="Full name *" autocomplete="name">
          <input x-model="form.phone" type="tel" class="rounded-xl border border-ink-200 px-3 py-2.5 text-sm" placeholder="Phone *" autocomplete="tel">
          <input x-model="form.email" type="email" class="rounded-xl border border-ink-200 px-3 py-2.5 text-sm" placeholder="Email *" autocomplete="email">
          <template x-if="mode === 'callback'">
            <input x-model="form.time" class="rounded-xl border border-ink-200 px-3 py-2.5 text-sm sm:col-span-2" placeholder="Best time to call (e.g. weekdays after 5 PM)">
          </template>
          <template x-if="mode !== 'callback'">
            <label class="flex items-center justify-between gap-2 rounded-xl border border-ink-200 px-3 py-2 text-sm text-ink-500">Travellers <input x-model.number="form.people" type="number" min="1" max="100" class="w-16 bg-transparent text-right text-ink-900 focus:outline-none"></label>
          </template>
          <template x-if="mode !== 'callback'">
            @if($tour->departures->isNotEmpty())
              <select x-model="form.date" class="rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm"><option value="">Preferred date</option>@foreach($tour->departures as $departure)<option>{{ $departure->departs_at->format('M j, Y') }}</option>@endforeach</select>
            @else
              <input x-model="form.date" type="date" class="rounded-xl border border-ink-200 px-3 py-2.5 text-sm" aria-label="Preferred date">
            @endif
          </template>
          <textarea x-model="form.message" rows="3" class="rounded-xl border border-ink-200 px-3 py-2.5 text-sm sm:col-span-2" :placeholder="mode === 'invoice' ? 'Company name, billing address or anything to include on the invoice' : 'Message (optional)'"></textarea>
          <p x-show="error" x-text="error" class="rounded-xl bg-red-50 p-3 text-sm text-red-800 sm:col-span-2"></p>
          <button class="btn btn-primary sm:col-span-2" :disabled="sending" x-text="sending ? 'Sending…' : 'Send request'"></button>
        </form>
      </div>
    </div>
  </main>

  @include('front.partials.footer')
</body>
</html>
