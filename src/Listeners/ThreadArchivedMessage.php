<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use Throwable;

class ThreadArchivedMessage implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'messenger';

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
        return [
            $event->thread,
            $event->provider,
            $this->messageBody($event),
            'THREAD_ARCHIVED'
        ];
    }

    /**
     * @param ThreadArchivedEvent $event
     * @return string
     */
    private function messageBody(ThreadArchivedEvent $event): string
    {
        return $event->thread->isGroup()
            ? 'archived the group'
            : 'archived the conversation';
    }
}
