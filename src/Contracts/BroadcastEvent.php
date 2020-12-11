<?php

namespace RTippin\Messenger\Contracts;

interface BroadcastEvent
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array;

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array;

    /**
     * Set the data we will use to broadcast out.
     *
     * @param array $resource
     * @return self
     */
    public function setResource(array $resource): self;

    /**
     * Set the channels we will use to broadcast on.
     *
     * @param array $channels
     * @return self
     */
    public function setChannels(array $channels): self;
}
