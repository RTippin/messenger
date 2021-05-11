<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Services\SystemMessageService;
use Throwable;

class ThreadNameMessage implements ShouldQueue
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
     * @param ThreadSettingsEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(ThreadSettingsEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param ThreadSettingsEvent $event
     * @return array
     */
    private function systemMessage(ThreadSettingsEvent $event): array
    {
        return $this->service
            ->setStoreData($event->thread, $event->provider)
            ->makeGroupRenamed($event->thread->subject);
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param ThreadSettingsEvent $event
     * @return bool
     */
    public function shouldQueue(ThreadSettingsEvent $event): bool
    {
        return $event->nameChanged;
    }
}
