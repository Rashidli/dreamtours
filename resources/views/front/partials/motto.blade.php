{{-- The DreamTour motto, shown above the footer on every public page (admin: Global settings → Motto). --}}
@php
  $motto = (string) setting('motto', 'Don’t just dream places — go and see them.');
  [$mottoLead, $mottoTail] = array_pad(array_map('trim', preg_split('/\s+[—–-]\s+/u', $motto, 2)), 2, '');
@endphp
@if(trim($motto) !== '')
  <section aria-label="Our motto" class="relative overflow-hidden border-t border-white/5 bg-ink-900 py-10 text-center sm:py-12">
    <svg class="pointer-events-none absolute left-4 top-1/2 hidden h-16 w-56 -translate-y-1/2 text-brand-500 md:block lg:left-10 lg:w-72" viewBox="0 0 280 64" fill="none" aria-hidden="true">
      <path d="M14 6c-5.5 0-10 4.4-10 9.8C4 23.2 14 34 14 34s10-10.8 10-18.2C24 10.4 19.5 6 14 6Zm0 13.4a3.6 3.6 0 1 1 0-7.2 3.6 3.6 0 0 1 0 7.2Z" fill="currentColor"/>
      <path d="M14 40c30 22 90 22 140 4s90-20 120-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="7 9" opacity=".8"/>
    </svg>
    <svg class="pointer-events-none absolute right-4 top-1/2 hidden h-16 w-56 -translate-y-1/2 text-brand-500 md:block lg:right-10 lg:w-72" viewBox="0 0 280 64" fill="none" aria-hidden="true">
      <path d="M6 44c40 -18 80 -20 118 -6 16 6 30 4 34 -6 3 -8 -6 -14 -12 -8 -6 6 2 18 22 18 30 0 60 -14 86 -26" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="7 9" opacity=".8"/>
      <path d="m276 10-30 13-14-6-5 3 10 8-10 5-6-2-4 2 7 6 4 6 4-2-1-6 10-5 3 13 5-2 1-15 25-15c3-2 3-6 1-5Z" fill="currentColor"/>
    </svg>
    <div class="container-page relative">
      <p class="font-script text-3xl leading-tight text-white sm:text-4xl lg:text-5xl">{{ $mottoLead }}@if($mottoTail !== '') —@endif</p>
      @if($mottoTail !== '')
        <p class="mt-3 flex items-center justify-center gap-3 font-display text-sm font-bold uppercase tracking-[0.3em] text-brand-500 sm:gap-4 sm:text-lg">
          <span class="h-0.5 w-8 rounded-full bg-brand-500 sm:w-14" aria-hidden="true"></span>{{ $mottoTail }}<span class="h-0.5 w-8 rounded-full bg-brand-500 sm:w-14" aria-hidden="true"></span>
        </p>
      @endif
    </div>
  </section>
@endif
