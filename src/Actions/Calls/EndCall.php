<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Http\Resources\Broadcast\CallBroadcastResource;
use RTippin\Messenger\Models\Call;
use Throwable;

class EndCall extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Repository
     */
    protected Repository $cacheDriver;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * EndCall constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     * @param Repository $cacheDriver
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Repository $cacheDriver)
    {
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * End the call immediately if it is still active. Teardown with
     * our video provider will be picked up by the event listener.
     *
     * @param mixed ...$parameters
     * @return $this
     * @var Call[0]
     * @throws Throwable|InvalidArgumentException
     */
    public function execute(...$parameters): self
    {
        $this->setCall($parameters[0]);

        if (! $this->hasNoEndingLockout()) {
            $this->setEndingLockout();

            if ($this->getCall()->fresh()->isActive()) {
                $this->handleTransactions()
                    ->fireBroadcast()
                    ->fireEvents();
            }
        }

        return $this;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    private function hasNoEndingLockout(): bool
    {
        return $this->cacheDriver->has("call:{$this->getCall()->id}:ending");
    }

    /**
     * @return void
     */
    private function setEndingLockout(): void
    {
        $this->cacheDriver->put("call:{$this->getCall()->id}:ending", true, 10);
    }

    /**
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(): self
    {
        if ($this->isChained()) {
            $this->endCall();
        } else {
            $this->database->transaction(fn () => $this->endCall());
        }

        return $this;
    }

    /**
     * Update the call with the information we received from our video provider.
     */
    private function endCall(): void
    {
        $this->setData(
            $this->getCall()
                ->update([
                    'call_ended' => now(),
                ])
        );

        $this->getCall()
            ->participants()
            ->inCall()
            ->update([
                'left_call' => now(),
            ]);
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new CallBroadcastResource(
            $this->getCall()
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toAllInThread($this->getCall()->thread)
                ->with($this->generateBroadcastResource())
                ->broadcast(CallEndedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallEndedEvent(
                $this->getCall(true)
            ));
        }

        return $this;
    }
}
