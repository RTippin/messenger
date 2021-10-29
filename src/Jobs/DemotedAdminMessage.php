<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class DemotedAdminMessage extends BaseMessengerJob
{
    /**
     * @var DemotedAdminEvent
     */
    public DemotedAdminEvent $event;

    /**
     * Create a new job instance.
     *
     * @param  DemotedAdminEvent  $event
     */
    public function __construct(DemotedAdminEvent $event)
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
        return MessageTransformer::makeParticipantDemoted(
            $this->event->thread,
            $this->event->provider,
            $this->event->participant
        );
    }
}
