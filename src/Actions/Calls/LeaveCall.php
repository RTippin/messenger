<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Broadcasting\CallLeftBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Http\Resources\Broadcast\CallBroadcastResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;

class LeaveCall extends CallParticipantAction
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
     * LeaveCall constructor.
     *
     * @param Repository $cacheDriver
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     */
    public function __construct(Repository $cacheDriver,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        parent::__construct($cacheDriver);

        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Leave the call!
     *
     * @param mixed ...$parameters
     * @var Call[0]
     * @var CallParticipant[1]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setCall($parameters[0])
            ->updateParticipant(...$this->participantLeft($parameters[1]))
            ->removeParticipantInCallCache($parameters[1])
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param CallParticipant $participant
     * @return array
     */
    private function participantLeft(CallParticipant $participant): array
    {
        return [
            $participant,
            [
                'left_call' => now(),
            ],
        ];
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new CallBroadcastResource(
            $this->getCall()
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->getCallParticipant())
                ->with($this->generateBroadcastResource())
                ->broadcast(CallLeftBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallLeftEvent(
                $this->getCall(true),
                $this->getCallParticipant(true)
            ));
        }
    }
}
