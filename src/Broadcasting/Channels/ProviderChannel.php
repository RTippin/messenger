<?php

namespace RTippin\Messenger\Broadcasting\Channels;

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
     * @return bool
     */
    public function join($user, $alias = null, $id = null): bool
    {
        return ! is_null($alias)
            && ! is_null($id)
            && $this->messenger->getProviderAlias() === $alias
            && (string) $this->messenger->getProvider()->getKey() === $id;
    }
}
