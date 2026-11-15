<?php

namespace App\Mail\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued email that survives a mail-server outage: five attempts spread over about
 * an hour and a half, then a logged failure (the job also stays in failed_jobs, so
 * `php artisan queue:retry all` can send it later).
 */
trait RetriesDelivery
{
    public int $tries = 5;

    /** Seconds to wait before attempts 2–5. */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function failed(Throwable $e): void
    {
        Log::error('Email could not be delivered', [
            'mail' => static::class,
            'to' => collect($this->to)->pluck('address')->all(),
            'subject' => $this->subject,
            'error' => $e->getMessage(),
        ]);
    }
}
