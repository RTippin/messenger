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
     * DemoteAdmin constructor.
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
     * Update the participants permissions
     *
     * @param mixed ...$parameters
     * @var Thread $thread $parameters[0]
     * @var Participant $participant $parameters[1]
     * @var ParticipantPermissionsRequest $validated $parameters[2]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->updateParticipant(
                $parameters[1],
                $parameters[2]
            )
            ->generateResource();

        if($this->getParticipant()->wasChanged())
        {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new ParticipantResource(
            $this->getParticipant(),
            $this->getThread()
        ));

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if($this->shouldFireBroadcast())
        {
            $this->broadcaster
                ->to($this->getParticipant())
                ->with($this->generateBroadcastResource())
                ->broadcast(ParticipantPermissionsBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if($this->shouldFireEvents())
        {
            $this->dispatcher->dispatch(new ParticipantPermissionsEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
                $this->getParticipant(true)
            ));
        }

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'thread_id' => $this->getThread()->id
        ];
    }
}