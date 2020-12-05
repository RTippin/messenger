<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Http\Resources\Broadcast\NewCallBroadcastResource;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Messenger;

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
        if($this->shouldFireBroadcast())
        {
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
        if($this->shouldFireEvents())
        {
            $this->dispatcher->dispatch(new CallStartedEvent(
                $this->getCall(true),
                $this->getThread(true)
            ));
        }

        return $this;
    }

    /**
     * @return $this
     * @throws AuthorizationException
     */
    protected function hasNoActiveCall(): self
    {
        if($this->getThread()->hasActiveCall())
        {
            throw new AuthorizationException("{$this->getThread()->name()} already has an active call.");
        }

        return $this;
    }

    /**
     * @return $this
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    protected function hasNoCallLockout(): self
    {
        if($this->cacheDriver->get("call:{$this->getThread()->id}:starting"))
        {
            throw new AuthorizationException("{$this->getThread()->name()} has a call awaiting creation.");
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
                    'type' => array_search($type,Definitions::Call),
                    'owner_id' => $this->messenger->getProviderId(),
                    'owner_type' => $this->messenger->getProviderClass(),
                    'setup_complete' => $isSetupComplete
                ])
                ->setRelations([
                    'owner' => $this->messenger->getProvider(),
                    'thread' => $this->getThread()
                ])
        );

        $this->setData($this->getCall());

        return $this;
    }
}