<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Calls\CallBrokerSetup;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Exceptions\CallBrokerException;

class SetupCall implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var CallStartedEvent
     */
    private CallStartedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param CallStartedEvent $event
     */
    public function __construct(CallStartedEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param CallBrokerSetup $broker
     * @return void
     * @throws CallBrokerException
     */
    public function handle(CallBrokerSetup $broker): void
    {
        $broker->execute(
            $this->event->thread,
            $this->event->call
        );
    }
}
