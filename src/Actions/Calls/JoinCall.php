<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
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
     * @param Repository $cacheDriver
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     */
    public function __construct(Repository $cacheDriver,
                                Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        parent::__construct($cacheDriver);

        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * Join/Re-join the call and set the call participant in cache.
     *
     * @param mixed ...$parameters
     * @var Call $parameters[0]
     * @var bool $parameters[1]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setCall($parameters[0]);

        if ($parameters[1] || ! $this->getCall()->hasJoinedCall()) {
            $this->storeParticipant($this->messenger->getProvider());
        } else {
            $this->updateParticipant(...$this->participantAttributes());
        }

        $this->setParticipantInCallCache($this->getCallParticipant())
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

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
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new CallParticipantResource(
            $this->getCallParticipant()
        ));

        return $this;
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
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallJoinedEvent(
                $this->getCall(true),
                $this->getCallParticipant(true)
            ));
        }

        return $this;
    }
}
