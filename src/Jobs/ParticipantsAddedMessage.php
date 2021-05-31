<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class ParticipantsAddedMessage implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var ParticipantsAddedEvent
     */
    private ParticipantsAddedEvent $event;

    /**
     * Create a new job instance.
     *
     * @param ParticipantsAddedEvent $event
     */
    public function __construct(ParticipantsAddedEvent $event)
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
        return MessageTransformer::makeParticipantsAdded(
            $this->event->thread,
            $this->event->provider,
            $this->event->participants
        );
    }
}
