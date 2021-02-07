<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Events\MessageEditedEvent;
use Throwable;

class StoreMessageEdit implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public string $queue = 'messenger';

    /**
     * Handle the event.
     *
     * @param MessageEditedEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(MessageEditedEvent $event): void
    {
        $event->message->edits()->create([
            'body' => $event->originalBody,
            'edited_at' => $event->message->updated_at,
        ]);
    }
}
