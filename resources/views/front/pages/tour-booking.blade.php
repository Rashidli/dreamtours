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
    'old' => array_merge(session()->getOldInput(), ['tour_departure_id' => old('tour_departure_id', $selectedDepartureId)]),
    'errors' => $errors->getMessages(),
    'depositPerPerson' => $tour->deposit_per_person ? (float) $tour->deposit_per_person : null,
    'taxNote' => $taxNote,
    'countries' => $countries,
    'pickups' => $pickupPoints->map(fn ($p) => ['id' => (string) $p->id, 'name' => $p->name, 'address' => $p->address])->values(),
    'meals' => $tour->mealChoices(),
    'hasWaiver' => $tour->hasWaiver(),
  ];
  $submissionToken = old('submission_token') ?: \App\Http\Controllers\BookingPageController::newSubmissionToken();
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
      <ol id="booking-steps" class="mt-6 grid scroll-mt-28 grid-cols-6 gap-2">
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
        <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
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

            {{-- Step 1: date (calendar), rooms / travellers --}}
            <section x-show="step === 1" @departure-selected="departureId = $event.detail">
              <h2 class="font-display text-xl font-bold text-ink-900">When are you travelling and how many people?</h2>
              <div class="mt-5">
                <span class="{{ $label }}">Choose your departure date</span>
                <input type="hidden" name="tour_departure_id" :value="departureId">
                @include('front.partials.departure_calendar', ['calendarDepartures' => $calendarDepartures, 'calendarSelected' => old('tour_departure_id', $selectedDepartureId)])
                <p class="mt-3 rounded-2xl bg-brand-50 px-4 py-3 text-sm text-ink-800 ring-1 ring-brand-100" x-show="departure">
                  Selected: <b x-text="departure?.label"></b> · departs <span x-text="departure?.time"></span> · <span x-text="departure?.seats + ' seats left'"></span>
                </p>
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

            {{-- Step 2: passenger names and pickup points --}}
            <section x-show="step === 2" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Passengers & pickup points</h2>
              <p class="mt-1 text-sm text-ink-500">Enter names exactly as they appear on photo ID{{ $tour->contentItems()->whereTranslation('title', 'Valid passport')->exists() ? ' / passport' : '' }}. The first passenger is the main contact. Choose where each passenger will board.</p>
              <label x-show="passengers.length > 1" class="mt-4 flex items-center gap-2 text-sm font-medium text-ink-800">
                <input type="checkbox" name="same_pickup" value="1" x-model="samePickup" @change="applySamePickup()" class="size-4 accent-brand-600">
                All passengers use the same pickup point
              </label>
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
                      <label class="mt-3 block">
                        <span class="sr-only" x-text="'Pickup point for passenger ' + (i + 1)"></span>
                        <select :name="'passengers[' + i + '][pickup_point_id]'" x-model="passenger.pickup_point_id" @change="pickupChanged(i)" class="{{ $input }}" :class="!passenger.pickup_point_id && clientError ? 'border-red-400' : ''">
                          <option value="">Pickup point *</option>
                          <template x-for="point in departurePickups" :key="point.id">
                            <option :value="point.id" x-text="point.name + (point.address ? ' — ' + point.address : '')" :selected="point.id === passenger.pickup_point_id"></option>
                          </template>
                        </select>
                      </label>
                      @if($tour->mealChoices())
                        <label class="mt-3 block">
                          <span class="sr-only" x-text="'Meal for passenger ' + (i + 1)"></span>
                          <select :name="'passengers[' + i + '][meal_selection]'" x-model="passenger.meal_selection" class="{{ $input }}" :class="!passenger.meal_selection && clientError ? 'border-red-400' : ''">
                            <option value="">Meal choice *</option>
                            @foreach($tour->mealChoices() as $meal)<option value="{{ $meal }}">{{ $meal }}</option>@endforeach
                          </select>
                        </label>
                      @endif
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

            {{-- Step 3: phone, WhatsApp, email, emergency contact --}}
            <section x-show="step === 3" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Contact details</h2>
              <div class="mt-5 grid gap-4 sm:grid-cols-2">
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
              <div class="mt-4">
                <label class="{{ $label }}" for="whatsapp">WhatsApp number *</label>
                <input type="hidden" name="whatsapp_country" :value="waCountry">
                <div class="flex gap-2">
                  <div class="relative shrink-0" @click.outside="waOpen = false" @keydown.escape.stop="waOpen = false">
                    <button type="button" @click="toggleCountries()" :disabled="waSame" class="flex h-full items-center gap-1.5 rounded-xl border border-ink-200 bg-white px-3 text-sm font-medium text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 disabled:bg-ink-50"
                            :aria-expanded="waOpen" aria-haspopup="listbox" :aria-label="'Country code: ' + (country?.name || '') + ' +' + (country?.dial || '')">
                      <span class="text-xs font-semibold text-ink-500" x-text="waCountry"></span><span x-text="'+' + (country?.dial || '')"></span><span class="text-ink-400" aria-hidden="true">⌄</span>
                    </button>
                    <div x-cloak :class="waOpen ? 'block' : 'hidden'" class="absolute left-0 z-30 mt-1 w-72 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl bg-white shadow-card ring-1 ring-ink-200">
                      <input x-ref="waSearch" x-model="waQuery" @keydown.enter.prevent="chooseCountry(filteredCountries[0])" class="w-full border-0 border-b border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-0" placeholder="Search country or code" aria-label="Search country or code">
                      <ul class="max-h-60 overflow-y-auto py-1" role="listbox">
                        <template x-for="c in filteredCountries" :key="c.iso">
                          <li role="option" :aria-selected="c.iso === waCountry">
                            <button type="button" @click="chooseCountry(c)" class="flex w-full items-center gap-2 px-3.5 py-2 text-left text-sm hover:bg-brand-50 focus:bg-brand-50 focus:outline-none" :class="c.iso === waCountry ? 'font-semibold text-brand-700' : 'text-ink-800'">
                              <span class="min-w-0 flex-1 truncate" x-text="c.name"></span><span class="text-ink-500" x-text="'+' + c.dial"></span>
                            </button>
                          </li>
                        </template>
                        <li x-show="!filteredCountries.length" class="px-3.5 py-2 text-sm text-ink-500">No matching country</li>
                      </ul>
                    </div>
                  </div>
                  <input id="whatsapp" name="whatsapp" type="tel" x-model="waNumber" :readonly="waSame" class="{{ $input }} read-only:bg-ink-50" :placeholder="country?.dial === '1' ? '416-555-0100' : 'Phone number'" autocomplete="tel-national">
                </div>
                <label class="mt-2 flex items-center gap-2 text-sm text-ink-700">
                  <input type="checkbox" name="whatsapp_same" value="1" x-model="waSame" @change="syncWhatsapp()" class="size-4 accent-brand-600">
                  My WhatsApp number is the same as my phone number
                </label>
                <p class="mt-1 text-xs text-ink-500" x-show="waNumber && !waSame" x-text="validWhatsapp() ? 'Saved as ' + whatsappPreview() : ''"></p>
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
              <h2 class="font-display text-xl font-bold text-ink-900">{{ $tour->mealChoices() ? 'Special requirements' : 'Meals & special requirements' }} <span class="text-base font-normal text-ink-400">(optional)</span></h2>
              <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @unless($tour->mealChoices())
                <div>
                  <label class="{{ $label }}" for="meal">Meal preference</label>
                  <select id="meal" name="meal_selection" class="{{ $input }}">
                    @foreach(['' => 'No preference', 'Vegetarian' => 'Vegetarian', 'Vegan' => 'Vegan', 'Halal' => 'Halal', 'Gluten-free' => 'Gluten-free', 'No pork' => 'No pork'] as $value => $text)
                      <option value="{{ $value }}" @selected(old('meal_selection') === $value)>{{ $text }}</option>
                    @endforeach
                  </select>
                </div>
                @endunless
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
                  <svg class="size-7 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                  <span class="text-sm font-semibold text-ink-800" x-text="fileName || 'Choose an image or PDF'"></span>
                  <span class="text-xs text-ink-500">JPG, PNG, HEIC or PDF · up to 10 MB</span>
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

              @if($tour->hasWaiver())
                <div class="mt-6">
                  <p class="{{ $label }}">{{ $tour->waiver_title ?: 'Waiver' }} <span class="font-normal text-ink-500">— please read before you continue</span></p>
                  <div class="rich-text max-h-64 overflow-y-auto rounded-2xl bg-sand-50 p-4 text-sm ring-1 ring-ink-200" tabindex="0" aria-label="{{ $tour->waiver_title ?: 'Waiver' }}">{!! str_contains((string) $tour->waiver, '<') ? $tour->waiver : nl2br(e($tour->waiver)) !!}</div>
                </div>
              @endif

              <fieldset class="mt-6 space-y-3">
                <legend class="{{ $label }}">Agreements</legend>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Required</p>
                <label class="flex items-start gap-3 text-sm text-ink-700">
                  <input type="checkbox" name="accept_terms" value="1" x-model="acceptTerms" class="mt-0.5 size-5 shrink-0 accent-brand-600">
                  <span>I have read and accept DreamTour’s <a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 underline">Terms and Conditions of Booking</a>{{ $policyFields->isNotEmpty() ? ', including the deposit, cancellation and refund policies above' : '' }}.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-ink-700">
                  <input type="checkbox" name="accept_privacy" value="1" x-model="acceptPrivacy" class="mt-0.5 size-5 shrink-0 accent-brand-600">
                  <span>I have read DreamTour’s <a href="{{ $privacyUrl }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 underline">Personal Data Protection and Privacy Policy</a>.</span>
                </label>
                @if($tour->hasWaiver())
                  <label class="flex items-start gap-3 text-sm text-ink-700">
                    <input type="checkbox" name="accept_waiver" value="1" x-model="acceptWaiver" class="mt-0.5 size-5 shrink-0 accent-brand-600">
                    <span>I have read and agree to the {{ $tour->waiver_title ?: 'waiver' }} for this trip, for myself and the passengers in this booking.</span>
                  </label>
                @endif
                <p class="pt-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Optional</p>
                <label class="flex items-start gap-3 text-sm text-ink-700">
                  <input type="checkbox" name="marketing_consent" value="1" x-model="marketing" class="mt-0.5 size-5 shrink-0 accent-brand-600">
                  <span>I agree to the use of my personal data by DreamTour for communicating news, promotions, client relationship management and marketing activities.</span>
                </label>
              </fieldset>
            </section>

            {{-- Step 6: review everything before anything is saved --}}
            <section x-show="step === 6" x-cloak>
              <h2 class="font-display text-xl font-bold text-ink-900">Review your booking</h2>
              <p class="mt-1 text-sm text-ink-500">Please check everything below. Your booking is only created when you press “Confirm and Submit Booking”.</p>
              <dl class="mt-5 divide-y divide-ink-100 rounded-2xl text-sm ring-1 ring-ink-200">
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500">Trip</dt><dd class="font-semibold text-ink-900">{{ $tour->name }}</dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500">Date</dt><dd class="font-semibold text-ink-900" x-text="departure ? departure.label + ' · departs ' + departure.time : ''"></dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500">Main contact</dt><dd class="text-ink-900"><span x-text="(passengers[0]?.first_name || '') + ' ' + (passengers[0]?.last_name || '')"></span><br><span x-text="email"></span><br>Phone: <span x-text="phone"></span><br>WhatsApp: <span x-text="validWhatsapp() ? whatsappPreview() : waNumber"></span></dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500">Emergency contact</dt><dd class="text-ink-900" x-text="emergencyName + ' · ' + emergencyPhone"></dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500" x-text="count + (count === 1 ? ' passenger' : ' passengers')"></dt>
                  <dd><ul class="space-y-2">
                    <template x-for="(p, i) in passengers" :key="i">
                      <li class="text-ink-900"><b x-text="p.first_name + ' ' + p.last_name"></b> <span class="text-ink-500" x-text="'· ' + fareLabel(i) + ' · ' + money(priceOf(i))"></span><br><span class="text-ink-600" x-text="'Pickup: ' + pickupName(p.pickup_point_id) + (p.meal_selection ? ' · Meal: ' + p.meal_selection : '')"></span></li>
                    </template>
                  </ul></dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]" x-show="extrasSummary"><dt class="text-ink-500">Extras</dt><dd class="whitespace-pre-line text-ink-900" x-text="extrasSummary"></dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500">Payment</dt><dd class="text-ink-900">Total <b x-text="money(total)"></b> <span x-text="taxNote"></span> · deposit now <b x-text="money(deposit)"></b> · balance later <b x-text="money(total - deposit)"></b><br>Payment proof: <span x-text="fileName"></span></dd></div>
                <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]"><dt class="text-ink-500">Agreements</dt><dd class="text-ink-900">
                  <span x-text="(acceptTerms ? '✓' : '✗') + ' Terms and Conditions of Booking'"></span><br>
                  <span x-text="(acceptPrivacy ? '✓' : '✗') + ' Privacy Policy'"></span><br>
                  <template x-if="hasWaiver"><span x-text="(acceptWaiver ? '✓' : '✗') + ' Waiver'"></span></template><template x-if="hasWaiver"><br></template>
                  <span x-text="marketing ? '✓ News and promotions: yes' : '— News and promotions: no'"></span>
                </dd></div>
              </dl>
            </section>

            <div class="mt-8 flex items-center justify-between gap-3 border-t border-ink-100 pt-5">
              <button type="button" x-show="step > 1 && step < steps.length" @click="back()" class="btn btn-outline">← Back</button>
              <span x-show="step === 1"></span>
              <button type="button" x-show="step === steps.length" x-cloak @click="goTo(1)" class="btn btn-outline">Edit Booking Information</button>
              <button type="button" x-show="step < steps.length" @click="next()" class="btn btn-primary btn-lg" x-text="step === steps.length - 1 ? 'Review Booking →' : 'Continue →'">Continue →</button>
              <button type="submit" x-show="step === steps.length" x-cloak :disabled="submitting" class="btn btn-primary btn-lg disabled:cursor-wait disabled:opacity-60" x-text="submitting ? 'Submitting…' : 'Confirm and Submit Booking'">Confirm and Submit Booking</button>
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
