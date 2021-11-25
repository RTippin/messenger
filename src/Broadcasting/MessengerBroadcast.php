<?php

namespace RTippin\Messenger\Broadcasting;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

abstract class MessengerBroadcast implements ShouldBroadcastNow
{
    use InteractsWithSockets,
        SerializesModels;

    /**
     * @var array
     */
    protected array $resource;

    /**
     * @var array
     */
    protected array $channels;

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    abstract public function broadcastAs(): string;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return $this->channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return $this->resource;
    }

    /**
     * Set the data we will use to broadcast out.
     *
     * @param  array  $resource
     * @return self
     */
    public function setResource(array $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set the channels we will use to broadcast on.
     *
     * @param  array  $channels
     * @return self
     */
    public function setChannels(array $channels): self
    {
        $this->channels = $channels;

        return $this;
    }
}
