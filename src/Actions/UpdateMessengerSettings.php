<?php

namespace RTippin\Messenger\Actions;

use RTippin\Messenger\Actions\Base\BaseMessengerAction;
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
     * @var array|MessengerSettingsRequest $attributes $parameters[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->messenger->getProviderMessenger()
            ->update($parameters[0]);

        return $this;
    }
}