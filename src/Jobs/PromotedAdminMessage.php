<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class PromotedAdminMessage implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var PromotedAdminEvent
     */
    private PromotedAdminEvent $event;

    /**
     * Create a new job instance.
     *
     * @param PromotedAdminEvent $event
     */
    public function __construct(PromotedAdminEvent $event)
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
        return MessageTransformer::makeParticipantPromoted(
            $this->event->thread,
            $this->event->provider,
            $this->event->participant
        );
    }
}
