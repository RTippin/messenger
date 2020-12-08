<?php

namespace RTippin\Messenger\Broadcasting\Base;

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
    abstract public function broadcastAs();

    /**
     * @inheritDoc
     */
    public function broadcastOn()
    {
        return $this->channels;
    }

    /**
     * @inheritDoc
     */
    public function broadcastWith()
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
