<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Http\Request\GroupThreadRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Support\Definitions;
use Throwable;

class StoreGroupThread extends NewThreadAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * StoreGroupThread constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher)
    {
        parent::__construct($messenger);

        $this->dispatcher = $dispatcher;
        $this->broadcaster = $broadcaster;
        $this->database = $database;
    }

    /**
     * Create a new group thread! If an array of provider alias/id is present,
     * we will also add the first batch of participants in this cycle.
     *
     * @param array ...$parameters
     * @var GroupThreadRequest[0]
     * @return $this
     * @throws Throwable
     */
    public function execute(...$parameters): self
    {
        $this->handleTransactions(
            $parameters[0]['subject'],
            $parameters[0]['providers'] ?? []
        )
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param string $subject
     * @param array $providers
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(string $subject, array $providers): self
    {
        if ($this->isChained()) {
            $this->executeTransactions($subject, $providers);
        } else {
            $this->database->transaction(fn () => $this->executeTransactions($subject, $providers));
        }

        return $this;
    }

    /**
     * Execute all actions that must occur for
     * a successful private thread creation.
     *
     * @param string $subject
     * @param array $providers
     */
    private function executeTransactions(string $subject, array $providers): void
    {
        $this->storeThread($this->groupThreadAttributes($subject))
            ->chain(StoreSystemMessage::class)
            ->withoutDispatches()
            ->execute(...$this->createdSystemMessage($subject))
            ->chain(StoreParticipant::class)
            ->execute(...$this->creatorParticipant())
            ->chain(StoreManyParticipants::class)
            ->execute(...$this->manyParticipants($providers));
    }

    /**
     * @param string $subject
     * @return array
     */
    private function groupThreadAttributes(string $subject): array
    {
        return [
            'type' => 2,
            'subject' => $subject,
            'image' => rand(1, 5).'.png',
            'add_participants' => true,
            'invitations' => true,
        ];
    }

    /**
     * @mixin StoreSystemMessage
     * @param string $subject
     * @return array
     */
    private function createdSystemMessage(string $subject): array
    {
        return [
            $this->getThread(),
            $this->messenger->getProvider(),
            'created '.$subject,
            'GROUP_CREATED',
        ];
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
            Definitions::DefaultAdminParticipant,
        ];
    }

    /**
     * @param array $providers
     * @mixin StoreManyParticipants
     * @return array
     */
    private function manyParticipants(array $providers): array
    {
        return [
            $this->getThread(),
            $providers,
            true,
        ];
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toOthersInThread($this->getThread())
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
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
            ));
        }
    }
}
