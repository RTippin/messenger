<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Events\CallLeftEvent;
use Throwable;

class EndCallIfEmpty implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'messenger';

    /**
     * @var EndCall
     */
    private EndCall $endCall;

    /**
     * Create the event listener.
     *
     * @param EndCall $endCall
     */
    public function __construct(EndCall $endCall)
    {
        $this->endCall = $endCall;
    }

    /**
     * Handle the event.
     *
     * @param CallLeftEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(CallLeftEvent $event)
    {
        if( ! $event->call->participants()->inCall()->count())
        {
            $this->endCall->execute($event->call);
        }
    }
}
