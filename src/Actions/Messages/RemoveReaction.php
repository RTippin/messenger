<?php

namespace RTippin\Messenger\Actions\Messages;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ReactionRemovedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ReactionRemovedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use Throwable;

class RemoveReaction extends BaseMessengerAction
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
     * @var MessageReaction
     */
    private MessageReaction $reaction;

    /**
     * @var int
     */
    private int $reactionsCount;

    /**
     * RemoveReaction constructor.
     *
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     * @param  Messenger  $messenger
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Messenger $messenger)
    {
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * Remove a reaction from the given message.
     *
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  MessageReaction  $reaction
     * @return $this
     *
     * @throws Throwable|FeatureDisabledException
     */
    public function execute(Thread $thread,
                            Message $message,
                            MessageReaction $reaction): self
    {
        $this->bailIfDisabled();

        $this->reaction = $reaction;
        $this->reactionsCount = $message->reactions()->count();

        $this->setThread($thread)
            ->setMessage($message)
            ->process()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return void
     *
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isMessageReactionsEnabled()) {
            throw new FeatureDisabledException('Message reactions are currently disabled.');
        }
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    private function process(): self
    {
        if ($this->isChained() || $this->reactionsCount > 1) {
            $this->handle();
        } else {
            $this->database->transaction(fn () => $this->handle(), 3);
        }

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'id' => $this->reaction->id,
            'message_id' => $this->getMessage()->id,
            'reaction' => $this->reaction->reaction,
        ];
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toPresence($this->getThread())
                ->with($this->generateBroadcastResource())
                ->broadcast(ReactionRemovedBroadcast::class);

            $this->checkBroadcastToMessageOwner();
        }

        return $this;
    }

    /**
     * Only broadcast to message owner if the current provider is not
     * the message owner and the owner is still in the thread.
     *
     * @return void
     */
    private function checkBroadcastToMessageOwner(): void
    {
        if ($this->getMessage()->isOwnedByCurrentProvider()) {
            // We are the owner, break;
            return;
        }

        // Only broadcast if participant still in thread.
        if ($this->getThread()
            ->participants()
            ->forProviderWithModel($this->getMessage())
            ->exists()) {
            $this->broadcaster
                ->to($this->getMessage())
                ->with($this->generateBroadcastResource())
                ->broadcast(ReactionRemovedBroadcast::class);
        }
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ReactionRemovedEvent(
                $this->messenger->getProvider(true),
                $this->reaction->toArray()
            ));
        }
    }

    /**
     * Remove reaction. Mark the message as not reacted to when none left.
     *
     * @return void
     *
     * @throws Exception
     */
    private function handle(): void
    {
        $this->reaction->delete();

        if ($this->reactionsCount === 1) {
            $this->getMessage()->update([
                'reacted' => false,
            ]);
        }
    }
}
