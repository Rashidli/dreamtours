{{-- Always-visible WhatsApp chat button, bottom-right on every public page. --}}
@php($floatWhatsapp = $siteSettings->get('whatsapp'))
@if($floatWhatsapp)
  @php($floatLabel = $siteSettings->get('whatsapp_label') ?: 'Chat with us on WhatsApp')
  <a href="https://wa.me/{{ preg_replace('/\D+/', '', $floatWhatsapp) }}?text={{ rawurlencode('Hi! I have a question about '.(isset($tour) && $tour instanceof \App\Models\Tour ? $tour->name : 'a DreamTour trip').'.') }}"
     target="_blank" rel="noopener"
     class="wa-float fixed bottom-5 right-4 z-40 flex items-center gap-2.5 rounded-full bg-[#25D366] py-3 pl-3 pr-3 font-display text-sm font-semibold text-white shadow-xl shadow-black/20 ring-4 ring-white/70 transition hover:scale-105 hover:bg-[#1ebe5b] sm:right-6 sm:pr-5"
     aria-label="{{ $floatLabel }}">
    <svg class="size-7 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2c-5.5 0-9.96 4.46-9.96 9.96 0 1.76.46 3.48 1.34 5L2 22l5.2-1.36a9.9 9.9 0 0 0 4.84 1.24h.01c5.5 0 9.96-4.46 9.96-9.96S17.54 2 12.04 2Zm5.83 14.06c-.24.68-1.42 1.32-1.96 1.36-.5.05-.98.24-3.32-.7-2.8-1.1-4.58-3.95-4.72-4.14-.14-.18-1.12-1.5-1.12-2.86 0-1.36.71-2.02.96-2.3.25-.28.55-.35.73-.35.18 0 .37 0 .53.01.17.01.4-.06.62.48.24.56.8 1.95.87 2.09.07.14.12.3.02.48-.1.18-.15.3-.29.46-.14.16-.3.36-.43.48-.14.14-.29.29-.13.57.17.28.74 1.22 1.59 1.98 1.09.98 2.01 1.28 2.29 1.42.28.14.45.12.61-.07.17-.19.71-.83.9-1.11.19-.28.37-.23.62-.14.25.09 1.6.75 1.87.89.28.14.46.21.53.32.07.12.07.66-.17 1.34Z"/></svg>
    <span class="hidden sm:inline">{{ $floatLabel }}</span>
  </a>
@endif
