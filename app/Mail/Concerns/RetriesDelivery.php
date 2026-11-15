<?php

namespace App\Mail\Concerns;

use App\Support\Activity;
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
        Activity::record('email_failed', $this->booking ?? $this->enquiry ?? null, [
            'to' => collect($this->to)->pluck('address')->implode(', '),
            'email' => class_basename($this),
            'error' => $e->getMessage(),
        ]);
        Log::error('Email could not be delivered', [
            'mail' => static::class,
            'to' => collect($this->to)->pluck('address')->all(),
            'subject' => $this->subject,
            'error' => $e->getMessage(),
        ]);
    }
}
