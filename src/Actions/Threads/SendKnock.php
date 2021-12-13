<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
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
     * Send a KNOCK 👊✊ to the thread!
     *
     * @param  Thread  $thread
     * @return $this
     *
     * @throws FeatureDisabledException|KnockException
     */
    public function execute(Thread $thread): self
    {
        $this->setThread($thread);

        $this->bailIfChecksFail();

        $this->generateResource();

        $thread->setKnockCacheLockout(
            $this->messenger->getProvider()
        );

        $this->fireBroadcast()->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException|KnockException
     */
    private function bailIfChecksFail(): void
    {
        if (! $this->messenger->isKnockKnockEnabled()) {
            throw new FeatureDisabledException('Knocking is currently disabled.');
        }

        if ($this->getThread()->hasKnockTimeout(
            $this->messenger->getProvider()
        )) {
            throw new KnockException("You may only knock at {$this->getThread()->name()} once every {$this->messenger->getKnockTimeout()} minutes.");
        }
    }

    /**
     * @return void
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new KnockBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread()
        ));
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
}
