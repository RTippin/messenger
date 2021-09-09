<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Http\Resources\Broadcast\KnockBroadcastResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class SendKnock extends BaseMessengerAction
{
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
     * Send a nice KNOCK to the thread!
     *
     * @param  Thread  $thread
     * @return $this
     * @throws FeatureDisabledException|KnockException
     */
    public function execute(Thread $thread): self
    {
        $this->setThread($thread)
            ->bailIfCannotKnockAtThread()
            ->generateResource()
            ->storeCacheTimeout()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws FeatureDisabledException|KnockException
     */
    private function bailIfCannotKnockAtThread(): self
    {
        if (! $this->messenger->isKnockKnockEnabled()) {
            throw new FeatureDisabledException('Knocking is currently disabled.');
        }

        if ($this->messenger->getKnockTimeout() !== 0
            && ($this->hasGroupThreadLockout()
                || $this->hasPrivateThreadLockout())) {
            throw new KnockException("You may only knock at {$this->getThread()->name()} once every {$this->messenger->getKnockTimeout()} minutes.");
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function hasPrivateThreadLockout(): bool
    {
        return $this->getThread()->isPrivate()
            && Cache::has("knock.knock.{$this->getThread()->id}.{$this->messenger->getProvider()->getKey()}");
    }

    /**
     * @return bool
     */
    private function hasGroupThreadLockout(): bool
    {
        return $this->getThread()->isGroup()
            && Cache::has("knock.knock.{$this->getThread()->id}");
    }

    /**
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
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new KnockEvent(
                $this->messenger->getProvider(true),
                $this->getThread(true)
            ));
        }
    }

    /**
     * @return $this
     */
    private function storeCacheTimeout(): self
    {
        if ($this->messenger->getKnockTimeout() !== 0) {
            Cache::put(
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
            : "knock.knock.{$this->getThread()->id}.{$this->messenger->getProvider()->getKey()}";
    }
}
