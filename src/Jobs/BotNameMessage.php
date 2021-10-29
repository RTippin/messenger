<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\BotUpdatedEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class BotNameMessage extends BaseMessengerJob
{
    /**
     * @var BotUpdatedEvent
     */
    public BotUpdatedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  BotUpdatedEvent  $event
     */
    public function __construct(BotUpdatedEvent $event)
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
        return MessageTransformer::makeBotRenamed(
            $this->event->bot->thread,
            $this->event->provider,
            $this->event->originalName,
            $this->event->bot->name
        );
    }
}
