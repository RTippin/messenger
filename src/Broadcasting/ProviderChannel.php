<?php

namespace RTippin\Messenger\Broadcasting;


use RTippin\Messenger\Messenger;

class ProviderChannel
{
    /**
     * @var Messenger
     */
    public Messenger $messenger;

    /**
     * Create a new channel instance.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param $user
     * @param $alias
     * @param $id
     * @return array|bool
     */
    public function join($user, $alias = null, $id = null)
    {
        return ! is_null($alias)
            && ! is_null($id)
            && $this->messenger->getProviderAlias() === $alias
            && $this->messenger->getProviderId() === $id;
    }
}
