<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class ThreadAvatarMessage extends BaseMessengerJob
{
    /**
     * @var ThreadAvatarEvent
     */
    public ThreadAvatarEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  ThreadAvatarEvent  $event
     */
    public function __construct(ThreadAvatarEvent $event)
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
        return MessageTransformer::makeGroupAvatarChanged(
            $this->event->thread,
            $this->event->provider
        );
    }
}
