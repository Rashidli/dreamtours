@php
  $phone   = $siteSettings->get('phone');
  $whats   = $siteSettings->get('whatsapp');
  $email   = $siteSettings->get('email');
  $address = $siteSettings->get('address');
  $hours   = $siteSettings->get('working_hours');
@endphp
@include('front.partials.motto')
<footer class="bg-ink-950 text-ink-300">
  <div class="container-page py-14 lg:py-18">

    <div class="grid gap-10 lg:grid-cols-12 lg:gap-8">

      {{-- Brand + description + socials --}}
      <div class="lg:col-span-4">
        <a href="/" class="inline-block font-display text-2xl font-extrabold">
          @if($footerLogo = setting_image('logo'))
            <img src="{{ $footerLogo }}" alt="{{ $siteSettings->get('site_name','DreamTour') }}" class="h-10 w-auto max-w-[240px] object-contain">
          @else
            <span class="text-white">DREAM</span><span class="text-brand-500">TOUR</span>
          @endif
        </a>
        <p class="mt-4 max-w-sm text-sm leading-6">{{ $siteSettings->get('footer_description') }}</p>
        @if($socials->isNotEmpty())
          <div class="mt-5 flex gap-3">
            @foreach($socials as $social)
              <a href="{{ $social->link }}" target="_blank" rel="noopener" aria-label="{{ $siteSettings->get('site_name', 'DreamTour') }} on {{ $social->title }}"
                 class="grid size-9 place-items-center rounded-full bg-white/10 text-xs font-bold transition hover:bg-brand-600 hover:text-white">{{ str($social->title)->substr(0, 1) }}</a>
            @endforeach
          </div>
        @endif
      </div>

      {{-- Tours --}}
      @if(!empty($footerTours) && $footerTours->isNotEmpty())
        <div class="lg:col-span-3">
          <h2 class="font-display font-bold text-white">Tours</h2>
          <ul class="mt-4 space-y-2.5 text-sm">
            @foreach($footerTours as $tour)
              @php $next = $tour->departures->first(); @endphp
              <li>
                <a href="/tours/{{ $tour->slug }}" class="group flex items-baseline justify-between gap-3 hover:text-white">
                  <span class="truncate">{{ $tour->name }}</span>
                  @if($next)<span class="shrink-0 text-xs text-ink-500 group-hover:text-ink-400">{{ $next->departs_at->format('M j') }}</span>@endif
                </a>
              </li>
            @endforeach
          </ul>
          <a href="/one-day-tours" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-500 hover:text-brand-400">
            All tours
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
          </a>
        </div>
      @endif

      {{-- Destinations --}}
      @if(setting('footer_show_destinations', '1') !== '0' && !empty($footerDestinations) && $footerDestinations->isNotEmpty())
        <div class="lg:col-span-2">
          <h2 class="font-display font-bold text-white">Destinations</h2>
          <ul class="mt-4 space-y-2.5 text-sm">
            @foreach($footerDestinations as $destination)
              <li><a href="{{ $destination->listing_url ?? '/one-day-tours' }}?destination={{ $destination->id }}" class="hover:text-white">{{ $destination->name }}</a></li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Link columns from the admin-managed navigation --}}
      @foreach(['footer_company' => 'Company', 'footer_support' => 'Support'] as $group => $title)
        @php $links = $footerNavigation->get($group, collect()); @endphp
        @if($links->isNotEmpty())
          <div class="lg:col-span-2">
            <h2 class="font-display font-bold text-white">{{ $title }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
              @foreach($links as $item)
                <li><a href="{{ $item->url }}" target="{{ $item->target }}" class="hover:text-white">{{ $item->title }}</a></li>
              @endforeach
            </ul>
          </div>
        @endif
      @endforeach

      {{-- Contact --}}
      <div class="lg:col-span-3">
        <h2 class="font-display font-bold text-white">Get in touch</h2>
        <ul class="mt-4 space-y-2.5 text-sm">
          @if($phone)
            <li><a href="tel:{{ $siteSettings->get('phone_link') ?: $phone }}" class="hover:text-white">{{ $phone }}</a></li>
          @endif
          @if($whats)
            <li><a href="https://wa.me/{{ $whats }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a></li>
          @endif
          @if($email)
            <li><a href="mailto:{{ $email }}" class="break-all hover:text-white">{{ $email }}</a></li>
          @endif
          @if($address)<li class="text-ink-400">{{ $address }}</li>@endif
          @if($hours)<li class="text-ink-400">{{ $hours }}</li>@endif
        </ul>
        <a href="{{ $siteSettings->get('header_book_url') ?: '/contact' }}" class="btn btn-primary btn-sm mt-5">Book Now</a>
      </div>
    </div>

    {{-- Bottom bar --}}
    <div class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
      <span>{{ $siteSettings->get('copyright') }} <button type="button" x-data @click="$store.consent.openPanel()" class="ms-3 underline-offset-2 hover:text-white hover:underline">Cookie settings</button></span>
      @php $legal = $footerNavigation->get('footer_legal', collect()); @endphp
      @if($legal->isNotEmpty())
        <ul class="flex flex-wrap gap-x-5 gap-y-2">
          @foreach($legal as $item)
            <li><a href="{{ $item->url }}" target="{{ $item->target }}" class="hover:text-white">{{ $item->title }}</a></li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</footer>
@include('front.partials.whatsapp_float')
@include('front.partials.cookie_consent')
