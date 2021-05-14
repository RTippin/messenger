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
     * @var Thread[0]
     * @var MessengerProvider[1]
     * @var string[2]
     * @var string[3]
     * @return $this
     * @throws Throwable
     */
    public function execute(...$parameters): self
    {
        $this->systemMessage = true;

        $this->setThread($parameters[0])
            ->setMessageType($parameters[3])
            ->setMessageBody($parameters[2])
            ->setMessageOwner($parameters[1])
            ->handleTransactions()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }
}
