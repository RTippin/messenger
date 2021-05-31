<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Events\CallLeftEvent;
use Throwable;

class EndCallIfEmpty implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var CallLeftEvent
     */
    private CallLeftEvent $event;

    /**
     * Create a new job instance.
     *
     * @param CallLeftEvent $event
     */
    public function __construct(CallLeftEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param EndCall $endCall
     * @return void
     * @throws Throwable|InvalidArgumentException
     */
    public function handle(EndCall $endCall): void
    {
        if (! $this->event->call->participants()->inCall()->count()) {
            $endCall->execute($this->event->call);
        }
    }
}
