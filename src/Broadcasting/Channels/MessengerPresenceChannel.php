<?php

namespace RTippin\Messenger\Broadcasting\Channels;

use Illuminate\Broadcasting\Channel;
use RTippin\Messenger\Contracts\HasPresenceChannel;

class MessengerPresenceChannel extends Channel
{
    /**
     * Create a new presence channel instance.
     *
     * @param  HasPresenceChannel  $model
     */
    public function __construct(HasPresenceChannel $model)
    {
        parent::__construct('presence-messenger.'.$model->getPresenceChannel());
    }
}
