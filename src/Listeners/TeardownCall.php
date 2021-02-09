<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Calls\CallBrokerTeardown;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Exceptions\CallBrokerException;

class TeardownCall implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public string $queue = 'messenger';

    /**
     * @var CallBrokerTeardown
     */
    private CallBrokerTeardown $callBrokerTeardown;

    /**
     * Create the event listener.
     *
     * @param CallBrokerTeardown $callBrokerTeardown
     */
    public function __construct(CallBrokerTeardown $callBrokerTeardown)
    {
        $this->callBrokerTeardown = $callBrokerTeardown;
    }

    /**
     * Handle the event.
     *
     * @param CallEndedEvent $event
     * @return void
     * @throws CallBrokerException
     */
    public function handle(CallEndedEvent $event): void
    {
        $this->callBrokerTeardown->execute($event->call);
    }
}
