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
     * Add a reaction to the given message.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var Message[1]
     * @var string[2]
     * @return $this
     * @throws FeatureDisabledException|ReactionException|Throwable
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setMessage($parameters[1])
            ->prepareReaction($parameters[2])
            ->canReact()
            ->handleTransactions()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(): self
    {
        if ($this->isChained() || $this->getMessage()->reacted) {
            $this->storeReaction();
        } else {
            $this->database->transaction(fn () => $this->storeReaction(), 3);
        }

        return $this;
    }

    /**
     * Set our reaction to the first valid emoji, or null if none found.
     *
     * @param string $reaction
     * @return $this
     */
    private function prepareReaction(string $reaction): self
    {
        $this->react = $this->emoji->getValidEmojiShortcodes($reaction)[0] ?? null;

        return $this;
    }

    /**
     * @return $this
     * @throws FeatureDisabledException|ReactionException
     */
    private function canReact(): self
    {
        if (! $this->messenger->isMessageReactionsEnabled()) {
            throw new FeatureDisabledException('Message reactions are currently disabled.');
        } elseif (is_null($this->react)) {
            throw new ReactionException('No valid reactions found.');
        } elseif ($this->hasExistingReaction()) {
            throw new ReactionException('You have already used that reaction.');
        } elseif (! $this->canAddNewReaction()) {
            throw new ReactionException('We appreciate the enthusiasm, but there are already too many reactions on this message.');
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function hasExistingReaction(): bool
    {
        return $this->getMessage()
                ->reactions()
                ->forProvider($this->messenger->getProvider())
                ->reaction($this->react)
                ->exists();
    }

    /**
     * See if someone else used the pending emoji. If yes, we are
     * good to go. If no, we check if adding the emoji goes over
     * max unique limit per message.
     *
     * @return bool
     */
    private function canAddNewReaction(): bool
    {
        return $this->getMessage()
                ->reactions()
                ->reaction($this->react)
                ->exists()
            || $this->getMessage()
                ->reactions()
                ->distinct()
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

            if ($this->messenger->getProvider()->isNot($this->getMessage()->owner)) {
                $this->broadcaster
                    ->to($this->getMessage()->owner)
                    ->with($this->generateBroadcastResource())
                    ->broadcast(ReactionAddedBroadcast::class);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ReactionAddedEvent(
                $this->reaction->withoutRelations()
            ));
        }

        return $this;
    }

    /**
     * Store reaction. Mark message as reacted to.
     */
    private function storeReaction(): void
    {
        $this->reaction = $this->getMessage()
            ->reactions()
            ->create([
                'owner_id' => $this->messenger->getProviderId(),
                'owner_type' => $this->messenger->getProviderClass(),
                'reaction' => $this->react,
                'created_at' => now(),
            ])
            ->setRelations([
                'owner' => $this->messenger->getProvider(),
                'message' => $this->getMessage(),
            ]);

        if (! $this->getMessage()->reacted) {
            $this->getMessage()->update([
                'reacted' => true,
            ]);
        }
    }
}
