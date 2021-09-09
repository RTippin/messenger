<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\Messages\NewMessageAction;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
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
     * @var string
     */
    private string $messageActionType;

    /**
     * @var string
     */
    private string $messageActionKey;

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
     *
     * @param  array  $params
     * @return $this
     * @see PrivateThreadRequest
     * @throws AuthorizationException|Throwable
     */
    public function execute(array $params): self
    {
        $this->setRecipientAndExistingThread(
            $params['recipient_alias'],
            $params['recipient_id']
        );

        $this->bailIfCannotCreateThread();

        $this->setMessageActions($params)
            ->determineIfPending()
            ->handleTransactions($params)
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  array  $inputs
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(array $inputs): self
    {
        if ($this->isChained()) {
            $this->executeTransactions($inputs);
        } else {
            $this->database->transaction(fn () => $this->executeTransactions($inputs));
        }

        return $this;
    }

    /**
     * Execute all actions that must occur for
     * a successful private thread creation.
     *
     * @param  array  $inputs
     */
    private function executeTransactions(array $inputs): void
    {
        $this->storeThread()
            ->chain(StoreParticipant::class)
            ->execute(...$this->creatorParticipant())
            ->execute(...$this->recipientParticipant())
            ->chain($this->messageActionType)
            ->withoutDispatches()
            ->execute(...$this->storeMessage($inputs));
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
     * @param  array  $inputs
     * @return array
     */
    private function storeMessage(array $inputs): array
    {
        return [
            $this->getThread(),
            [
                $this->messageActionKey => $inputs[$this->messageActionKey],
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
        if ($this->messenger->providerHasFriends()
            && $this->messenger->isProviderFriendable($this->recipient)
            && $this->friends->friendStatus($this->recipient) === FriendDriver::FRIEND) {
            $this->pending = false;
        } else {
            $this->pending = true;
        }

        return $this;
    }

    /**
     * @param  string  $alias
     * @param  string  $id
     */
    private function setRecipientAndExistingThread(string $alias, string $id): void
    {
        $this->locator->setAlias($alias)->setId($id)->locate();

        $this->recipient = $this->locator->getRecipient();

        $this->existingThread = $this->locator->getThread();
    }

    /**
     * Determine which type of message was sent
     * to initiate this thread.
     *
     * @param  array  $inputs
     * @return $this
     */
    private function setMessageActions(array $inputs): self
    {
        if (array_key_exists('message', $inputs)) {
            $this->messageActionType = StoreMessage::class;
            $this->messageActionKey = 'message';
        } elseif (array_key_exists('image', $inputs)) {
            $this->messageActionType = StoreImageMessage::class;
            $this->messageActionKey = 'image';
        } elseif (array_key_exists('document', $inputs)) {
            $this->messageActionType = StoreDocumentMessage::class;
            $this->messageActionKey = 'document';
        } elseif (array_key_exists('audio', $inputs)) {
            $this->messageActionType = StoreAudioMessage::class;
            $this->messageActionKey = 'audio';
        }

        return $this;
    }

    /**
     * @throws NewThreadException|ProviderNotFoundException
     */
    private function bailIfCannotCreateThread(): void
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
