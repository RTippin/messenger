<?php

namespace RTippin\Messenger\Actions;

use RTippin\Messenger\Http\Request\MessengerSettingsRequest;
use RTippin\Messenger\Messenger;

class UpdateMessengerSettings extends BaseMessengerAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * UpdateMessengerSettings constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @param mixed ...$parameters
     * @var array|MessengerSettingsRequest[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->messenger->getProviderMessenger()
            ->update($parameters[0]);

        $this->setOnlineCacheStatus($parameters[0]['online_status']);

        return $this;
    }

    /**
     * @param int $onlineStatus
     */
    private function setOnlineCacheStatus(int $onlineStatus): void
    {
        switch ($onlineStatus) {
            case 0:
                $this->messenger->setProviderToOffline();
            break;
            case 1:
                $this->messenger->setProviderToOnline();
            break;
            case 2:
                $this->messenger->setProviderToAway();
            break;
        }
    }
}
