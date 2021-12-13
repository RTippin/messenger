<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\NewCallException;
use RTippin\Messenger\Http\Resources\Broadcast\NewCallBroadcastResource;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Messenger;

abstract class NewCallAction extends BaseMessengerAction
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var BroadcastDriver
     */
    protected BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * NewCallAction constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        $this->messenger = $messenger;
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return $this
     */
    protected function generateResource(): self
    {
        $this->setJsonResource(new CallResource(
            $this->getCall(),
            $this->getThread()
        ));

        return $this;
    }

    /**
     * @return array
     */
    protected function generateBroadcastResource(): array
    {
        return (new NewCallBroadcastResource(
            $this->messenger->getProvider(),
            $this->getCall()
        ))->resolve();
    }

    /**
     * @return $this
     */
    protected function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toOthersInThread($this->getThread())
                ->with($this->generateBroadcastResource())
                ->broadcast(CallStartedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallStartedEvent(
                $this->getCall(true),
                $this->getThread(true)
            ));
        }

        return $this;
    }

    /**
     * @throws FeatureDisabledException|NewCallException
     */
    protected function bailIfChecksFail(): void
    {
        if (! $this->messenger->isCallingEnabled()
            || $this->messenger->isCallingTemporarilyDisabled()) {
            throw new FeatureDisabledException('Calling is currently disabled.');
        }

        if ($this->getThread()->hasActiveCall()) {
            throw new NewCallException("{$this->getThread()->name()} already has an active call.");
        }

        if (Cache::get("call:{$this->getThread()->id}:starting")) {
            throw new NewCallException("{$this->getThread()->name()} has a call awaiting creation.");
        }
    }

    /**
     * @return $this
     */
    protected function setCallLockout(): self
    {
        Cache::put("call:{$this->getThread()->id}:starting", true, 10);

        return $this;
    }

    /**
     * @param  int  $type
     * @param  bool  $isSetupComplete
     * @return $this
     */
    protected function storeCall(int $type, bool $isSetupComplete): self
    {
        $this->setCall(
            $this->getThread()
                ->calls()
                ->create([
                    'type' => $type,
                    'owner_id' => $this->messenger->getProvider()->getKey(),
                    'owner_type' => $this->messenger->getProvider()->getMorphClass(),
                    'setup_complete' => $isSetupComplete,
                    'teardown_complete' => false,
                ])
                ->setRelations([
                    'owner' => $this->messenger->getProvider(),
                    'thread' => $this->getThread(),
                ])
        );

        return $this;
    }
}
