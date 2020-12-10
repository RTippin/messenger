<?php

namespace RTippin\Messenger\Actions\Threads;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Http\Resources\Broadcast\ThreadApprovalBroadcastResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class ThreadApproval extends ThreadParticipantAction
{
    /**
     * @var bool
     */
    private bool $approved;

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
     * ThreadApproval constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
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
     * @param mixed ...$parameters
     * @var Thread $parameters[0]
     * @var bool $parameters[1]
     * @return $this
     * @throws AuthorizationException|Exception
     */
    public function execute(...$parameters): self
    {
        $this->approved = $parameters[1];

        $this->setThread($parameters[0])
            ->checkThreadNeedsApproval()
            ->handleTransactions()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function handleTransactions(): self
    {
        if ($this->approved) {
            $this->updateParticipant(
                $this->getThread()->currentParticipant(),
                ['pending' => false]
            );
        } else {
            $this->getThread()->delete();
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->getThread()->recipient())
                ->with($this->generateBroadcastResource())
                ->broadcast(ThreadApprovalBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ThreadApprovalEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
                $this->approved
            ));
        }

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new ThreadApprovalBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread(),
            $this->approved
        ))->resolve();
    }

    /**
     * @return $this
     * @throws AuthorizationException
     */
    private function checkThreadNeedsApproval(): self
    {
        if (! $this->getThread()->isAwaitingMyApproval()) {
            throw new AuthorizationException('This conversation is not pending approval.');
        }

        return $this;
    }
}
