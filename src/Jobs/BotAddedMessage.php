<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\NewBotEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class BotAddedMessage implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var NewBotEvent
     */
    private NewBotEvent $event;

    /**
     * Create a new job instance.
     *
     * @param NewBotEvent $event
     */
    public function __construct(NewBotEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param StoreSystemMessage $message
     * @return void
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
        return MessageTransformer::makeBotAdded(
            $this->event->bot->thread,
            $this->event->bot->owner,
            $this->event->bot->getProviderName()
        );
    }
}
