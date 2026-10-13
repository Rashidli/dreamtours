<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Seat numbers go out 1–2 days before departure. Requires the standard
// `* * * * * php artisan schedule:run` cron entry on the server.
Illuminate\Support\Facades\Schedule::command('bookings:send-seat-notices --days=2')->dailyAt('09:00');
