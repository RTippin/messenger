<?php

namespace RTippin\Messenger\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Messenger;

class OnlineStatus extends BaseMessengerAction
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * OnlineStatus constructor.
     *
     * @param Dispatcher $dispatcher
     * @param Request $request
     * @param Messenger $messenger
     */
    public function __construct(Dispatcher $dispatcher,
                                Request $request,
                                Messenger $messenger)
    {
        $this->request = $request;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * @param mixed ...$parameters
     * @var bool[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setOnlineStatus($parameters[0])
            ->touchProvider()
            ->fireEvents($parameters[0]);

        return $this;
    }

    /**
     * @param bool $away
     * @return $this
     */
    private function setOnlineStatus(bool $away): self
    {
        if ($this->messenger->isOnlineStatusEnabled()) {
            $away
                ? $this->messenger->setProviderToAway()
                : $this->messenger->setProviderToOnline();
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function touchProvider(): self
    {
        if ($this->messenger->isOnlineStatusEnabled()
            && $this->messenger->getProviderMessenger()->online_status !== 0) {
            $this->messenger->getProvider()->touch();
        }

        return $this;
    }

    /**
     * @param bool $away
     */
    private function fireEvents(bool $away): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(
                new StatusHeartbeatEvent(
                    $this->messenger->getProvider(),
                    $away,
                    $this->request->ip()
                )
            );
        }
    }
}
