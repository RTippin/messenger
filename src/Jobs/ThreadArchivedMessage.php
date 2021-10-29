<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class ThreadArchivedMessage extends BaseMessengerJob
{
    /**
     * @var ThreadArchivedEvent
     */
    public ThreadArchivedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  ThreadArchivedEvent  $event
     */
    public function __construct(ThreadArchivedEvent $event)
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
        $message->withoutBroadcast()->execute(...$this->systemMessage());
    }

    /**
     * @return array
     */
    private function systemMessage(): array
    {
        return MessageTransformer::makeThreadArchived(
            $this->event->thread,
            $this->event->provider
        );
    }
}
