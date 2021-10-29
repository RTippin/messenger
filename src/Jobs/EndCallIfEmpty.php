<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Events\CallLeftEvent;
use Throwable;

class EndCallIfEmpty extends BaseMessengerJob
{
    /**
     * @var CallLeftEvent
     */
    public CallLeftEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  CallLeftEvent  $event
     */
    public function __construct(CallLeftEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param  EndCall  $endCall
     * @return void
     *
     * @throws Throwable
     */
    public function handle(EndCall $endCall): void
    {
        if (! $this->event->call->participants()->inCall()->count()) {
            $endCall->execute($this->event->call);
        }
    }
}
