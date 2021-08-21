<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\Middleware\ResetMessenger;
use Throwable;

abstract class BaseMessengerJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new ResetMessenger];
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $this->flushMessenger();
    }

    /**
     * Flush any active provider set, and reset our configs to default values.
     */
    protected function flushMessenger(): void
    {
        Messenger::flush();
    }
}
