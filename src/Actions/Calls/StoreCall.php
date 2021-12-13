<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\NewCallException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
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
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     * @param  DatabaseManager  $database
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
     * @param  Thread  $thread
     * @param  bool  $setupComplete
     * @return $this
     *
     * @throws NewCallException|Throwable|FeatureDisabledException
     */
    public function execute(Thread $thread, bool $setupComplete = false): self
    {
        $this->setThread($thread);

        $this->bailIfChecksFail();

        $this->setCallLockout()
            ->process($setupComplete)
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  bool  $setupComplete
     * @return $this
     *
     * @throws Throwable
     */
    private function process(bool $setupComplete): self
    {
        $this->isChained()
            ? $this->handle($setupComplete)
            : $this->database->transaction(fn () => $this->handle($setupComplete), 3);

        return $this;
    }

    /**
     * @param  bool  $setupComplete
     */
    private function handle(bool $setupComplete): void
    {
        $this->storeCall(Call::VIDEO, $setupComplete);

        $participant = $this->chain(JoinCall::class)
            ->withoutDispatches()
            ->execute($this->getCall(), true);

        $this->getCall()->setRelation(
            'participants',
            Collection::make($participant->getCallParticipant())
        );
    }
}
