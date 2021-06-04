<?php

namespace RTippin\Messenger\Bots;

use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\BotHandlerInterface;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Action;
use RTippin\Messenger\Models\Message;
use Throwable;

class ReplyBot implements BotHandlerInterface
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * ReplyBot constructor.
     */
    public function __construct(Messenger $messenger, StoreMessage $storeMessage)
    {
        $this->messenger = $messenger;
        $this->storeMessage = $storeMessage;
    }

    /**
     * @param Action $action
     * @param Message $message
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function execute(Action $action, Message $message): void
    {
        $this->messenger->setProvider($action->bot);
        $this->storeMessage->execute($message->thread, [
            'message' => json_decode($action->payload, true)['reply'],
            'reply_to_id' => $message->id,
        ]);
    }
}
