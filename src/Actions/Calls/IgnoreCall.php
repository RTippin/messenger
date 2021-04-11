<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Events\Dispatcher;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\CallIgnoredBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\CallIgnoredEvent;
use RTippin\Messenger\Http\Resources\Broadcast\CallBroadcastResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use Throwable;

class IgnoreCall extends BaseMessengerAction
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
     * @var EndCall
     */
    private EndCall $endCall;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * IgnoreCall constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param EndCall $endCall
     * @param Messenger $messenger
     */
    public function __construct(BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                EndCall $endCall,
                                Messenger $messenger)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->endCall = $endCall;
        $this->messenger = $messenger;
    }

    /**
     * Ignore the incoming call. If private call, we will
     * end it immediately as well.
     *
     * @param mixed ...$parameters
     * @return $this
     * @var Thread[0]
     * @var Call[1]
     * @throws Throwable|InvalidArgumentException
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])->setCall($parameters[1]);

        $this->getCall()->setRelations([
            'thread' => $this->getThread(),
        ]);

        $this->fireBroadcast()->fireEvents();


        if ($this->getThread()->isPrivate()) {
            $this->endCall();
        }

        return $this;
    }

    /**
     * End the private call that was ignored.
     *
     * @throws Throwable|InvalidArgumentException
     */
    private function endCall(): void
    {
        $this->endCall->execute($this->getCall());
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
                ->to($this->messenger->getProvider())
                ->with($this->generateBroadcastResource())
                ->broadcast(CallIgnoredBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new CallIgnoredEvent(
                $this->getCall(true),
                $this->messenger->getProvider()->withoutRelations()
            ));
        }

        return $this;
    }
}
