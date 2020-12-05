<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\CallEndedEvent;
use Throwable;

class CallEndedMessage implements ShouldQueue
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
     * @param CallEndedEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(CallEndedEvent $event): void
    {
        $this->storeSystemMessage->execute(...$this->systemMessage($event));
    }

    /**
     * @param CallEndedEvent $event
     * @return array
     */
    private function systemMessage(CallEndedEvent $event): array
    {
        return [
            $event->call->thread,
            $event->call->owner,
            collect(["call_id" => $event->call->id])->toJson(),
            Definitions::Call[$event->call->type] . '_CALL'
        ];
    }
}
