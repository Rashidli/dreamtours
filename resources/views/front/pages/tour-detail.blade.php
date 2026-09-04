<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $tour->name }} | DreamTour</title>
  <meta name="description" content="{{ $tour->short_description }}">
  <link rel="stylesheet" href="/dreamtour/dist/main.css">
  <script defer src="/dreamtour/src/js/vendor/alpine.min.js"></script>
</head>
<body class="bg-white text-ink-800 antialiased">
  <a href="#main" class="skip-link">Skip to content</a>
  @include('front.partials.header')
  @php
    $nextDeparture = $tour->departures->first();
    $galleryMedia = $tour->galleryAlbums->flatMap->media;
  @endphp
  <main id="main">
    <section class="relative isolate overflow-hidden bg-ink-900 text-white">
      @if($tour->main_image)<img src="{{ $tour->main_image }}" class="absolute inset-0 -z-10 size-full object-cover opacity-35" alt="{{ $tour->name }}">@endif
      <div class="container-page py-20 lg:py-28">
        <p class="eyebrow text-brand-400">{{ $tour->categories->pluck('name')->join(' · ') }}</p>
        <h1 class="mt-3 max-w-4xl font-display text-4xl font-extrabold sm:text-6xl">{{ $tour->name }}</h1>
        <p class="mt-5 max-w-2xl text-lg text-ink-100">{{ $tour->short_description }}</p>
        <div class="mt-7 flex flex-wrap gap-3 text-sm">
          <span class="rounded-full bg-white/10 px-4 py-2">{{ $tour->duration_days }} days / {{ $tour->duration_nights }} nights</span>
          @if($tour->destination)<span class="rounded-full bg-white/10 px-4 py-2">{{ $tour->destination->name }}</span>@endif
          @if($tour->transportations->isNotEmpty())<span class="rounded-full bg-white/10 px-4 py-2">{{ $tour->transportations->pluck('name')->join(', ') }}</span>@endif
        </div>
      </div>
    </section>
    <section class="py-14 lg:py-20">
      <div class="container-page grid gap-12 lg:grid-cols-[1fr_380px]">
        <div>
          <dl class="mb-10 grid gap-3 rounded-3xl bg-sand-50 p-6 sm:grid-cols-2 xl:grid-cols-3">
            <div><dt class="text-xs uppercase text-ink-400">Price</dt><dd class="font-display text-xl font-bold">@if($nextDeparture?->old_price)<span class="mr-2 text-sm font-normal text-ink-400 line-through">{{ $nextDeparture->old_price }} {{ $nextDeparture->currency }}</span>@endif{{ $nextDeparture?->price ?? 'On request' }} {{ $nextDeparture?->currency }}</dd></div>
            <div><dt class="text-xs uppercase text-ink-400">Departure</dt><dd class="font-semibold">{{ $nextDeparture?->departs_at?->format('M j, Y g:i A') ?? 'To be announced' }}</dd></div>
            <div><dt class="text-xs uppercase text-ink-400">Return</dt><dd class="font-semibold">{{ $nextDeparture?->returns_at?->format('M j, Y g:i A') ?? 'To be announced' }}</dd></div>
            <div><dt class="text-xs uppercase text-ink-400">Available / capacity</dt><dd class="font-semibold">{{ $nextDeparture?->available_seats ?? 0 }} / {{ $nextDeparture?->capacity ?? 0 }}</dd></div>
            <div><dt class="text-xs uppercase text-ink-400">Departure location</dt><dd class="font-semibold">{{ $pickupPoints->first()?->name ?? 'To be announced' }}@if($pickupPoints->count() > 1) <a href="#pickup-points" class="text-sm font-normal text-brand-600 underline">+{{ $pickupPoints->count() - 1 }} more</a>@endif</dd></div>
            <div><dt class="text-xs uppercase text-ink-400">Status</dt><dd class="font-semibold">{{ str($nextDeparture?->status ?? $tour->status)->replace('_', ' ')->title() }}</dd></div>
            @if($tour->age_restriction)<div><dt class="text-xs uppercase text-ink-400">Age restriction</dt><dd class="font-semibold">{{ $tour->age_restriction }}</dd></div>@endif
            @if($tour->activity_level)<div><dt class="text-xs uppercase text-ink-400">Activity level</dt><dd class="font-semibold">{{ $tour->activity_level }}</dd></div>@endif
          </dl>

          @if($galleryMedia->isNotEmpty())
            <h2 class="mb-5 font-display text-3xl font-bold">Tour gallery</h2>
            <div class="mb-12 grid grid-cols-2 gap-3 md:grid-cols-3">
              @foreach($galleryMedia as $media)
                @if($media->type === 'video')<a href="{{ $media->path }}" target="_blank" rel="noopener" class="grid min-h-40 place-items-center rounded-2xl bg-ink-900 p-4 text-center font-semibold text-white">Watch video: {{ $media->caption }}</a>
                @else<img src="{{ $media->path }}" alt="{{ $media->alt_text ?: $tour->name }}" class="h-48 w-full rounded-2xl object-cover" loading="lazy">@endif
              @endforeach
            </div>
          @endif

          <h2 class="font-display text-3xl font-bold">About this tour</h2>
          <div class="mt-5">{!! nl2br(e($tour->description)) !!}</div>
          @if($tour->itineraryDays->isNotEmpty())
            <h2 class="mt-12 font-display text-3xl font-bold">Itinerary</h2>
            <div class="mt-6 space-y-5">
              @foreach($tour->itineraryDays as $day)
                <article class="rounded-3xl bg-sand-50 p-6">
                  <h3 class="font-display text-xl font-bold">Day {{ $day->day_number }} — {{ $day->title }}</h3>
                  <p class="mt-2">{{ $day->description }}</p>
                  <ol class="mt-4 space-y-3">
                    @foreach($day->items as $item)
                      <li class="border-l-2 border-brand-500 pl-4">
                        <b>{{ $item->arrival_time }}@if($item->departure_time)–{{ $item->departure_time }}@endif {{ $item->location_name }}</b>
                        @if($item->duration_minutes)<small class="ml-2 text-ink-400">{{ $item->duration_minutes }} minutes</small>@endif
                        <p class="text-sm text-ink-500">{{ $item->description }}</p>
                        @if($item->sights)<p class="mt-1 text-sm"><strong>What you will see:</strong> {{ $item->sights }}</p>@endif
                        @if($item->activities)<p class="mt-1 text-sm"><strong>Activities:</strong> {{ $item->activities }}</p>@endif
                        @if($item->address)<p class="mt-1 text-sm"><strong>Address:</strong> {{ $item->address }} @if($item->map_url)<a class="text-brand-600 underline" href="{{ $item->map_url }}" target="_blank" rel="noopener">Map</a>@endif</p>@endif
                        @if($item->video_url)<p class="mt-1 text-sm"><a class="text-brand-600 underline" href="{{ $item->video_url }}" target="_blank" rel="noopener">Watch video</a></p>@endif
                        @if($item->notes)<p class="mt-1 text-sm"><strong>Notes:</strong> {{ $item->notes }}</p>@endif
                        @if(!empty($item->images))<div class="mt-3 flex flex-wrap gap-2">@foreach($item->images as $image)<img src="{{ $image }}" alt="{{ $item->location_name }}" class="h-24 w-32 rounded-xl object-cover" loading="lazy">@endforeach</div>@endif
                        @if($item->entrance_included)<small class="text-emerald-700">Entrance included</small>@endif
                      </li>
                    @endforeach
                  </ol>
                </article>
              @endforeach
            </div>
          @endif
          @foreach(['included'=>'What is included','excluded'=>'What is not included','what_to_bring'=>'What to bring','document_requirement'=>'Document requirements','children'=>'Information for children','seniors'=>'Information for seniors','faq'=>'Frequently asked questions'] as $type => $heading)
            @php $items = $tour->contentItems->where('type', $type); @endphp
            @if($items->isNotEmpty())
              <h2 class="mt-12 font-display text-2xl font-bold">{{ $heading }}</h2>
              <ul class="mt-4 space-y-2">@foreach($items as $item)<li class="rounded-xl bg-sand-50 p-4"><b>{{ $item->title }}</b>@if($item->description)<p class="mt-1 text-sm text-ink-500">{{ $item->description }}</p>@endif</li>@endforeach</ul>
            @endif
          @endforeach
          @if($pickupPoints->isNotEmpty())
            <h2 id="pickup-points" class="mt-12 scroll-mt-28 font-display text-2xl font-bold">Pick-up points</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
              @foreach($pickupPoints as $point)
                <li class="rounded-2xl bg-sand-50 p-4">
                  <b>{{ $point->name }}</b>
                  @if($point->address)<p class="mt-1 text-sm text-ink-500">{{ $point->address }}</p>@endif
                  @if($point->instructions)<p class="mt-1 text-sm text-ink-500">{{ $point->instructions }}</p>@endif
                  @if($point->map_url)<a href="{{ $point->map_url }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm text-brand-600 underline">Open in Maps</a>@endif
                </li>
              @endforeach
            </ul>
          @endif
          @foreach(['cancellation_policy'=>'Cancellation policy','refund_policy'=>'Refund policy','boarding_rules'=>'Boarding rules','baggage_rules'=>'Baggage rules','important_notes'=>'Important notes'] as $field => $heading)
            @if($tour->{$field})<h2 class="mt-10 font-display text-2xl font-bold">{{ $heading }}</h2><p class="mt-3">{{ $tour->{$field} }}</p>@endif
          @endforeach
        </div>
        <aside id="booking" class="scroll-mt-28">
          <div class="sticky top-28 rounded-3xl bg-sand-50 p-6 ring-1 ring-ink-900/10">
            <h2 class="font-display text-2xl font-bold">Book this tour</h2>
            @if(session('message'))<div class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('message') }}</div>@endif
            @if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('front.bookings.store') }}" class="mt-5 space-y-3" x-data="{ count: {{ (int) old('participant_count', 1) }}, passengers: [], sync(){ const needed=Math.max(0,Number(this.count||1)-1); while(this.passengers.length<needed)this.passengers.push({}); this.passengers=this.passengers.slice(0,needed) } }" x-init="sync()">
              @csrf
              <input type="hidden" name="type" value="{{ $tour->is_cruise ? 'cruise' : 'tour' }}"><input type="hidden" name="tour_id" value="{{ $tour->id }}">
              <div class="grid grid-cols-2 gap-2"><input class="rounded-xl border p-3" name="first_name" value="{{ old('first_name') }}" placeholder="First name" required><input class="rounded-xl border p-3" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" required></div>
              <input class="w-full rounded-xl border p-3" name="email" type="email" value="{{ old('email') }}" placeholder="Email" required><input class="w-full rounded-xl border p-3" name="phone" value="{{ old('phone') }}" placeholder="Phone" required>
              <div class="grid grid-cols-2 gap-2"><input class="rounded-xl border p-3" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="WhatsApp"><input class="rounded-xl border p-3" type="number" min="0" max="120" name="age" value="{{ old('age') }}" placeholder="Age"></div>
              <input class="w-full rounded-xl border p-3" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" aria-label="Date of birth"><input class="w-full rounded-xl border p-3" name="address" value="{{ old('address') }}" placeholder="Address">
              <select class="w-full rounded-xl border p-3" name="tour_departure_id" required><option value="">Select departure</option>@foreach($tour->departures as $departure)<option value="{{ $departure->id }}" @selected(old('tour_departure_id') == $departure->id)>{{ $departure->departs_at->format('M j, Y') }} — {{ $departure->price }} {{ $departure->currency }} — {{ $departure->available_seats }} seats</option>@endforeach</select>
              <input class="w-full rounded-xl border p-3" type="number" min="1" max="100" name="participant_count" x-model.number="count" @input="sync()" required aria-label="Number of participants">
              <select class="w-full rounded-xl border p-3" name="pickup_point_id"><option value="">Select pickup point</option>@foreach($pickupPoints as $point)<option value="{{ $point->id }}" @selected(old('pickup_point_id') == $point->id)>{{ $point->name }}</option>@endforeach</select>
              <textarea class="w-full rounded-xl border p-3" name="comments" placeholder="Comments">{{ old('comments') }}</textarea><textarea class="w-full rounded-xl border p-3" name="special_requests" placeholder="Special requests">{{ old('special_requests') }}</textarea>
              <textarea class="w-full rounded-xl border p-3" name="mobility_information" placeholder="Limited mobility information">{{ old('mobility_information') }}</textarea><textarea class="w-full rounded-xl border p-3" name="food_allergies" placeholder="Food allergies">{{ old('food_allergies') }}</textarea>
              <div class="grid grid-cols-2 gap-2"><input class="rounded-xl border p-3" name="meal_selection" value="{{ old('meal_selection') }}" placeholder="Meal selection"><input class="rounded-xl border p-3" name="promo_code" value="{{ old('promo_code') }}" placeholder="Promo code"></div>
              <select class="w-full rounded-xl border p-3" name="source"><option value="">How did you hear about us?</option>@foreach(['Google','Facebook','Instagram','Friend or family','Returning customer','Other'] as $source)<option @selected(old('source')===$source)>{{ $source }}</option>@endforeach</select>

              <template x-for="(passenger,index) in passengers" :key="index">
                <fieldset class="space-y-2 rounded-2xl border border-ink-200 p-3"><legend class="px-1 font-display font-bold" x-text="'Additional passenger '+(index+1)"></legend>
                  <div class="grid grid-cols-2 gap-2"><input class="rounded-xl border p-2" :name="`passengers[${index}][first_name]`" placeholder="First name" required><input class="rounded-xl border p-2" :name="`passengers[${index}][last_name]`" placeholder="Last name" required></div>
                  <div class="grid grid-cols-2 gap-2"><input class="rounded-xl border p-2" type="number" min="0" max="120" :name="`passengers[${index}][age]`" placeholder="Age"><input class="rounded-xl border p-2" type="date" :name="`passengers[${index}][date_of_birth]`" aria-label="Passenger date of birth"></div>
                  <div class="grid grid-cols-2 gap-2"><input class="rounded-xl border p-2" :name="`passengers[${index}][phone]`" placeholder="Phone"><input class="rounded-xl border p-2" type="email" :name="`passengers[${index}][email]`" placeholder="Email"></div>
                  <select class="w-full rounded-xl border p-2" :name="`passengers[${index}][category]`" required>@foreach(['adult','child','infant','senior','accompanying_person','complimentary','driver','group_leader'] as $category)<option value="{{ $category }}">{{ str($category)->replace('_',' ')->title() }}</option>@endforeach</select>
                  <select class="w-full rounded-xl border p-2" :name="`passengers[${index}][pickup_point_id]`"><option value="">Same pickup point</option>@foreach($pickupPoints as $point)<option value="{{ $point->id }}">{{ $point->name }}</option>@endforeach</select>
                  <input class="w-full rounded-xl border p-2" :name="`passengers[${index}][meal_selection]`" placeholder="Meal selection"><textarea class="w-full rounded-xl border p-2" :name="`passengers[${index}][notes]`" placeholder="Notes"></textarea>
                </fieldset>
              </template>
              <button class="btn btn-primary w-full">Submit booking request</button>
            </form>
          </div>
        </aside>
      </div>
    </section>
  </main>
  @include('front.partials.footer')
</body>
</html>
