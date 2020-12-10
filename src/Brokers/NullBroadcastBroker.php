<?php

namespace RTippin\Messenger\Brokers;

use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Models\Thread;

class NullBroadcastBroker implements BroadcastDriver
{
    /**
     * @inheritDoc
     */
    public function toAllInThread(Thread $thread): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toOthersInThread(Thread $thread): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toSelected(Collection $recipients): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function to($recipient): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toPresence($entity): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toManyPresence(Collection $presence): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with(array $with): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function broadcast(string $abstract): void
    {
        //
    }
}
