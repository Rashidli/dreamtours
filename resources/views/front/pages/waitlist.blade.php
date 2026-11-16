@php
  $input = 'w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500';
  $label = 'mb-1.5 block text-sm font-semibold text-ink-800';
@endphp
<!doctype html>
<html lang="en">
<head>
  @include('front.partials.head_assets')
  <title>Waitlist — {{ $tour->name }} | {{ $siteSettings->get('site_name', 'DreamTour') }}</title>
  <meta name="robots" content="noindex">
</head>
<body class="bg-sand-50 text-ink-800 antialiased">
  @include('front.partials.header')
  <main id="main" class="py-10 lg:py-16">
    <div class="container-page max-w-3xl">
      @if($joined)
        <div class="rounded-3xl bg-white p-6 text-center shadow-card ring-1 ring-ink-900/5 sm:p-10">
          <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-3xl text-emerald-600" aria-hidden="true">✓</span>
          <h1 class="mt-4 font-display text-3xl font-extrabold text-ink-900">You're on the waitlist, {{ $joined }}</h1>
          <p class="mx-auto mt-2 max-w-xl text-ink-600">If a seat opens on <b>{{ $tour->name }}</b> on <b>{{ $departure->departs_at->format('l, F j, Y') }}</b>, a DreamTour representative will contact you in the order people joined. Nothing is booked or charged until then.</p>
          <div class="mt-6 flex flex-wrap justify-center gap-3"><a href="{{ route('front.tours.show', $tour->slug) }}" class="btn btn-primary">Back to the tour</a><a href="/" class="btn btn-ghost btn-sm">Home</a></div>
        </div>
      @else
        <div class="rounded-3xl bg-white p-6 shadow-card ring-1 ring-ink-900/5 sm:p-10">
          <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Sold out</p>
          <h1 class="mt-1 font-display text-3xl font-extrabold text-ink-900">Join the waitlist</h1>
          <p class="mt-2 text-ink-600"><b>{{ $tour->name }}</b> · {{ $departure->departs_at->format('l, F j, Y · g:i A') }}. This date is full. Leave your details and we will contact you if seats become available — no payment now.</p>
          @if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
          <form method="post" action="{{ route('front.waitlist.store', ['slug' => $tour->slug, 'departure' => $departure->id]) }}" class="mt-6 grid gap-4 sm:grid-cols-2">@csrf
            <div><label class="{{ $label }}" for="w-first">First name</label><input id="w-first" name="first_name" value="{{ old('first_name') }}" class="{{ $input }}" required maxlength="100" autocomplete="given-name"></div>
            <div><label class="{{ $label }}" for="w-last">Last name</label><input id="w-last" name="last_name" value="{{ old('last_name') }}" class="{{ $input }}" required maxlength="100" autocomplete="family-name"></div>
            <div><label class="{{ $label }}" for="w-email">Email</label><input id="w-email" type="email" name="email" value="{{ old('email') }}" class="{{ $input }}" required autocomplete="email" placeholder="you@example.com"></div>
            <div><label class="{{ $label }}" for="w-phone">Phone</label><input id="w-phone" name="phone" value="{{ old('phone') }}" class="{{ $input }}" required maxlength="30" autocomplete="tel"></div>
            <div><label class="{{ $label }}" for="w-guests">Seats needed</label><input id="w-guests" type="number" min="1" max="20" name="guests" value="{{ old('guests', 1) }}" class="{{ $input }}" required></div>
            <div class="sm:col-span-2"><label class="{{ $label }}" for="w-note">Anything we should know? <span class="font-normal text-ink-500">(optional)</span></label><input id="w-note" name="note" value="{{ old('note') }}" class="{{ $input }}" maxlength="500" placeholder="e.g. Another date would also work"></div>
            <div class="hidden" aria-hidden="true"><label for="w-website">Website</label><input id="w-website" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="sm:col-span-2 flex flex-wrap items-center gap-3"><button class="btn btn-primary">Join the waitlist</button><a href="{{ route('front.tours.show', $tour->slug) }}" class="btn btn-ghost btn-sm">Back to the tour</a></div>
          </form>
        </div>
      @endif
    </div>
  </main>
  @include('front.partials.footer')
</body>
</html>
