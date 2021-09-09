<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Http\Resources\Broadcast\CallBroadcastResource;
use RTippin\Messenger\Http\Resources\CallParticipantResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;

class JoinCall extends CallParticipantAction
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
     * JoinCall constructor.
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
     * Join/Re-join the call and set the call participant in cache.
     *
     * @param  Call  $call
     * @param  bool  $isNewCall
     * @return $this
     */
    public function execute(Call $call, bool $isNewCall = false): self
    {
        $this->setCall($call);

        if ($isNewCall || ! $this->getCall()->hasJoinedCall()) {
            $this->storeParticipant($this->messenger->getProvider());
        } else {
            $this->updateParticipant(...$this->participantAttributes());
        }

        if ($isNewCall
            || $this->getCallParticipant()->wasRecentlyCreated
            || $this->getCallParticipant()->wasChanged()) {
            $this->setParticipantInCallCache($this->getCallParticipant())
                ->fireBroadcast()
                ->fireEvents();
        }

        $this->generateResource();

        return $this;
    }

    /**
     * @return array
     */
    private function participantAttributes(): array
    {
        return [
            $this->getCall()->currentCallParticipant(),
            [
                'left_call' => null,
            ],
        ];
    }

    /**
     * @return void
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new CallParticipantResource(
            $this->getCallParticipant()
        ));
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
                ->broadcast(CallJoinedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallJoinedEvent(
                $this->getCall(true),
                $this->getCallParticipant(true)
            ));
        }
    }
}
