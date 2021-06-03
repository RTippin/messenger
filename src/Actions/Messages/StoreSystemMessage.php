<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use Throwable;

class StoreSystemMessage extends NewMessageAction
{
    private Messenger $messenger;

    /**
     * StoreSystemMessage constructor.
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Messenger $messenger)
    {
        parent::__construct(
            $broadcaster,
            $database,
            $dispatcher
        );

        $this->messenger = $messenger;
    }

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
        if ($this->messenger->isSystemMessagesEnabled()) {
            $this->setThread($parameters[0])
                ->setMessageType($parameters[3])
                ->setMessageBody($parameters[2])
                ->setMessageOwner($parameters[1])
                ->handleTransactions()
                ->generateResource()
                ->fireBroadcast()
                ->fireEvents();
        }

        return $this;
    }
}
