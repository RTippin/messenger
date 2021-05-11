<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class DemotedAdminMessage implements ShouldQueue
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
     * @param DemotedAdminEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(DemotedAdminEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param DemotedAdminEvent $event
     * @return array
     */
    private function systemMessage(DemotedAdminEvent $event): array
    {
        return MessageTransformer::makeParticipantDemoted($event->thread, $event->provider, $event->participant);
    }
}
