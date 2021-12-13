<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use Throwable;

class AddReaction extends BaseMessengerAction
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
     * @var MessageReaction
     */
    private MessageReaction $reaction;

    /**
     * @var string|null
     */
    private ?string $react;

    /**
     * AddReaction constructor.
     *
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     * @param  Messenger  $messenger
     * @param  EmojiInterface  $emoji
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
     * Add a reaction to the given message.
     *
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  string  $reaction
     * @return $this
     *
     * @throws FeatureDisabledException|ReactionException|Throwable
     */
    public function execute(Thread $thread,
                            Message $message,
                            string $reaction): self
    {
        $this->setThread($thread)
            ->setMessage($message)
            ->prepareReaction($reaction);

        $this->bailIfChecksFail();

        $this->process()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    private function process(): self
    {
        $this->isChained()
            ? $this->handle()
            : $this->database->transaction(fn () => $this->handle());

        return $this;
    }

    /**
     * Set our reaction to the first valid emoji, or null if none found.
     *
     * @param  string  $reaction
     * @return void
     */
    private function prepareReaction(string $reaction): void
    {
        $this->react = $this->emoji->getFirstValidEmojiShortcode($reaction);
    }

    /**
     * @throws FeatureDisabledException|ReactionException
     */
    private function bailIfChecksFail(): void
    {
        if (! $this->messenger->isMessageReactionsEnabled()) {
            throw new FeatureDisabledException('Message reactions are currently disabled.');
        }

        if (is_null($this->react)) {
            throw new ReactionException('No valid reactions found.');
        }

        if ($this->hasAlreadyUsedReaction()) {
            throw new ReactionException('You have already used that reaction.');
        }

        if (! $this->doesntGoOverMaxUniqueReactions()) {
            throw new ReactionException('We appreciate the enthusiasm, but there are already too many reactions on this message.');
        }
    }

    /**
     * If the provider already used the emoji,
     * we do not want to allow duplicates.
     *
     * @return bool
     */
    private function hasAlreadyUsedReaction(): bool
    {
        return $this->getMessage()
                ->reactions()
                ->forProvider($this->messenger->getProvider())
                ->whereReaction($this->react)
                ->exists();
    }

    /**
     * Check if adding the emoji goes over max unique limit per message.
     * Adding an emoji already present from another provider is allowed.
     *
     * @return bool
     */
    private function doesntGoOverMaxUniqueReactions(): bool
    {
        return $this->getMessage()
                ->reactions()
                ->distinct()
                ->notReaction($this->react)
                ->count('reaction') < $this->messenger->getMessageReactionsMax();
    }

    /**
     * Generate the reaction resource.
     *
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new MessageReactionResource(
            $this->reaction,
        ));

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new MessageReactionResource(
            $this->reaction,
            $this->getMessage()
        ))->resolve();
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
                ->broadcast(ReactionAddedBroadcast::class);

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
            ->notMuted()
            ->exists()) {
            $this->broadcaster
                ->to($this->getMessage())
                ->with($this->generateBroadcastResource())
                ->broadcast(ReactionAddedBroadcast::class);
        }
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ReactionAddedEvent(
                $this->reaction->withoutRelations()
            ));
        }
    }

    /**
     * Store reaction. Mark the message as reacted.
     *
     * @return void
     */
    private function handle(): void
    {
        $this->storeReaction();

        if (! $this->getMessage()->reacted) {
            $this->getMessage()->update([
                'reacted' => true,
            ]);
        }
    }

    /**
     * @return void
     */
    private function storeReaction(): void
    {
        $this->reaction = $this->getMessage()->reactions()->create([
            'owner_id' => $this->messenger->getProvider()->getKey(),
            'owner_type' => $this->messenger->getProvider()->getMorphClass(),
            'reaction' => $this->react,
            'created_at' => now(),
        ])
            ->setRelations([
                'owner' => $this->messenger->getProvider(),
                'message' => $this->getMessage(),
            ]);
    }
}
