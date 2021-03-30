<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Http\Request\MessageRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use Throwable;

class StoreMessage extends NewMessageAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * StoreMessage constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     * @param Messenger $messenger
     * @param EmojiInterface $emoji
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Messenger $messenger,
                                EmojiInterface $emoji)
    {
        parent::__construct(
            $broadcaster,
            $database,
            $dispatcher
        );

        $this->messenger = $messenger;
        $this->emoji = $emoji;
    }

    /**
     * Store new message, update thread updated_at,
     * mark read for participant, broadcast.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var MessageRequest[1]
     * @return $this
     * @throws Throwable
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setReplyingToMessage($parameters[1]['reply_to_id'] ?? null)
            ->handleTransactions(
                $this->messenger->getProvider(),
                'MESSAGE',
                $this->emoji->toShort($parameters[1]['message']),
                $parameters[1]['temporary_id'] ?? null
            )
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }
}
