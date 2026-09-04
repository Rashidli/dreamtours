@php
  $toursUrl = $siteSettings->get('header_tours_url') ?: '/one-day-tours';
  $bookUrl  = $siteSettings->get('header_book_url') ?: '/contact';
  $phone    = $siteSettings->get('phone');
  $whatsapp = $siteSettings->get('whatsapp');
  $email    = $siteSettings->get('email');
@endphp
<header x-data="{ scrolled:false, mobile:false }" @scroll.window="scrolled=window.scrollY>8" :class="scrolled&&'shadow-lg shadow-ink-950/30'" class="sticky top-0 z-50 bg-ink-900 text-white transition-shadow">
  <div class="hidden border-b border-white/10 lg:block"><div class="container-page flex h-10 items-center justify-between text-[13px]">
    <div class="flex items-center gap-5 text-ink-100">
      @if($phone)
        <a href="tel:{{ $siteSettings->get('phone_link') ?: $phone }}" class="flex items-center gap-1.5 hover:text-white">
          <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
          <span>{{ $phone }}</span>
        </a>
      @endif
      @if($whatsapp)
        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 hover:text-white">
          <svg class="size-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2c-5.5 0-9.96 4.46-9.96 9.96 0 1.76.46 3.48 1.34 5L2 22l5.2-1.36a9.9 9.9 0 0 0 4.84 1.24h.01c5.5 0 9.96-4.46 9.96-9.96S17.54 2 12.04 2Zm5.83 14.06c-.24.68-1.42 1.32-1.96 1.36-.5.05-.98.24-3.32-.7-2.8-1.1-4.58-3.95-4.72-4.14-.14-.18-1.12-1.5-1.12-2.86 0-1.36.71-2.02.96-2.3.25-.28.55-.35.73-.35.18 0 .37 0 .53.01.17.01.4-.06.62.48.24.56.8 1.95.87 2.09.07.14.12.3.02.48-.1.18-.15.3-.29.46-.14.16-.3.36-.43.48-.14.14-.29.29-.13.57.17.28.74 1.22 1.59 1.98 1.09.98 2.01 1.28 2.29 1.42.28.14.45.12.61-.07.17-.19.71-.83.9-1.11.19-.28.37-.23.62-.14.25.09 1.6.75 1.87.89.28.14.46.21.53.32.07.12.07.66-.17 1.34Z"/></svg>
          <span>WhatsApp</span>
        </a>
      @endif
      @if($email)
        <a href="mailto:{{ $email }}" class="flex items-center gap-1.5 hover:text-white">
          <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
          <span>{{ $email }}</span>
        </a>
      @endif
    </div>
    <span class="text-ink-300">{{ $siteSettings->get('working_hours') }}</span>
  </div></div>
  <div class="container-page flex h-16 items-center justify-between gap-4 lg:h-20">
    <a href="/" class="flex shrink-0 items-center gap-2.5" aria-label="{{ $siteSettings->get('site_name','DreamTour') }} home">
      <span class="grid size-10 place-items-center rounded-lg bg-white font-display text-xl font-extrabold text-brand-600">D</span>
      <span class="font-display text-xl font-extrabold tracking-tight lg:text-2xl"><span class="text-white">DREAM</span><span class="text-brand-500">TOUR</span></span>
    </a>
    <nav aria-label="Primary" class="hidden items-center gap-0.5 xl:flex">
      @foreach($headerNavigation as $item)
        @if($item->children->isNotEmpty())
          <div class="relative" x-data="{open:false}" @mouseenter="open=true" @mouseleave="open=false" @click.outside="open=false"><button type="button" class="nav-link" @click="open=!open">{{ $item->title }} <span aria-hidden="true">⌄</span></button><div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-50 w-64 rounded-2xl bg-white p-2 text-ink-800 shadow-2xl ring-1 ring-ink-900/10">@foreach($item->children as $child)<a href="{{ $child->url }}" target="{{ $child->target }}" class="menu-item">{{ $child->title }}</a>@endforeach</div></div>
        @else
          <a href="{{ $item->url }}" target="{{ $item->target }}" class="nav-link {{ request()->is(ltrim($item->url,'/')) ? 'nav-link-active' : '' }}">{{ $item->title }}</a>
        @endif
      @endforeach
    </nav>
    <div class="flex items-center gap-2">
      <a href="/favorites" class="relative grid size-10 place-items-center rounded-lg text-white hover:bg-white/10" aria-label="My favourites">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" :fill="$store.wishlist?.count ? 'currentColor' : 'none'">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
        <span
          x-cloak
          x-show="$store.wishlist?.count"
          x-text="$store.wishlist?.count"
          class="absolute -right-0.5 -top-0.5 grid min-w-5 place-items-center rounded-full bg-brand-600 px-1 text-[11px] font-bold leading-5 text-white"
        ></span>
      </a>
      <a href="{{ $toursUrl }}" class="btn btn-ghost btn-sm hidden lg:inline-flex">View Tours</a>
      <a href="{{ $bookUrl }}" class="btn btn-primary btn-sm hidden sm:inline-flex">Book Now</a>
      <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-white/10 xl:hidden" @click="mobile=true" aria-label="Open menu">☰</button>
    </div>
  </div>
  <div x-show="mobile" x-cloak class="fixed inset-0 z-50 xl:hidden"><button class="absolute inset-0 bg-ink-950/70" @click="mobile=false" aria-label="Close menu"></button><div class="absolute right-0 top-0 h-full w-[86%] max-w-sm overflow-y-auto bg-ink-900 p-5 shadow-2xl">
    <div class="mb-5 flex items-center justify-between"><strong class="font-display text-xl">DREAM<span class="text-brand-500">TOUR</span></strong><button @click="mobile=false" class="text-2xl" aria-label="Close menu">×</button></div>
    <nav class="flex flex-col gap-1">@foreach($headerNavigation as $item)<a href="{{ $item->url }}" class="rounded-xl px-4 py-3 font-semibold hover:bg-white/10">{{ $item->title }}</a>@foreach($item->children as $child)<a href="{{ $child->url }}" class="ml-4 rounded-xl px-4 py-2 text-sm text-ink-200 hover:bg-white/10">{{ $child->title }}</a>@endforeach @endforeach</nav>
    <div class="mt-6 grid gap-2"><a href="{{ $toursUrl }}" class="btn btn-ghost w-full">View Tours</a><a href="{{ $bookUrl }}" class="btn btn-primary w-full">Book Now</a><a href="/favorites" class="btn btn-ghost w-full">My Favourites <span x-cloak x-show="$store.wishlist?.count" x-text="'(' + $store.wishlist?.count + ')'"></span></a></div>
    <div class="mt-6 grid gap-2 border-t border-white/10 pt-5 text-sm text-ink-200">
      @if($phone)<a href="tel:{{ $siteSettings->get('phone_link') ?: $phone }}" class="hover:text-white">{{ $phone }}</a>@endif
      @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a>@endif
      @if($email)<a href="mailto:{{ $email }}" class="hover:text-white">{{ $email }}</a>@endif
      @if($siteSettings->get('working_hours'))<span class="text-ink-400">{{ $siteSettings->get('working_hours') }}</span>@endif
      @if($siteSettings->get('address'))<span class="text-ink-400">{{ $siteSettings->get('address') }}</span>@endif
    </div>
  </div></div>
</header>
