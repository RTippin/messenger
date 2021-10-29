<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class BotAvatarMessage extends BaseMessengerJob
{
    /**
     * @var BotAvatarEvent
     */
    public BotAvatarEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  BotAvatarEvent  $event
     */
    public function __construct(BotAvatarEvent $event)
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
        return MessageTransformer::makeBotAvatarChanged(
            $this->event->bot->thread,
            $this->event->provider,
            $this->event->bot->name,
        );
    }
}
