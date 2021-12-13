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
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Message;
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
     * @var string|null
     */
    private ?string $messageBody;

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
     * @var string|null
     */
    private ?string $senderIp = null;

    /**
     * @var MessengerProvider
     */
    private MessengerProvider $messageOwner;

    /**
     * NewMessageAction constructor.
     *
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
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
     * @param  int  $type
     * @return $this
     */
    protected function setMessageType(int $type): self
    {
        $this->messageType = $type;

        return $this;
    }

    /**
     * @param  string|null  $body
     * @return $this
     */
    protected function setMessageBody(?string $body): self
    {
        $this->messageBody = $body;

        return $this;
    }

    /**
     * @param  array  $parameters
     * @return $this
     *
     * @see BaseMessageRequest
     */
    protected function setMessageOptionalParameters(array $parameters): self
    {
        $this->messageTemporaryId = $parameters['temporary_id'] ?? null;

        $this->messageExtraData = $parameters['extra'] ?? null;

        $this->setReplyingToMessage($parameters['reply_to_id'] ?? null);

        return $this;
    }

    /**
     * @param  MessengerProvider  $owner
     * @return $this
     */
    protected function setMessageOwner(MessengerProvider $owner): self
    {
        $this->messageOwner = $owner;

        return $this;
    }

    /**
     * @param  string|null  $senderIp
     * @return $this
     */
    protected function setSenderIp(?string $senderIp): self
    {
        $this->senderIp = $senderIp;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    protected function process(): self
    {
        $this->isChained()
            ? $this->handle()
            : $this->database->transaction(fn () => $this->handle(), 5);

        return $this;
    }

    /**
     * Complete the cycle.
     *
     * @return void
     */
    protected function finalize(): void
    {
        $this->generateResource()
            ->fireBroadcast()
            ->fireEvents();
    }

    /**
     * Generate the message resource.
     *
     * @return $this
     */
    private function generateResource(): self
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
    private function fireBroadcast(): self
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
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewMessageEvent(
                $this->getMessage(true),
                $this->getThread(true),
                $this->isGroupAdmin(),
                $this->senderIp
            ));
        }
    }

    /**
     * Add the admin flag when not from bot and not a system message.
     *
     * @return bool
     */
    private function isGroupAdmin(): bool
    {
        return $this->getThread()->isGroup()
            && $this->getMessage()->notFromBot()
            && $this->getMessage()->notSystemMessage()
            && $this->getThread()->isAdmin();
    }

    /**
     * @param  string|null  $replyToId
     * @return void
     */
    private function setReplyingToMessage(?string $replyToId): void
    {
        if (! is_null($replyToId)) {
            $this->replyingTo = $this->getThread()
                ->messages()
                ->nonSystem()
                ->with('owner')
                ->find($replyToId);

            return;
        }

        $this->replyingTo = null;
    }

    /**
     * Store message. If not chained, touch thread and mark sender as read.
     *
     * @return void
     */
    private function handle(): void
    {
        $this->storeMessage();

        if ($this->shouldExecuteChains()) {
            $this->getThread()->touch();

            if ($this->shouldMarkRead()) {
                $this->chain(MarkParticipantRead::class)
                    ->withoutDispatches()
                    ->execute($this->getThread()->currentParticipant());
            }
        }
    }

    /**
     * Only mark read when not a system message and not a message sent from a bot.
     *
     * @return bool
     */
    private function shouldMarkRead(): bool
    {
        return in_array($this->messageType, Message::NonSystemTypes)
            && ! $this->messageOwner instanceof Bot;
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
