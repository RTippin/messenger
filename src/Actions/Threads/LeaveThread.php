<?php

namespace RTippin\Messenger\Actions\Threads;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class LeaveThread extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * LeaveThread constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * Leave the group thread.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @return $this
     * @throws Exception
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->removeParticipant()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function removeParticipant(): self
    {
        $this->getThread()
            ->currentParticipant()
            ->delete();

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->getThread()->currentParticipant())
                ->with($this->generateBroadcastResource())
                ->broadcast(ThreadLeftBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ThreadLeftEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
                $this->getThread()->currentParticipant()->withoutRelations()
            ));
        }
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'thread_id' => $this->getThread()->id,
        ];
    }
}
