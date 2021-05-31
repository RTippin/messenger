<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Calls\CallBrokerTeardown;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Exceptions\CallBrokerException;

class TeardownCall implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var CallEndedEvent
     */
    private CallEndedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param CallEndedEvent $event
     */
    public function __construct(CallEndedEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param CallBrokerTeardown $broker
     * @return void
     * @throws CallBrokerException
     */
    public function handle(CallBrokerTeardown $broker): void
    {
        $broker->execute($this->event->call);
    }
}
