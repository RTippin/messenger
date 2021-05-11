<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Services\SystemMessageService;
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
     * @var SystemMessageService
     */
    private SystemMessageService $service;

    /**
     * @var StoreSystemMessage
     */
    private StoreSystemMessage $storeSystemMessage;

    /**
     * Create the event listener.
     *
     * @param SystemMessageService $service
     * @param StoreSystemMessage $storeSystemMessage
     */
    public function __construct(SystemMessageService $service, StoreSystemMessage $storeSystemMessage)
    {
        $this->storeSystemMessage = $storeSystemMessage;
        $this->service = $service;
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
        return $this->service
            ->setStoreData($event->thread, $event->provider)
            ->makeRemovedFromGroup($event->participant);
    }
}
