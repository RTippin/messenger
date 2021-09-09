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
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     * @param  string  $body
     * @param  string  $type
     * @return $this
     * @throws Throwable
     */
    public function execute(Thread $thread,
                            MessengerProvider $provider,
                            string $body,
                            string $type): self
    {
        if ($this->messenger->isSystemMessagesEnabled()) {
            $this->setThread($thread)
                ->setMessageType($type)
                ->setMessageBody($body)
                ->setMessageOwner($provider)
                ->handleTransactions()
                ->generateResource()
                ->fireBroadcast()
                ->fireEvents();
        }

        return $this;
    }
}
