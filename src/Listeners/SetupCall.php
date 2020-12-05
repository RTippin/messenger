<?php

namespace RTippin\Messenger\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Calls\CallBrokerSetup;
use RTippin\Messenger\Events\CallStartedEvent;

class SetupCall implements ShouldQueue
{
    /**
     * @var CallBrokerSetup
     */
    private CallBrokerSetup $callBrokerSetup;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public string $queue = 'messenger';

    /**
     * Create the event listener.
     *
     * @param CallBrokerSetup $callBrokerSetup
     */
    public function __construct(CallBrokerSetup $callBrokerSetup)
    {
        $this->callBrokerSetup = $callBrokerSetup;
    }

    /**
     * Handle the event.
     *
     * @param CallStartedEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(CallStartedEvent $event): void
    {
        $this->callBrokerSetup->execute(
            $event->thread,
            $event->call
        );
    }
}
