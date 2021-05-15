<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\NewCallException;
use RTippin\Messenger\Http\Resources\Broadcast\NewCallBroadcastResource;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Support\Definitions;

abstract class NewCallAction extends BaseMessengerAction
{
    /**
     * @var Repository
     */
    protected Repository $cacheDriver;

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
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param Repository $cacheDriver
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                Repository $cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
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
     * @return $this
     * @throws FeatureDisabledException|NewCallException|InvalidArgumentException
     */
    protected function canInitiateCall(): self
    {
        if (! $this->messenger->isCallingEnabled()
            || $this->messenger->isCallingTemporarilyDisabled()) {
            throw new FeatureDisabledException('Calling is currently disabled.');
        }

        if ($this->getThread()->hasActiveCall()) {
            throw new NewCallException("{$this->getThread()->name()} already has an active call.");
        }

        if ($this->cacheDriver->get("call:{$this->getThread()->id}:starting")) {
            throw new NewCallException("{$this->getThread()->name()} has a call awaiting creation.");
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function setCallLockout(): self
    {
        $this->cacheDriver->put("call:{$this->getThread()->id}:starting", true, 10);

        return $this;
    }

    /**
     * @param string $type
     * @param bool $isSetupComplete
     * @return $this
     */
    protected function storeCall(string $type, bool $isSetupComplete): self
    {
        $this->setCall(
            $this->getThread()
                ->calls()
                ->create([
                    'type' => array_search($type, Definitions::Call),
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
