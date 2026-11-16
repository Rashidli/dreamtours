<?php

namespace App\Console\Commands;

use App\Support\Alerts;
use Illuminate\Console\Command;

class SweepAlerts extends Command
{
    protected $signature = 'crm:alerts';
    protected $description = 'Raise time-based staff alerts (balances due soon, upcoming tours) and close stale ones';

    public function handle(): int
    {
        Alerts::sweep();
        $this->info('Staff alerts checked.');

        return self::SUCCESS;
    }
}
