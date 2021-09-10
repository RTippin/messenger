<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Broadcasting\ParticipantPermissionsBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ParticipantPermissionsEvent;
use RTippin\Messenger\Http\Request\ParticipantPermissionsRequest;
use RTippin\Messenger\Http\Resources\ParticipantResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class UpdateParticipantPermissions extends ThreadParticipantAction
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
     * UpdateParticipantPermissions constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
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
     * Update the participants permissions.
     *
     * @param  Thread  $thread
     * @param  Participant  $participant
     * @param  array  $params
     * @return $this
     *
     * @see ParticipantPermissionsRequest
     */
    public function execute(Thread $thread,
                            Participant $participant,
                            array $params): self
    {
        $this->setThread($thread)
            ->updateParticipant(
                $participant,
                $params
            )
            ->generateResource();

        if ($this->getParticipant()->wasChanged()) {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @return void
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new ParticipantResource(
            $this->getParticipant(),
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
                ->to($this->getParticipant())
                ->with($this->generateBroadcastResource())
                ->broadcast(ParticipantPermissionsBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantPermissionsEvent(
                $this->messenger->getProvider(true),
                $this->getThread(true),
                $this->getParticipant(true)
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
