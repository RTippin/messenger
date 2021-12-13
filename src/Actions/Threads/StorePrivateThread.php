<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\Messages\NewMessageAction;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Messages\StoreVideoMessage;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Exceptions\NewThreadException;
use RTippin\Messenger\Exceptions\ProviderNotFoundException;
use RTippin\Messenger\Http\Request\PrivateThreadRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ThreadLocatorService;
use Throwable;

class StorePrivateThread extends NewThreadAction
{
    /**
     * @var ThreadLocatorService
     */
    private ThreadLocatorService $locator;

    /**
     * @var Thread|null
     */
    private ?Thread $existingThread;

    /**
     * @var MessengerProvider|null
     */
    private ?MessengerProvider $recipient;

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
     * @var FriendDriver
     */
    private FriendDriver $friends;

    /**
     * StorePrivateThread constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     * @param  FriendDriver  $friends
     * @param  ThreadLocatorService  $locator
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                FriendDriver $friends,
                                ThreadLocatorService $locator)
    {
        parent::__construct($messenger);

        $this->locator = $locator;
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->friends = $friends;
    }

    /**
     * Create a new private thread. Check one does not already exist between the
     * two providers, and that they are allowed to initiate a conversation.
     * Skip checking for an existing thread if a recipient is provided.
     *
     * @param  array  $params
     * @param  MessengerProvider|null  $recipient
     * @return $this
     *
     * @see PrivateThreadRequest
     *
     * @throws NewThreadException|ProviderNotFoundException|Throwable
     */
    public function execute(array $params, ?MessengerProvider $recipient = null): self
    {
        if (! is_null($recipient)) {
            $this->recipient = $recipient;
            $this->existingThread = null;
        } else {
            $this->setRecipientAndExistingThread(
                $params['recipient_alias'],
                $params['recipient_id']
            );
        }

        $this->bailIfChecksFail();

        $this->determineIfPending()
            ->process($params)
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  array  $params
     * @return $this
     *
     * @throws Throwable
     */
    private function process(array $params): self
    {
        $this->isChained()
            ? $this->handle($params)
            : $this->database->transaction(fn () => $this->handle($params));

        return $this;
    }

    /**
     * Execute all actions that must occur for
     * a successful private thread creation.
     *
     * @param  array  $params
     * @return void
     */
    private function handle(array $params): void
    {
        $this->storeThread()
            ->chain(StoreParticipant::class)
            ->execute(...$this->creatorParticipant())
            ->execute(...$this->recipientParticipant());

        if (! is_null($action = $this->getMessageAction($params))) {
            $this->chain($action[0])
                ->withoutDispatches()
                ->execute(...$this->storeMessage($params, $action[1]));
        }
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->recipient)
                ->with($this->generateBroadcastResource())
                ->broadcast(NewThreadBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewThreadEvent(
                $this->messenger->getProvider(true),
                $this->getThread(true),
            ));
        }
    }

    /**
     * @mixin StoreParticipant
     *
     * @return array
     */
    private function creatorParticipant(): array
    {
        return [
            $this->getThread(),
            $this->messenger->getProvider(),
        ];
    }

    /**
     * @mixin StoreParticipant
     *
     * @return array
     */
    private function recipientParticipant(): array
    {
        return [
            $this->getThread(),
            $this->recipient,
            ['pending' => $this->pending],
        ];
    }

    /**
     * @mixin NewMessageAction
     *
     * @param  array  $inputs
     * @param  string  $key
     * @return array
     */
    private function storeMessage(array $inputs, string $key): array
    {
        return [
            $this->getThread(),
            [
                $key => $inputs[$key],
                'extra' => $inputs['extra'] ?? null,
            ],
        ];
    }

    /**
     * Determine if the recipient participant should be marked pending.
     *
     * @return $this
     */
    private function determineIfPending(): self
    {
        if (! $this->messenger->shouldVerifyPrivateThreadFriendship()
            || ($this->messenger->providerHasFriends()
                && $this->messenger->isProviderFriendable($this->recipient)
                && $this->friends->friendStatus($this->recipient) === FriendDriver::FRIEND)) {
            $this->pending = false;
        } else {
            $this->pending = true;
        }

        return $this;
    }

    /**
     * @param  string  $alias
     * @param  string  $id
     * @return void
     */
    private function setRecipientAndExistingThread(string $alias, string $id): void
    {
        $this->locator->setAlias($alias)->setId($id)->locate();

        $this->recipient = $this->locator->getRecipient();

        $this->existingThread = $this->locator->getThread();
    }

    /**
     * Determine which type of message was sent
     * to initiate this thread, if any.
     *
     * @param  array  $params
     * @return array|null
     */
    private function getMessageAction(array $params): ?array
    {
        if (array_key_exists('message', $params)) {
            return [StoreMessage::class, 'message'];
        }

        if (array_key_exists('image', $params)) {
            return [StoreImageMessage::class, 'image'];
        }

        if (array_key_exists('document', $params)) {
            return [StoreDocumentMessage::class, 'document'];
        }

        if (array_key_exists('audio', $params)) {
            return [StoreAudioMessage::class, 'audio'];
        }

        if (array_key_exists('video', $params)) {
            return [StoreVideoMessage::class, 'video'];
        }

        return null;
    }

    /**
     * @throws NewThreadException|ProviderNotFoundException
     */
    private function bailIfChecksFail(): void
    {
        if (is_null($this->recipient)) {
            $this->locator->throwNotFoundError();
        }

        if (! is_null($this->existingThread)) {
            throw new NewThreadException("You already have an existing conversation with {$this->recipient->getProviderName()}.");
        }

        if (! $this->messenger->canMessageProviderFirst($this->recipient)) {
            throw new NewThreadException("Not authorized to start conversations with {$this->recipient->getProviderName()}.");
        }
    }
}
