<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\InviteUsedEvent;
use Throwable;

class JoinedWithInviteMessage implements ShouldQueue
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
     * @param InviteUsedEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(InviteUsedEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param InviteUsedEvent $event
     * @return array
     */
    private function systemMessage(InviteUsedEvent $event): array
    {
        return [
            $event->thread,
            $event->provider,
            'joined the group',
            'PARTICIPANT_JOINED_WITH_INVITE'
        ];
    }
}
