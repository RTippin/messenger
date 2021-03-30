<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\MessageEditedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Events\MessageEditedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Throwable;

class UpdateMessage extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * @var string
     */
    private string $originalBody;

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
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->emoji = $emoji;
    }

    /**
     * Update the given message.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var Message[1]
     * @var string[2]
     * @return $this
     * @throws FeatureDisabledException|Throwable
     */
    public function execute(...$parameters): self
    {
        $this->isEditMessagesEnabled();

        $this->setThread($parameters[0])
            ->setMessage($parameters[1])
            ->handleTransactions($parameters[2])
            ->generateResource();

        if ($this->getMessage()->wasChanged()) {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isEditMessagesEnabled(): void
    {
        if (! $this->messenger->isMessageEditsEnabled()) {
            throw new FeatureDisabledException('Edit messages are currently disabled.');
        }
    }

    /**
     * @param string $body
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(string $body): self
    {
        if ($this->isChained()) {
            $this->executeTransactions($body);
        } else {
            $this->database->transaction(fn () => $this->executeTransactions($body));
        }

        return $this;
    }

    /**
     * @param string $body
     * @return $this
     */
    private function executeTransactions(string $body): self
    {
        if ($this->getMessage()->body !== $newBody = $this->emoji->toShort($body)) {
            $this->originalBody = $this->getMessage()->body;

            $this->getMessage()->update([
                'body' => $newBody,
                'edited' => true,
            ]);

            $this->getMessage()->edits()->create([
                'body' => $this->originalBody,
                'edited_at' => $this->getMessage()->updated_at,
            ]);
        }

        return $this;
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
                ->toPresence($this->getThread())
                ->with($this->getJsonResource()->resolve())
                ->broadcast(MessageEditedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new MessageEditedEvent(
                $this->getMessage(true),
                $this->originalBody
            ));
        }

        return $this;
    }
}
