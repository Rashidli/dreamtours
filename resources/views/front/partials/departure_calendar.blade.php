{{-- Departure calendar. $calendarDepartures from App\Support\DepartureCalendar::payload(); $calendarSelected = departure id.
     Picking a date dispatches `departure-selected` with the id. --}}
<div x-data="departureCalendar({ departures: @js($calendarDepartures), selected: @js($calendarSelected ?? null) })" class="rounded-3xl bg-white ring-1 ring-ink-200">
  <div class="flex items-center justify-between gap-2 border-b border-ink-100 px-3 py-2.5 sm:px-4">
    <button type="button" @click="shift(-1)" :disabled="!canPrev" class="grid size-9 place-items-center rounded-full text-xl text-ink-700 hover:bg-ink-50 disabled:opacity-30" aria-label="Previous month with departures">‹</button>
    <p class="font-display text-base font-bold text-ink-900" x-text="monthLabel" aria-live="polite"></p>
    <button type="button" @click="shift(1)" :disabled="!canNext" class="grid size-9 place-items-center rounded-full text-xl text-ink-700 hover:bg-ink-50 disabled:opacity-30" aria-label="Next month with departures">›</button>
  </div>
  <div class="grid grid-cols-7 px-1.5 pt-2 text-center text-[11px] font-semibold uppercase tracking-wide text-ink-400 sm:px-3" aria-hidden="true">
    <template x-for="w in weekdays" :key="w"><span class="py-1" x-text="w"></span></template>
  </div>
  <div class="grid grid-cols-7 gap-1 px-1.5 pb-3 sm:px-3">
    <template x-for="(cell, i) in cells" :key="i">
      <div class="min-h-[3.25rem] sm:min-h-[4rem]">
        <template x-if="cell && !cell.departures.length">
          <span class="flex h-full items-start justify-center pt-1.5 text-sm text-ink-300" x-text="cell.day"></span>
        </template>
        <template x-if="cell && cell.departures.length">
          <button type="button" @click="choose(cell.departures.find((d) => d.bookable) || cell.departures[0])"
                  :disabled="!cell.departures.some((d) => d.bookable)"
                  :aria-pressed="cell.departures.some((d) => d.id === selected)"
                  :aria-label="cell.departures.map((d) => d.label + ', ' + d.statusLabel + (d.bookable ? ', from ' + d.priceLabel : '')).join('; ')"
                  class="flex h-full w-full flex-col items-center justify-start gap-0.5 rounded-xl pt-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                  :class="cell.departures.some((d) => d.id === selected) ? 'bg-brand-600 text-white shadow-card' : (cell.departures.some((d) => d.bookable) ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-200 hover:bg-brand-100' : 'cursor-not-allowed bg-ink-50 text-ink-400 line-through')">
            <span x-text="cell.day"></span>
            <span class="hidden text-[10px] font-medium leading-tight no-underline sm:block" x-text="cell.departures.some((d) => d.bookable) ? (cell.departures.find((d) => d.bookable).priceLabel) : cell.departures[0].statusLabel"></span>
          </button>
        </template>
      </div>
    </template>
  </div>
  <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-ink-100 px-4 py-2.5 text-xs text-ink-500">
    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand-50 ring-1 ring-brand-200"></span>Available</span>
    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand-600"></span>Selected</span>
    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-ink-100"></span>Sold out / closed</span>
  </div>
  {{-- The same dates as a list, for small screens and screen readers. --}}
  <ul class="space-y-1.5 border-t border-ink-100 p-3" aria-label="Departures this month">
    <template x-for="d in monthDepartures" :key="d.id">
      <li>
        <button type="button" @click="choose(d)" :disabled="!d.bookable" :aria-pressed="d.id === selected"
                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm transition"
                :class="d.id === selected ? 'bg-brand-600 text-white' : (d.bookable ? 'bg-sand-50 text-ink-800 hover:bg-brand-50' : 'cursor-not-allowed bg-ink-50 text-ink-400')">
          <span><span class="font-semibold" x-text="d.label"></span> <span class="opacity-80" x-text="'· ' + d.time"></span></span>
          <span class="shrink-0 text-xs font-semibold" x-text="d.bookable ? d.priceLabel + ' · ' + d.seats + ' seats left' : d.statusLabel"></span>
        </button>
      </li>
    </template>
  </ul>
</div>
