<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class CallEndedMessage implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var CallEndedEvent
     */
    private CallEndedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param CallEndedEvent $event
     */
    public function __construct(CallEndedEvent $event)
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
        return MessageTransformer::makeVideoCall(
            $this->event->call->thread,
            $this->event->call->owner,
            $this->event->call
        );
    }
}
