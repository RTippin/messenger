<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use Throwable;

class ThreadAvatarMessage implements ShouldQueue
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
     * @param ThreadAvatarEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(ThreadAvatarEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param ThreadAvatarEvent $event
     * @return array
     */
    private function systemMessage(ThreadAvatarEvent $event): array
    {
        return [
            $event->thread,
            $event->provider,
            "updated the groups avatar",
            'GROUP_AVATAR_CHANGED'
        ];
    }
}
