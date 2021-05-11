<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Services\SystemMessageService;
use Throwable;

class ThreadArchivedMessage implements ShouldQueue
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
     * @param ThreadArchivedEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(ThreadArchivedEvent $event): void
    {
        $this->storeSystemMessage
            ->withoutDispatches()
            ->execute(...$this->systemMessage($event));
    }

    /**
     * @param ThreadArchivedEvent $event
     * @return array
     */
    private function systemMessage(ThreadArchivedEvent $event): array
    {
        return $this->service
            ->setStoreData($event->thread, $event->provider)
            ->makeThreadArchived();
    }
}
