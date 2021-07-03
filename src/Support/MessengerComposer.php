<?php

namespace RTippin\Messenger\Support;

use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\MessengerComposerException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\PrivateThreadRepository;
use Throwable;

class MessengerComposer
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var PrivateThreadRepository
     */
    private PrivateThreadRepository $locator;

    /**
     * @var null|Thread|MessengerProvider
     */
    private $to = null;

    /**
     * @var bool
     */
    private bool $silent = false;

    /**
     * MessengerComposer constructor.
     */
    public function __construct(Messenger $messenger, PrivateThreadRepository $locator)
    {
        $this->messenger = $messenger;
        $this->locator = $locator;
    }

    /**
     * Set the thread or provider we want to compose to. If a provider
     * is supplied, we will attempt to locate a private thread between
     * the "TO"" and "FROM" providers. If no private thread is found,
     * one will be created.
     *
     * @param MessengerProvider|Thread $entity
     * @return $this
     * @throws MessengerComposerException
     */
    public function to($entity): self
    {
        if (! $entity instanceof Thread
            && ! $this->messenger->isValidMessengerProvider($entity)) {
            throw new MessengerComposerException('Invalid "TO" entity. Thread or messenger provider must be used.');
        }

        $this->to = $entity;

        return $this;
    }

    /**
     * Set the provider who is composing.
     *
     * @param MessengerProvider $provider
     * @return $this
     * @throws InvalidProviderException
     */
    public function from(MessengerProvider $provider): self
    {
        $this->messenger->setProvider($provider);

        return $this;
    }

    /**
     * When sending our composed payload, silence any broadcast events.
     *
     * @return $this
     */
    public function silent(): self
    {
        $this->silent = true;

        return $this;
    }

    /**
     * Send a message. Optional reply to message ID and extra data allowed.
     *
     * @param string $message
     * @param string|null $replyingToId
     * @param array|null $extra
     * @return StoreMessage
     * @throws MessengerComposerException
     * @throws Throwable
     */
    public function message(string $message,
                            ?string $replyingToId = null,
                            ?array $extra = null): StoreMessage
    {
        $action = app(StoreMessage::class);

        $payload = [$this->resolveToThread(), [
            'message' => $message,
            'reply_to_id' => $replyingToId,
            'extra' => $extra,
        ]];

        if ($this->silent) {
            return $action->withoutBroadcast()->execute(...$payload);
        }

        return $action->execute(...$payload);
    }

    /**
     * @return $this
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * If TO is not a thread, resolve or create a private
     * thread between the TO and FROM providers.
     *
     * @return Thread
     * @throws MessengerComposerException
     */
    private function resolveToThread(): Thread
    {
        $this->checkIsReadyToCompose();

        if ($this->to instanceof Thread) {
            return $this->to;
        }

        $thread = $this->locator->getProviderPrivateThreadWithRecipient($this->to);

        if (is_null($thread)) {
            return $this->makePrivateThread();
        }

        return $thread;
    }

    /**
     * Check that we have TO and FROM set.
     *
     * @throws MessengerComposerException
     */
    private function checkIsReadyToCompose(): void
    {
        if (is_null($this->to)) {
            throw new MessengerComposerException('No "TO" entity has been set.');
        }

        if (! $this->messenger->isProviderSet()) {
            throw new MessengerComposerException('No "FROM" provider has been set.');
        }
    }

    /**
     * Make the private thread between the TO and FROM providers.
     *
     * @return Thread
     */
    private function makePrivateThread(): Thread
    {
        $thread = Thread::create(Definitions::DefaultThread);
        Participant::factory()->for($thread)->owner($this->messenger->getProvider())->create();
        Participant::factory()->for($thread)->owner($this->to)->create();

        return $thread;
    }
}
