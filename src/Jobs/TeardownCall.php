<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Calls\CallBrokerTeardown;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Exceptions\CallBrokerException;

class TeardownCall extends BaseMessengerJob
{
    /**
     * @var CallEndedEvent
     */
    public CallEndedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  CallEndedEvent  $event
     */
    public function __construct(CallEndedEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param  CallBrokerTeardown  $broker
     * @return void
     *
     * @throws CallBrokerException
     */
    public function handle(CallBrokerTeardown $broker): void
    {
        $broker->execute($this->event->call);
    }
}
