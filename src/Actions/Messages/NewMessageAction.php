<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Http\Request\BaseMessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Support\Definitions;
use Throwable;

abstract class NewMessageAction extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    protected BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @var DatabaseManager
     */
    protected DatabaseManager $database;

    /**
     * @var int
     */
    private int $messageType;

    /**
     * @var string
     */
    private string $messageBody;

    /**
     * @var string|null
     */
    private ?string $messageTemporaryId = null;

    /**
     * @var array|null
     */
    private ?array $messageExtraData = null;

    /**
     * @var Message|null
     */
    private ?Message $replyingTo = null;

    /**
     * @var MessengerProvider
     */
    private MessengerProvider $messageOwner;

    /**
     * NewMessageAction constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->database = $database;
    }

    /**
     * @param string $type
     * @return $this
     */
    protected function setMessageType(string $type): self
    {
        $this->messageType = array_search($type, Definitions::Message);

        return $this;
    }

    /**
     * @param string $body
     * @return $this
     */
    protected function setMessageBody(string $body): self
    {
        $this->messageBody = $body;

        return $this;
    }

    /**
     * @param array $parameters
     * @return $this
     * @see BaseMessageRequest
     */
    protected function setMessageOptionalParameters(array $parameters): self
    {
        $this->setMessageTemporaryId($parameters['temporary_id'] ?? null);

        $this->setReplyingToMessage($parameters['reply_to_id'] ?? null);

        $this->setMessageExtraData($parameters['extra'] ?? null);

        return $this;
    }

    /**
     * @param MessengerProvider $owner
     * @return $this
     */
    protected function setMessageOwner(MessengerProvider $owner): self
    {
        $this->messageOwner = $owner;

        return $this;
    }

    /**
     * @return $this
     * @throws Throwable
     */
    protected function handleTransactions(): self
    {
        if ($this->isChained()) {
            $this->executeTransactions();
        } else {
            $this->database->transaction(fn () => $this->executeTransactions(), 5);
        }

        return $this;
    }

    /**
     * Generate the message resource.
     *
     * @return $this
     */
    protected function generateResource(): self
    {
        $this->setJsonResource(new MessageResource(
                $this->getMessage(),
                $this->getThread(),
                true
            )
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toAllInThread($this->getThread())
                ->with($this->getJsonResource()->resolve())
                ->broadcast(NewMessageBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewMessageEvent(
                $this->getMessage(true)
            ));
        }

        return $this;
    }

    /**
     * @param string|null $temporaryId
     */
    private function setMessageTemporaryId(?string $temporaryId = null): void
    {
        $this->messageTemporaryId = ! is_null($temporaryId) ? $temporaryId : null;
    }

    /**
     * @param array|null $extra
     */
    private function setMessageExtraData(?array $extra = null): void
    {
        $this->messageExtraData = ! is_null($extra) ? $extra : null;
    }

    /**
     * @param string|null $replyToId
     */
    private function setReplyingToMessage(?string $replyToId = null): void
    {
        if (! is_null($replyToId)) {
            $this->replyingTo = $this->getThread()
                ->messages()
                ->nonSystem()
                ->with('owner')
                ->find($replyToId);
        } else {
            $this->replyingTo = null;
        }
    }

    /**
     * Store message. If not chained, touch thread and mark sender as read.
     */
    private function executeTransactions(): void
    {
        $this->storeMessage();

        if ($this->shouldExecuteChains()) {
            $this->getThread()->touch();

            if (in_array($this->messageType, Message::NonSystemTypes)) {
                $this->chain(MarkParticipantRead::class)
                    ->withoutDispatches()
                    ->execute($this->getThread()->currentParticipant());
            }
        }
    }

    /**
     * Store message, attach owner relation from
     * provider in memory, add temp ID.
     *
     * @return void
     */
    private function storeMessage(): void
    {
        $this->setMessage(
            $this->getThread()->messages()->create([
                'type' => $this->messageType,
                'owner_id' => $this->messageOwner->getKey(),
                'owner_type' => $this->messageOwner->getMorphClass(),
                'body' => $this->messageBody,
                'reply_to_id' => optional($this->replyingTo)->id,
                'extra' => $this->messageExtraData,
            ])
            ->setRelations([
                'owner' => $this->messageOwner,
                'thread' => $this->getThread(),
                'replyTo' => $this->replyingTo,
            ])
            ->setTemporaryId($this->messageTemporaryId)
        );
    }
}
