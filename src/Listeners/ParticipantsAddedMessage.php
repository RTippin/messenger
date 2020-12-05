<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use Throwable;

class ParticipantsAddedMessage implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public string $queue = 'messenger';

    /**
     * @var StoreSystemMessage
     */
    private StoreSystemMessage $storeSystemMessage;

    /**
     * Create the event listener.
     *
     * @param StoreSystemMessage $storeSystemMessage
     */
    public function __construct(StoreSystemMessage $storeSystemMessage)
    {
        $this->storeSystemMessage = $storeSystemMessage;
    }

    /**
     * Handle the event.
     *
     * @param ParticipantsAddedEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(ParticipantsAddedEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param ParticipantsAddedEvent $event
     * @return array
     */
    private function systemMessage(ParticipantsAddedEvent $event): array
    {
        return [
            $event->thread,
            $event->provider,
            $this->messageBody($event),
            'PARTICIPANTS_ADDED'
        ];
    }

    /**
     * @param ParticipantsAddedEvent $event
     * @return string
     */
    private function messageBody(ParticipantsAddedEvent $event): string
    {
        return $event->participants->map(
            fn($item) => [
                'owner_id' => $item['owner_id'],
                'owner_type' => $item['owner_type']
            ])
            ->toJson();
    }
}
