<?php

namespace RTippin\Messenger\Actions\Messenger;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
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
     * @param  Dispatcher  $dispatcher
     * @param  Request  $request
     * @param  Messenger  $messenger
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
     * @param  bool  $away
     * @return $this
     */
    public function execute(bool $away): self
    {
        $this->setOnlineStatus($away)
            ->updateLastActiveTime()
            ->fireEvents($away);

        return $this;
    }

    /**
     * @param  bool  $away
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
    private function updateLastActiveTime(): self
    {
        if ($this->messenger->isOnlineStatusEnabled()
            && $this->messenger->getProviderMessenger()->online_status !== MessengerProvider::OFFLINE) {
            $this->messenger->getProvider()->forceFill([
                $this->messenger->getProvider()->getProviderLastActiveColumn() => now(),
            ])->save();
        }

        return $this;
    }

    /**
     * @param  bool  $away
     * @return void
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
