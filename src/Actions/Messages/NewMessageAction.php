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
     * @var string
     */
    protected string $messageType;

    /**
     * @var string
     */
    protected string $messageBody;

    /**
     * @var string|null
     */
    protected ?string $messageTemporaryId = null;

    /**
     * @var array|null
     */
    protected ?array $messageExtraData = null;

    /**
     * @var Message|null
     */
    protected ?Message $replyingTo = null;

    /**
     * @var MessengerProvider
     */
    protected MessengerProvider $messageOwner;

    /**
     * @var bool
     */
    protected bool $systemMessage = false;

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
        $this->messageType = $type;

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
     * @param string|null $temporaryId
     * @return $this
     */
    protected function setMessageTemporaryId(?string $temporaryId = null): self
    {
        $this->messageTemporaryId = ! is_null($temporaryId) ? $temporaryId : null;

        return $this;
    }

    /**
     * @param array|null $extra
     * @return $this
     */
    protected function setMessageExtraData(?array $extra = null): self
    {
        $this->messageExtraData = ! is_null($extra) ? $extra : null;

        return $this;
    }

    /**
     * @param string|null $replyToId
     * @return $this
     */
    protected function setReplyingToMessage(?string $replyToId = null): self
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
     * Store message. If not chained, touch thread and mark sender as read.
     */
    private function executeTransactions(): void
    {
        $this->storeMessage();

        if ($this->shouldExecuteChains()) {
            $this->getThread()->touch();

            if (! $this->systemMessage) {
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
                'type' => array_search($this->messageType, Definitions::Message),
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
