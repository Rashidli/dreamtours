<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  @include('front.partials.head_assets')
  <title>Book {{ $tour->name }} | {{ $siteSettings->get('site_name', 'DreamTour') }}</title>
  <meta name="robots" content="noindex">
</head>
@php
  $taxNote = $siteSettings->get('price_tax_note');
  $policyFields = collect(['deposit_policy' => 'Deposit policy', 'cancellation_policy' => 'Cancellation policy', 'refund_policy' => 'Refund policy'])->filter(fn ($t, $f) => filled($tour->{$f}));
  $input = 'w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500';
  $label = 'mb-1.5 block text-sm font-semibold text-ink-800';
  $wizard = [
    'departures' => $departuresPayload,
    'old' => array_merge(session()->getOldInput(), ['tour_departure_id' => old('tour_departure_id', request('departure'))]),
    'errors' => $errors->getMessages(),
    'depositPerPerson' => $tour->deposit_per_person ? (float) $tour->deposit_per_person : null,
    'taxNote' => $taxNote,
  ];
@endphp
<body class="bg-sand-50 text-ink-800 antialiased">
  <a href="#main" class="skip-link">Skip to content</a>
  @include('front.partials.header')

  <main id="main" class="py-8 lg:py-12" x-data="bookingWizard(@js($wizard))">
    <div class="container-page">
      <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-ink-500">
        <a href="/" class="hover:text-brand-600">Home</a><span aria-hidden="true">/</span>
        <a href="{{ route('front.tours.show', $tour->slug) }}" class="hover:text-brand-600">{{ $tour->name }}</a><span aria-hidden="true">/</span>
        <span class="font-medium text-ink-800">Booking</span>
      </nav>
      <h1 class="mt-3 font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Book your seats</h1>

      {{-- Step indicator --}}
      <ol id="booking-steps" class="mt-6 grid scroll-mt-28 grid-cols-5 gap-2">
        <template x-for="(name, i) in steps" :key="i">
          <li>
            <button type="button" @click="goTo(i + 1)" class="group w-full text-left">
              <span class="block h-1.5 rounded-full transition" :class="step > i ? 'bg-brand-600' : 'bg-ink-200'"></span>
              <span class="mt-2 hidden text-xs font-semibold sm:block" :class="step === i + 1 ? 'text-brand-600' : 'text-ink-500'" x-text="(i + 1) + '. ' + name"></span>
            </button>
          </li>
        </template>
      </ol>
      <p class="mt-2 text-sm font-semibold text-brand-600 sm:hidden" x-text="'Step ' + step + ' of ' + steps.length + ': ' + steps[step - 1]"></p>

      <form method="post" action="{{ route('front.tours.book.store', $tour->slug) }}" enctype="multipart/form-data" novalidate @submit="submit($event)"
            class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
        @csrf
        <div class="min-w-0">
          @if($errors->any())
            <div class="mb-5 rounded-2xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
              <p class="font-semibold">Please check the following:</p>
              <ul class="mt-1 list-disc pl-5">@foreach(collect($errors->all())->unique()->take(6) as $message)<li>{{ $message }}</li>@endforeach</ul>
              @if(!$errors->has('payment_proof'))<p class="mt-2">For your security, please attach the payment screenshot again.</p>@endif
            </div>
          @endif
          <div x-show="clientError" x-cloak class="mb-5 rounded-2xl bg-red-50 p-4 text-sm font-medium text-red-800 ring-1 ring-red-200" x-text="clientError" role="alert"></div>

          <div class="rounded-3xl bg-white p-5 shadow-card ring-1 ring-ink-900/5 sm:p-7">

            {{-- Step 1: date, rooms / travellers --}}
            <section x-show="step === 1">
              <h2 class="font-display text-xl font-bold text-ink-900">When are you travelling and how many people?</h2>
              <div class="mt-5">
                <span class="{{ $label }}">Departure date</span>
                <input type="hidden" name="tour_departure_id" :value="departureId">
                <div class="grid gap-2 sm:grid-cols-2">
                  <template x-for="d in departures" :key="d.id">
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl p-4 ring-1 transition" :class="Number(departureId) === d.id ? 'bg-brand-50 ring-2 ring-brand-500' : 'ring-ink-200 hover:ring-ink-400'">
                      <input type="radio" class="size-4 accent-brand-600" :value="d.id" x-model.number="departureId">
                      <span class="min-w-0">
                        <span class="block font-semibold text-ink-900" x-text="d.label"></span>
                        <span class="block text-xs text-ink-500" x-text="'Departs ' + d.time + ' · ' + d.seats + ' seats left'"></span>
                      </span>
                    </label>
                  </template>
                </div>
              </div>

              <div class="mt-7">
                <span class="{{ $label }}" x-text="roomPricing ? 'Choose your rooms' : 'Number of travellers'"></span>
                <p x-show="roomPricing" class="-mt-1 mb-3 text-sm text-ink-500">Prices are per person. A Double room is for 2 people, Triple for 3 and Quad for 4. <span x-show="childPrice">Children under 12 can be marked on the next step.</span></p>
                <div class="divide-y divide-ink-100 rounded-2xl ring-1 ring-ink-200">
                  <template x-for="p in options" :key="p.code">
                    <div class="flex items-center justify-between gap-4 p-4">
                      <div class="min-w-0">
                        <p class="font-semibold text-ink-900" x-text="p.label"></p>
                        <p class="text-sm text-ink-500"><span x-text="money(p.price)"></span> <span x-text="taxNote"></span> <span x-text="p.occupancy ? 'per person · ' + p.occupancy + ' guests per room' : 'per person'"></span></p>
                      </div>
                      <input type="hidden" :name="'selections[' + p.code + ']'" :value="selections[p.code] || 0">
                      <div class="flex shrink-0 items-center gap-3">
                        <button type="button" @click="change(p.code, -1)" class="grid size-10 place-items-center rounded-full text-xl ring-1 ring-ink-200 hover:bg-ink-50 disabled:opacity-40" :disabled="!selections[p.code]" :aria-label="'Fewer ' + p.label">−</button>
                        <span class="w-6 text-center font-display text-lg font-bold" x-text="selections[p.code] || 0"></span>
                        <button type="button" @click="change(p.code, 1)" class="grid size-10 place-items-center rounded-full text-xl ring-1 ring-ink-200 hover:bg-ink-50" :aria-label="'More ' + p.label">+</button>
                      </div>
                    </div>
                  </template>
                </div>
                <p class="mt-3 text-sm text-ink-600" x-show="count"><b x-text="count"></b> <span x-text="count === 1 ? 'traveller' : 'travellers'"></span> · total <b x-text="money(total)"></b> <span x-text="taxNote"></span></p>
              </div>
            </section>

            {{-- Step 2: passenger names --}}
            <section x-show="step === 2" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Passenger names</h2>
              <p class="mt-1 text-sm text-ink-500">Enter names exactly as they appear on photo ID{{ $tour->contentItems()->whereTranslation('title', 'Valid passport')->exists() ? ' / passport' : '' }}. The first passenger is the main contact.</p>
              <div class="mt-5 space-y-4">
                <template x-for="(passenger, i) in passengers" :key="i">
                  <div>
                    <p x-show="isNewRoom(i)" class="mb-2 mt-2 font-display text-sm font-bold uppercase tracking-wider text-brand-600" x-text="'Room ' + slots[i].room + ' — ' + slots[i].roomLabel"></p>
                    <fieldset class="rounded-2xl bg-sand-50 p-4">
                      <legend class="sr-only" x-text="'Passenger ' + (i + 1)"></legend>
                      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-ink-900" x-text="i === 0 ? 'Passenger 1 (main contact)' : 'Passenger ' + (i + 1)"></p>
                        <span class="text-sm text-ink-500"><span x-text="fareLabel(i)"></span> · <b class="text-ink-800" x-text="money(priceOf(i))"></b></span>
                      </div>
                      <div class="grid gap-3 sm:grid-cols-3">
                        <input :name="'passengers[' + i + '][first_name]'" x-model="passenger.first_name" class="{{ $input }}" placeholder="First name *" autocomplete="off">
                        <input :name="'passengers[' + i + '][last_name]'" x-model="passenger.last_name" class="{{ $input }}" placeholder="Last name *" autocomplete="off">
                        <label class="relative block">
                          <span class="pointer-events-none absolute -top-2 left-3 bg-sand-50 px-1 text-[11px] text-ink-500" x-text="i === 0 ? 'Date of birth *' : 'Date of birth'"></span>
                          <input type="date" max="{{ now()->subDay()->toDateString() }}" :name="'passengers[' + i + '][date_of_birth]'" x-model="passenger.date_of_birth" class="{{ $input }}">
                        </label>
                      </div>
                      <label x-show="roomPricing && childPrice" class="mt-3 flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" value="1" :name="'passengers[' + i + '][is_child]'" x-model="passenger.is_child" class="size-4 accent-brand-600">
                        Child under 12 (<span x-text="childPrice ? money(childPrice.price) : ''"></span>)
                      </label>
                    </fieldset>
                  </div>
                </template>
              </div>
              <div x-show="roomPricing" class="mt-5">
                <label class="{{ $label }}" for="roommate">Roommate requests <span class="font-normal text-ink-400">(optional)</span></label>
                <textarea id="roommate" name="roommate_request" x-model="roommate" rows="2" class="{{ $input }}" placeholder="e.g. Maria and Anna would like to share a room"></textarea>
              </div>
            </section>

            {{-- Step 3: pickup, phone, email, emergency contact --}}
            <section x-show="step === 3" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Pickup point & contact details</h2>
              <div class="mt-5">
                <span class="{{ $label }}">Pickup point *</span>
                <div class="grid gap-2 sm:grid-cols-2">
                  @foreach($pickupPoints as $point)
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl p-4 ring-1 transition" :class="pickup === '{{ $point->id }}' ? 'bg-brand-50 ring-2 ring-brand-500' : 'ring-ink-200 hover:ring-ink-400'">
                      <input type="radio" name="pickup_point_id" value="{{ $point->id }}" x-model="pickup" class="mt-1 size-4 accent-brand-600">
                      <span><span class="block font-semibold text-ink-900">{{ $point->name }}</span><span class="block text-xs text-ink-500">{{ $point->address }}</span></span>
                    </label>
                  @endforeach
                </div>
              </div>
              <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="{{ $label }}" for="phone">Canadian phone number *</label>
                  <input id="phone" name="phone" type="tel" x-model="phone" class="{{ $input }}" placeholder="416-555-0100" autocomplete="tel">
                </div>
                <div>
                  <label class="{{ $label }}" for="email">Active email *</label>
                  <input id="email" name="email" type="email" x-model="email" class="{{ $input }}" placeholder="you@example.com" autocomplete="email">
                  <p class="mt-1 text-xs text-ink-500">Your confirmation and seat numbers are sent here.</p>
                </div>
              </div>
              <div class="mt-6 rounded-2xl bg-sand-50 p-4">
                <p class="font-semibold text-ink-900">Emergency contact</p>
                <p class="text-xs text-ink-500">Someone not travelling with you whom we can reach during the trip.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                  <input name="emergency_contact_name" x-model="emergencyName" class="{{ $input }}" placeholder="Full name *">
                  <input name="emergency_contact_phone" type="tel" x-model="emergencyPhone" class="{{ $input }}" placeholder="Phone number *">
                </div>
              </div>
            </section>

            {{-- Step 4: optional extras --}}
            <section x-show="step === 4" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Meals & special requirements <span class="text-base font-normal text-ink-400">(optional)</span></h2>
              <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="{{ $label }}" for="meal">Meal preference</label>
                  <select id="meal" name="meal_selection" class="{{ $input }}">
                    @foreach(['' => 'No preference', 'Vegetarian' => 'Vegetarian', 'Vegan' => 'Vegan', 'Halal' => 'Halal', 'Gluten-free' => 'Gluten-free', 'No pork' => 'No pork'] as $value => $text)
                      <option value="{{ $value }}" @selected(old('meal_selection') === $value)>{{ $text }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="{{ $label }}" for="promo">Promo code <span class="font-normal text-ink-400">(if applicable)</span></label>
                  <input id="promo" name="promo_code" value="{{ old('promo_code') }}" class="{{ $input }} uppercase" placeholder="Code">
                </div>
                <div class="sm:col-span-2">
                  <label class="{{ $label }}" for="allergies">Food allergies</label>
                  <input id="allergies" name="food_allergies" value="{{ old('food_allergies') }}" class="{{ $input }}" placeholder="e.g. peanuts, shellfish">
                </div>
                <div class="sm:col-span-2">
                  <label class="{{ $label }}" for="special">Special requests</label>
                  <textarea id="special" name="special_requests" rows="3" class="{{ $input }}" placeholder="Seating near the front, celebrating a birthday, anything we should know">{{ old('special_requests') }}</textarea>
                </div>
              </div>
            </section>

            {{-- Step 5: amount, payment instructions, proof, policies --}}
            <section x-show="step === 5" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Deposit & payment</h2>
              <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-sand-50 p-4"><p class="text-xs uppercase text-ink-500">Tour total</p><p class="mt-1 font-display text-xl font-extrabold" x-text="money(total)"></p><p class="text-xs text-ink-400" x-text="taxNote"></p></div>
                <div class="rounded-2xl bg-brand-600 p-4 text-white"><p class="text-xs uppercase text-white/80">Pay now (deposit)</p><p class="mt-1 font-display text-xl font-extrabold" x-text="money(deposit)"></p></div>
                <div class="rounded-2xl bg-sand-50 p-4"><p class="text-xs uppercase text-ink-500">Balance later</p><p class="mt-1 font-display text-xl font-extrabold" x-text="money(total - deposit)"></p><p class="text-xs text-ink-400">due 14 days before departure</p></div>
              </div>

              @if($siteSettings->get('payment_instructions'))
                <div class="mt-5 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
                  <p class="font-semibold text-amber-900">How to pay</p>
                  <p class="mt-1 text-sm leading-6 text-amber-900">{!! nl2br(e($siteSettings->get('payment_instructions'))) !!}</p>
                </div>
              @endif

              <div class="mt-5">
                <label class="{{ $label }}" for="proof">Upload your payment screenshot *</label>
                <label for="proof" class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed border-ink-200 bg-sand-50 px-4 py-6 text-center hover:border-brand-400">
                  <span class="text-2xl" aria-hidden="true">📎</span>
                  <span class="text-sm font-semibold text-ink-800" x-text="fileName || 'Choose an image or PDF'"></span>
                  <span class="text-xs text-ink-500">JPG, PNG, HEIC or PDF · up to 8 MB</span>
                </label>
                <input id="proof" name="payment_proof" type="file" accept="image/*,application/pdf" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
              </div>

              @if($policyFields->isNotEmpty())
                <div class="mt-5 divide-y divide-ink-100 rounded-2xl ring-1 ring-ink-200">
                  @foreach($policyFields as $field => $title)
                    <details class="group"><summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-ink-900">{{ $title }}<span class="text-ink-400 group-open:rotate-180" aria-hidden="true">⌄</span></summary><p class="px-4 pb-4 text-sm text-ink-600">{!! nl2br(e($tour->{$field})) !!}</p></details>
                  @endforeach
                </div>
              @endif
              <label class="mt-4 flex items-start gap-3 text-sm text-ink-700">
                <input type="checkbox" name="accept_policies" value="1" x-model="accepted" class="mt-0.5 size-5 accent-brand-600">
                <span>I confirm the passenger details are correct and I agree to the deposit, cancellation and refund policies.</span>
              </label>
            </section>

            <div class="mt-8 flex items-center justify-between gap-3 border-t border-ink-100 pt-5">
              <button type="button" x-show="step > 1" @click="back()" class="btn btn-outline">← Back</button>
              <span x-show="step === 1"></span>
              <button type="button" x-show="step < steps.length" @click="next()" class="btn btn-primary btn-lg">Continue →</button>
              <button type="submit" x-show="step === steps.length" x-cloak class="btn btn-primary btn-lg">Submit booking</button>
            </div>
          </div>
        </div>

        {{-- Summary --}}
        <aside>
          <div class="overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-ink-900/5 lg:sticky lg:top-28">
            @if($tour->main_image)<img src="{{ $tour->main_image }}" alt="" class="h-36 w-full object-cover">@endif
            <div class="p-5">
              <p class="font-display text-lg font-bold text-ink-900">{{ $tour->name }}</p>
              <p class="mt-1 text-sm text-ink-500" x-text="departure ? departure.label + ' · ' + departure.time : ''"></p>
              <ul class="mt-4 space-y-1.5 text-sm" x-show="count">
                <template x-for="(slot, i) in slots" :key="i">
                  <li class="flex justify-between gap-3"><span class="truncate text-ink-600" x-text="(passengers[i]?.first_name ? passengers[i].first_name + ' — ' : 'Passenger ' + (i + 1) + ' — ') + fareLabel(i)"></span><span class="shrink-0 font-medium" x-text="money(priceOf(i))"></span></li>
                </template>
              </ul>
              <div class="mt-4 space-y-1 border-t border-ink-100 pt-4 text-sm">
                <p class="flex justify-between"><span class="text-ink-500">Total <span x-text="taxNote"></span></span><b class="font-display text-lg" x-text="money(total)"></b></p>
                <p class="flex justify-between"><span class="text-ink-500">Deposit due now</span><b class="text-brand-600" x-text="money(deposit)"></b></p>
              </div>
              <p class="mt-4 text-xs text-ink-500">Your booking reference is created when you submit. Seat numbers are emailed 1–2 days before departure once paid in full.</p>
            </div>
          </div>
        </aside>
      </form>
    </div>
  </main>

  @include('front.partials.footer')
</body>
</html>
