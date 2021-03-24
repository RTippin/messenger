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
     * @var Message|null
     */
    protected ?Message $replyingTo = null;

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
     * @param string|null $replyToId
     * @return $this
     */
    protected function setReplyingToMessage(?string $replyToId = null): self
    {
        if (! is_null($replyToId)
            && ! is_null($message = $this->getThread()->messages()->with('owner')->find($replyToId))
            && ! $message->isSystemMessage()) {
            $this->replyingTo = $message;
        }

        return $this;
    }

    /**
     * @param MessengerProvider $owner
     * @param string $type
     * @param string $body
     * @param string|null $temporaryId
     * @return $this
     * @throws Throwable
     */
    protected function handleTransactions(MessengerProvider $owner,
                                          string $type,
                                          string $body,
                                          ?string $temporaryId = null): self
    {
        if ($this->isChained()) {
            $this->executeTransactions($owner, $type, $body, $temporaryId);
        } else {
            $this->database->transaction(fn () => $this->executeTransactions($owner, $type, $body, $temporaryId), 5);
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
     * @param MessengerProvider $owner
     * @param string $type
     * @param string $body
     * @param string|null $temporaryId
     */
    private function executeTransactions(MessengerProvider $owner,
                                         string $type,
                                         string $body,
                                         ?string $temporaryId): void
    {
        $this->storeMessage($owner, $type, $body, $temporaryId);

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
     * @param MessengerProvider $owner
     * @param string $type
     * @param string $body
     * @param string|null $temporaryId
     * @return $this
     */
    private function storeMessage(MessengerProvider $owner,
                                  string $type,
                                  string $body,
                                  ?string $temporaryId): self
    {
        $this->setMessage(
            $this->getThread()
                ->messages()
                ->create([
                    'type' => array_search($type, Definitions::Message),
                    'owner_id' => $owner->getKey(),
                    'owner_type' => get_class($owner),
                    'body' => $body,
                    'reply_to_id' => optional($this->replyingTo)->id,
                ])
                ->setRelations([
                    'owner' => $owner,
                    'thread' => $this->getThread(),
                    'replyTo' => $this->replyingTo,
                ])
                ->setTemporaryId($temporaryId)
        );

        return $this;
    }
}
