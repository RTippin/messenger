<?php

namespace RTippin\Messenger\Actions\Messenger;

use RTippin\Messenger\Actions\BaseMessengerAction;
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
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @param  array  $params
     * @return $this
     *
     * @see MessengerSettingsRequest
     */
    public function execute(array $params): self
    {
        $this->messenger->getProviderMessenger()->update($params);

        $this->setOnlineCacheStatus($params['online_status']);

        return $this;
    }

    /**
     * @param  int  $onlineStatus
     * @return void
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
