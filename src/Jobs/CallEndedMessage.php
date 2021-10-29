<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class CallEndedMessage extends BaseMessengerJob
{
    /**
     * @var CallEndedEvent
     */
    public CallEndedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  CallEndedEvent  $event
     */
    public function __construct(CallEndedEvent $event)
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
        return MessageTransformer::makeVideoCall(
            $this->event->call->thread,
            $this->event->call->owner,
            $this->event->call
        );
    }
}
