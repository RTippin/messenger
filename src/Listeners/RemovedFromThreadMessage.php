<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class RemovedFromThreadMessage implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public string $queue = 'messenger';

    /**
     * @var StoreSystemMessage
     */
    private StoreSystemMessage $storeSystemMessage;

    /**
     * Create the event listener.
     *
     * @param StoreSystemMessage $storeSystemMessage
     */
    public function __construct(StoreSystemMessage $storeSystemMessage)
    {
        $this->storeSystemMessage = $storeSystemMessage;
    }

    /**
     * Handle the event.
     *
     * @param RemovedFromThreadEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(RemovedFromThreadEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param RemovedFromThreadEvent $event
     * @return array
     */
    private function systemMessage(RemovedFromThreadEvent $event): array
    {
        return MessageTransformer::makeRemovedFromGroup($event->thread, $event->provider, $event->participant);
    }
}
