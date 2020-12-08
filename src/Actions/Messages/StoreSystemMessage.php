<?php

namespace RTippin\Messenger\Actions\Messages;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use Throwable;

class StoreSystemMessage extends NewMessageAction
{
    /**
     * Store new system message, update thread updated_at.
     *
     * @param mixed ...$parameters
     * @var Thread $parameters[0]
     * @var MessengerProvider $parameters[1]
     * @var string $parameters[2]
     * @var string $parameters[3]
     * @return $this
     * @throws Throwable
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0]);

        $this->handleTransactions(
            $parameters[1],
            $parameters[3],
            $parameters[2],
            null
        )
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }
}
