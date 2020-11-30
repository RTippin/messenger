<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Contracts\BroadcastDriver;
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
     * @param Repository $cacheDriver
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                DatabaseManager $database,
                                Repository $cacheDriver)
    {
        parent::__construct(
            $messenger,
            $broadcaster,
            $dispatcher,
            $cacheDriver
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
     * @var bool|null $setupComplete $parameters[1]
     * @var Thread $thread $parameters[0]
     * @return $this
     * @throws AuthorizationException|Throwable|InvalidArgumentException
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->hasNoActiveCall()
            ->hasNoCallLockout()
            ->setCallLockout()
            ->handleTransactions(
                'VIDEO',
                $parameters[1] ?? false
            )
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param string $type
     * @param bool $setupComplete
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(string $type, bool $setupComplete): self
    {
        if($this->isChained())
        {
            $this->executeTransactions($type, $setupComplete);
        }
        else
        {
            $this->database->transaction(
                fn() => $this->executeTransactions($type, $setupComplete),
                3);

        }

        return $this;
    }

    /**
     * @param string $type
     * @param bool $setupComplete
     */
    private function executeTransactions(string $type, bool $setupComplete): void
    {
        $this->storeCall($type, $setupComplete);

        $participant = $this->chain(JoinCall::class)
            ->withoutDispatches()
            ->execute($this->getCall(), true);

        $this->getCall()->setRelation(
            'participants',
            collect($participant->getData())
        );
    }
}