<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

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
     * @var string|null
     */
    private ?string $reaction;

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
     * @throws FeatureDisabledException|ReactionException
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setMessage($parameters[1])
            ->prepareReaction($parameters[2])
            ->canReact();

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
        $this->reaction = $this->emoji->getValidEmojiShortcodes($reaction)[0] ?? null;

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
        } elseif (is_null($this->reaction)) {
            throw new ReactionException('No valid reactions found.');
        } elseif ($this->hasExistingReaction()) {
            throw new ReactionException('You have already used that reaction.');
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
                ->reaction($this->reaction)
                ->count() > 0;
    }
}
