<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Http\Resources\Broadcast\KnockBroadcastResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class SendKnock extends BaseMessengerAction
{
    /**
     * @var Repository
     */
    private Repository $cacheDriver;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * SendKnock constructor.
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
     * Send a nice KNOCK to the thread!
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents()
            ->storeCacheTimeout();

        return $this;
    }

    /**
     * Generate the knock resource.
     *
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new KnockBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread()
        ));

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toOthersInThread($this->getThread())
                ->with($this->getJsonResource()->resolve())
                ->broadcast(KnockBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new KnockEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true)
            ));
        }

        return $this;
    }

    /**
     * If the timeout for knocks is not 0, then put the lock in cache.
     *
     * @return $this
     */
    private function storeCacheTimeout(): self
    {
        if ($this->messenger->getKnockTimeout() !== 0) {
            $this->cacheDriver->put(
                $this->generateCacheKey(),
                true,
                now()->addMinutes($this->messenger->getKnockTimeout())
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    private function generateCacheKey(): string
    {
        return $this->getThread()->isGroup()
            ? "knock.knock.{$this->getThread()->id}"
            : "knock.knock.{$this->getThread()->id}.{$this->messenger->getProviderId()}";
    }
}
