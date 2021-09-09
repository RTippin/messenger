<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;

trait BroadcastLogger
{
    /**
     * Location of our responses file for storing test case responses.
     */
    private string $broadcastFile = __DIR__.'/../docs/generated/broadcast.json';

    /**
     * Store the given broadcast event.
     *
     * @param  MessengerBroadcast|string  $event
     * @param  string|null  $context
     */
    public function logBroadcast($event, ?string $context = null): void
    {
        if (! $this->withBroadcastLogging) {
            return;
        }

        Event::dispatched($event)
            ->flatten()
            ->each(fn (MessengerBroadcast $broadcast) => $this->storeBroadcast($broadcast, $context));
    }

    /**
     * @param  MessengerBroadcast  $event
     * @param  string|null  $context
     */
    private function storeBroadcast(MessengerBroadcast $event, ?string $context): void
    {
        $broadcast = $this->getBroadcastFile();
        $base = class_basename($event);

        $broadcast[$base]['name'] = $event->broadcastAs();
        $broadcast[$base]['broadcast'][] = [
            'channels' => $event->broadcastOn(),
            'context' => $context,
            'data' => $event->broadcastWith(),
        ];

        $this->storeBroadcastFile($broadcast);
    }

    /**
     * @return array
     */
    private function getBroadcastFile(): array
    {
        if (! file_exists($this->broadcastFile)) {
            return [];
        }

        return json_decode(file_get_contents($this->broadcastFile), true) ?: [];
    }

    /**
     * @param  array  $broadcast
     */
    private function storeBroadcastFile(array $broadcast): void
    {
        file_put_contents($this->broadcastFile, json_encode($broadcast));
    }
}
