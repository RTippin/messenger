<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Broadcasting\ReactionRemovedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ReactionRemovedEvent;
use RTippin\Messenger\Models\Message;

class MessageUnReacted implements ShouldQueue
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
     * @param ReactionRemovedEvent $event
     * @return void
     */
    public function handle(ReactionRemovedEvent $event): void
    {
        $this->broadcaster
            ->to(Message::findOrFail($event->reaction['message_id'])->owner)
            ->with($this->generateBroadcastResource($event))
            ->broadcast(ReactionRemovedBroadcast::class);
    }

    /**
     * @param ReactionRemovedEvent $event
     * @return array
     */
    private function generateBroadcastResource(ReactionRemovedEvent $event): array
    {
        return [
            'message_id' => $event->reaction['message_id'],
            'reaction_id' => $event->reaction['id'],
            'reaction' => $event->reaction['reaction'],
        ];
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param ReactionRemovedEvent $event
     * @return bool
     */
    public function shouldQueue(ReactionRemovedEvent $event): bool
    {
        return ! $event->isMessageOwner;
    }
}
