<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Http\Resources\Broadcast\CallBroadcastResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
use Throwable;

class EndCall extends BaseMessengerAction
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
     * EndCall constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * End the call immediately if it is still active. Teardown with
     * our video provider will be picked up by the event listener.
     *
     * @param  Call  $call
     * @return $this
     *
     * @throws Throwable
     */
    public function execute(Call $call): self
    {
        $this->setCall($call);

        if (! $this->hasNoEndingLockout()) {
            $this->setEndingLockout();

            if ($this->getCall()->isActive()) {
                $this->process()
                    ->fireBroadcast()
                    ->fireEvents();
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function hasNoEndingLockout(): bool
    {
        return Cache::has("call:{$this->getCall()->id}:ending");
    }

    /**
     * @return void
     */
    private function setEndingLockout(): void
    {
        Cache::put("call:{$this->getCall()->id}:ending", true, 10);
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    private function process(): self
    {
        if ($this->isChained()) {
            $this->handle();
        } else {
            $this->database->transaction(fn () => $this->handle());
        }

        return $this;
    }

    /**
     * Mark the call as ended and all active participants as left.
     *
     * @return void
     */
    private function handle(): void
    {
        $this->getCall()->update([
            'call_ended' => now(),
        ]);

        $this->getCall()->participants()->inCall()->update([
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
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallEndedEvent(
                $this->messenger->getProvider(true),
                $this->getCall(true)
            ));
        }
    }
}
