<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadLeftEvent;
use Throwable;

class ThreadLeftMessage implements ShouldQueue
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
     * @param ThreadLeftEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(ThreadLeftEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param ThreadLeftEvent $event
     * @return array
     */
    private function systemMessage(ThreadLeftEvent $event): array
    {
        return [
            $event->thread,
            $event->provider,
            'left',
            'PARTICIPANT_LEFT_GROUP',
        ];
    }
}
