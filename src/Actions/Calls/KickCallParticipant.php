<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Broadcasting\KickedFromCallBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\KickedFromCallEvent;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;

class KickCallParticipant extends CallParticipantAction
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
     * KickCallParticipant constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
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
     * Kick or un-kick the call participant!
     *
     * @param  Call  $call
     * @param  CallParticipant  $participant
     * @param  bool  $kicked
     * @return $this
     */
    public function execute(Call $call,
                            CallParticipant $participant,
                            bool $kicked): self
    {
        $this->setCall($call)
            ->updateParticipant(
                $participant,
                $this->participantState($kicked)
            );

        if ($this->getCallParticipant()->wasChanged()) {
            if ($kicked) {
                $this->removeParticipantInCallCache($participant);
            }

            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @param  bool  $kicked
     * @return array
     */
    private function participantState(bool $kicked): array
    {
        return $kicked
            ? [
                'kicked' => true,
                'left_call' => now(),
            ]
            : [
                'kicked' => false,
            ];
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'thread_id' => $this->getCall()->thread_id,
            'call_id' => $this->getCall()->id,
            'kicked' => $this->getCallParticipant()->kicked,
        ];
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
                ->broadcast(KickedFromCallBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new KickedFromCallEvent(
                $this->messenger->getProvider(true),
                $this->getCall(true),
                $this->getCallParticipant(true)
            ));
        }
    }
}
