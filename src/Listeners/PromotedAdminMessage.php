<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\PromotedAdminEvent;
use Throwable;

class PromotedAdminMessage implements ShouldQueue
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
     * @param PromotedAdminEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(PromotedAdminEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param PromotedAdminEvent $event
     * @return array
     */
    private function systemMessage(PromotedAdminEvent $event): array
    {
        return [
            $event->thread,
            $event->provider,
            $this->messageBody($event),
            'PROMOTED_ADMIN'
        ];
    }

    /**
     * @param PromotedAdminEvent $event
     * @return string
     */
    private function messageBody(PromotedAdminEvent $event): string
    {
        return collect([
            "owner_id" => $event->participant->owner_id,
            "owner_type" => $event->participant->owner_type
        ])->toJson();
    }
}
