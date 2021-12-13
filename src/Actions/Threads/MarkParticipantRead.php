<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ParticipantReadEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;

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
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(BroadcastDriver $broadcaster, Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Update participants last_read.
     *
     * @param  Participant|null  $participant
     * @param  Thread|null  $thread
     * @return $this
     */
    public function execute(?Participant $participant = null, ?Thread $thread = null): self
    {
        $this->setParticipant($participant);

        if ($this->shouldUpdateTimestamp($thread)) {
            $this->markParticipantRead();
        }

        return $this;
    }

    /**
     * @param  Thread|null  $thread
     * @return bool
     */
    private function shouldUpdateTimestamp(?Thread $thread): bool
    {
        return ! is_null($this->getParticipant())
            && ! $this->getParticipant()->pending
            && (is_null($thread)
                || is_null($this->getParticipant()->last_read)
                || Helpers::precisionTime($thread->updated_at) > Helpers::precisionTime($this->getParticipant()->last_read));
    }

    /**
     * Update participant last_read. If changed, dispatch
     * events and clear the cached last read message.
     *
     * @return void
     */
    private function markParticipantRead(): void
    {
        $this->getParticipant()->update([
            'last_read' => now(),
        ]);

        if ($this->getParticipant()->wasChanged()) {
            Cache::forget($this->getParticipant()->getLastSeenMessageCacheKey());

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
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantReadEvent(
                $this->getParticipant(true)
            ));
        }
    }
}
