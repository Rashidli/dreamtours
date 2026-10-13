<!doctype html>
<html lang="en">
<head>
  @include('front.partials.head_assets')
  <title>Booking received — {{ $booking->reference }} | {{ $siteSettings->get('site_name', 'DreamTour') }}</title>
  <meta name="robots" content="noindex">
</head>
<body class="bg-sand-50 text-ink-800 antialiased">
  @include('front.partials.header')
  <main id="main" class="py-10 lg:py-16">
    <div class="container-page max-w-3xl">
      <div class="rounded-3xl bg-white p-6 text-center shadow-card ring-1 ring-ink-900/5 sm:p-10">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-3xl text-emerald-600" aria-hidden="true">✓</span>
        <h1 class="mt-4 font-display text-3xl font-extrabold text-ink-900">Your request has been received</h1>
        <p class="mx-auto mt-2 max-w-xl text-ink-600">A DreamTour representative will contact you to confirm your booking after checking your deposit. A copy of this summary was sent to <b>{{ $booking->email }}</b>.</p>
        <p class="mt-6 text-sm uppercase tracking-wider text-ink-500">Booking reference</p>
        <p class="font-display text-3xl font-extrabold tracking-wide text-brand-600">{{ $booking->reference }}</p>
      </div>

      <div class="mt-6 grid gap-6 md:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 ring-1 ring-ink-900/5">
          <h2 class="font-display text-lg font-bold">Trip</h2>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-ink-500">Tour</dt><dd class="text-right font-semibold">{{ $booking->tour?->name }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-ink-500">Departure</dt><dd class="text-right font-semibold">{{ $booking->departure?->departs_at?->format('D, M j, Y g:i A') }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-ink-500">Pickup</dt><dd class="text-right font-semibold">{{ $booking->pickupPoint?->name }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-ink-500">Passengers</dt><dd class="text-right font-semibold">{{ $booking->participant_count }}</dd></div>
          </dl>
          <ul class="mt-4 space-y-1 border-t border-ink-100 pt-4 text-sm text-ink-600">
            @foreach($booking->passengers as $passenger)
              <li class="flex justify-between gap-3"><span>{{ $passenger->first_name }} {{ $passenger->last_name }}@if($passenger->room_number) <small class="text-ink-400">· room {{ $passenger->room_number }}</small>@endif</span><span>{{ money($passenger->price, $booking->currency) }}</span></li>
            @endforeach
          </ul>
        </div>
        <div class="rounded-3xl bg-white p-6 ring-1 ring-ink-900/5">
          <h2 class="font-display text-lg font-bold">Payment</h2>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-ink-500">Total {{ $siteSettings->get('price_tax_note') }}</dt><dd class="font-semibold">{{ money($booking->total_amount, $booking->currency) }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-500">Deposit</dt><dd class="font-semibold">{{ money($booking->deposit_amount, $booking->currency) }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-500">Balance</dt><dd class="font-semibold">{{ money($booking->total_amount - $booking->deposit_amount, $booking->currency) }}</dd></div>
          </dl>
          <p class="mt-4 rounded-2xl bg-amber-50 p-3 text-sm text-amber-900">Payment screenshot received — we are verifying it.</p>
        </div>
      </div>

      <div class="mt-6 rounded-3xl bg-ink-900 p-6 text-white">
        <h2 class="font-display text-lg font-bold">What happens next</h2>
        <ol class="mt-3 space-y-2 text-sm text-ink-100">
          <li>1. We check your deposit and confirm your booking by phone or email.</li>
          <li>2. Pay the remaining balance 14 days before departure.</li>
          <li>3. Your seat numbers are emailed 1–2 days before departure.</li>
        </ol>
        <div class="mt-5 flex flex-wrap gap-3">
          <a href="{{ route('front.tours.show', $booking->tour?->slug) }}" class="btn btn-white btn-sm">Back to the tour</a>
          <a href="/" class="btn btn-ghost btn-sm">Home</a>
        </div>
      </div>
    </div>
  </main>
  @include('front.partials.footer')
</body>
</html>
