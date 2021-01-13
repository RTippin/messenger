<?php

namespace RTippin\Messenger\Broadcasting;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\BroadcastEvent;

abstract class MessengerBroadcast implements BroadcastEvent, ShouldBroadcastNow
{
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var array
     */
    protected array $resource;

    /**
     * @var array
     */
    protected array $channels;

    /**
     * @inheritDoc
     */
    abstract public function broadcastAs(): string;

    /**
     * @inheritDoc
     */
    public function broadcastOn(): array
    {
        return $this->channels;
    }

    /**
     * @inheritDoc
     */
    public function broadcastWith(): array
    {
        return $this->resource;
    }

    /**
     * @inheritDoc
     */
    public function setResource(array $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setChannels(array $channels): self
    {
        $this->channels = $channels;

        return $this;
    }
}
