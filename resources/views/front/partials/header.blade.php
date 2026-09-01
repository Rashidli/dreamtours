<header x-data="{ scrolled:false, mobile:false }" @scroll.window="scrolled=window.scrollY>8" :class="scrolled&&'shadow-lg shadow-ink-950/30'" class="sticky top-0 z-50 bg-ink-900 text-white transition-shadow">
  <div class="hidden border-b border-white/10 lg:block"><div class="container-page flex h-10 items-center justify-between text-[13px]">
    <div class="flex items-center gap-5 text-ink-100">
      @foreach($contactItems->take(3) as $contact)<a href="{{ $contact->link ?: '#' }}" @if(str_starts_with($contact->link ?? '', 'http')) target="_blank" rel="noopener" @endif class="hover:text-white">{{ $contact->value }}</a>@endforeach
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
    <div class="flex items-center gap-2"><a href="/contact" class="btn btn-primary btn-sm hidden sm:inline-flex">Book Now</a><button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-white/10 xl:hidden" @click="mobile=true" aria-label="Open menu">☰</button></div>
  </div>
  <div x-show="mobile" x-cloak class="fixed inset-0 z-50 xl:hidden"><button class="absolute inset-0 bg-ink-950/70" @click="mobile=false" aria-label="Close menu"></button><div class="absolute right-0 top-0 h-full w-[86%] max-w-sm overflow-y-auto bg-ink-900 p-5 shadow-2xl"><div class="mb-5 flex items-center justify-between"><strong class="font-display text-xl">DREAM<span class="text-brand-500">TOUR</span></strong><button @click="mobile=false" class="text-2xl" aria-label="Close menu">×</button></div><nav class="flex flex-col gap-1">@foreach($headerNavigation as $item)<a href="{{ $item->url }}" class="rounded-xl px-4 py-3 font-semibold hover:bg-white/10">{{ $item->title }}</a>@foreach($item->children as $child)<a href="{{ $child->url }}" class="ml-4 rounded-xl px-4 py-2 text-sm text-ink-200 hover:bg-white/10">{{ $child->title }}</a>@endforeach @endforeach</nav><div class="mt-6 border-t border-white/10 pt-5 text-sm text-ink-200">@foreach($contactItems as $contact)<div class="mb-2">@if($contact->link)<a href="{{ $contact->link }}">{{ $contact->value }}</a>@else{{ $contact->value }}@endif</div>@endforeach</div></div></div>
</header>
