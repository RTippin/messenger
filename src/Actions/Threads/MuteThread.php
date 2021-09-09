<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Events\ParticipantMutedEvent;
use RTippin\Messenger\Models\Thread;

class MuteThread extends ThreadParticipantAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * MuteThread constructor.
     *
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Mute the thread for the current participant.
     *
     * @param  Thread  $thread
     * @return $this
     */
    public function execute(Thread $thread): self
    {
        $this->setThread($thread)
            ->updateParticipant(
                $this->getThread()->currentParticipant(),
                ['muted' => true]
            );

        if ($this->getParticipant()->wasChanged()) {
            $this->fireEvents();
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantMutedEvent(
                $this->getParticipant(true)
            ));
        }
    }
}
