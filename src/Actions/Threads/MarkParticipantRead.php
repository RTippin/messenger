<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ParticipantsReadEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class MarkParticipantRead extends BaseMessengerAction
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
     * MarkParticipantRead constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     */
    public function __construct(BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Update participants last_read.
     *
     * @param mixed ...$parameters
     * @var Participant[0]
     * @var Thread|null[1]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setParticipant($parameters[0] ?? null);

        if ($this->shouldUpdateTimestamp($parameters[1] ?? null)) {
            $this->markParticipantRead();
        }

        return $this;
    }

    /**
     * @param Thread|null $thread
     * @return bool
     */
    private function shouldUpdateTimestamp(Thread $thread = null): bool
    {
        return ! is_null($this->getParticipant())
            && ! $this->getParticipant()->pending
            && (! $thread
                || is_null($this->getParticipant()->last_read)
                || $thread->updated_at > $this->getParticipant()->last_read);
    }

    /**
     * Update participant last_read. If changed, dispatch events.
     */
    private function markParticipantRead(): void
    {
        $this->getParticipant()->update([
            'last_read' => now(),
        ]);

        if ($this->getParticipant()->wasChanged()) {
            $this->fireBroadcast()->fireEvents();
        }
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'thread_id' => $this->getParticipant()->thread_id,
            'last_read' => $this->getParticipant()->last_read,
        ];
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->getParticipant())
                ->with($this->generateBroadcastResource())
                ->broadcast(ParticipantReadBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantsReadEvent(
                $this->getParticipant(true)
            ));
        }

        return $this;
    }
}
