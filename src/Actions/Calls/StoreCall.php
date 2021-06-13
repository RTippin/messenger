<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\NewCallException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use Throwable;

class StoreCall extends NewCallAction
{
    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * StoreCall constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param DatabaseManager $database
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                DatabaseManager $database)
    {
        parent::__construct(
            $messenger,
            $broadcaster,
            $dispatcher
        );

        $this->database = $database;
    }

    /**
     * Check thread has no active calls or lockouts, then store call
     * and creator participant in database. By default, setup complete
     * is false. We use our new call event to hook into creating a
     * video room using our desired video call service.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var bool|null[1]
     * @return $this
     * @throws NewCallException|Throwable|FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->canInitiateCall()
            ->setCallLockout()
            ->handleTransactions($parameters[1] ?? false)
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param bool $setupComplete
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(bool $setupComplete): self
    {
        if ($this->isChained()) {
            $this->executeTransactions($setupComplete);
        } else {
            $this->database->transaction(fn () => $this->executeTransactions($setupComplete), 3);
        }

        return $this;
    }

    /**
     * @param bool $setupComplete
     */
    private function executeTransactions(bool $setupComplete): void
    {
        $this->storeCall('VIDEO', $setupComplete);

        $participant = $this->chain(JoinCall::class)
            ->withoutDispatches()
            ->execute($this->getCall(), true);

        $this->getCall()->setRelation(
            'participants',
            new Collection($participant->getCallParticipant())
        );
    }
}
