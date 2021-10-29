<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class JoinedWithInviteMessage extends BaseMessengerJob
{
    /**
     * @var InviteUsedEvent
     */
    public InviteUsedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  InviteUsedEvent  $event
     */
    public function __construct(InviteUsedEvent $event)
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
        return MessageTransformer::makeJoinedWithInvite(
            $this->event->thread,
            $this->event->provider
        );
    }
}
