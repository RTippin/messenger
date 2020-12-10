<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class StoreParticipant extends ThreadParticipantAction
{
    /**
     * Store a single, fresh participant for the provided thread.
     *
     * @param mixed ...$parameters
     * @var Thread $parameters[0]
     * @var MessengerProvider $parameters[1]
     * @var array|null $parameters[2]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->storeParticipant(
                $parameters[1],
                $parameters[2] ?? []
            );

        return $this;
    }
}
