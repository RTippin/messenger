<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Http\Resources\MessageReactionResource;

class MessageReacted implements ShouldQueue
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public string $queue = 'messenger';

    /**
     * Create the event listener.
     *
     * @param BroadcastDriver $broadcaster
     */
    public function __construct(BroadcastDriver $broadcaster)
    {
        $this->broadcaster = $broadcaster;
    }

    /**
     * Handle the event.
     *
     * @param ReactionAddedEvent $event
     * @return void
     */
    public function handle(ReactionAddedEvent $event): void
    {
        $this->broadcaster
            ->to($event->reaction->message->owner)
            ->with($this->generateBroadcastResource($event))
            ->broadcast(ReactionAddedBroadcast::class);
    }

    /**
     * @param ReactionAddedEvent $event
     * @return array
     */
    private function generateBroadcastResource(ReactionAddedEvent $event): array
    {
        return (new MessageReactionResource(
            $event->reaction,
            $event->reaction->message
        ))->resolve();
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param ReactionAddedEvent $event
     * @return bool
     */
    public function shouldQueue(ReactionAddedEvent $event): bool
    {
        return $event->isMessageOwner === false;
    }
}
