<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class RemovedFromThreadMessage extends BaseMessengerJob
{
    /**
     * @var RemovedFromThreadEvent
     */
    public RemovedFromThreadEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  RemovedFromThreadEvent  $event
     */
    public function __construct(RemovedFromThreadEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param  StoreSystemMessage  $message
     * @return void
     *
     * @throws Throwable
     */
    public function handle(StoreSystemMessage $message): void
    {
        $message->execute(...$this->systemMessage());
    }

    /**
     * @return array
     */
    private function systemMessage(): array
    {
        return MessageTransformer::makeRemovedFromGroup(
            $this->event->thread,
            $this->event->provider,
            $this->event->participant
        );
    }
}
