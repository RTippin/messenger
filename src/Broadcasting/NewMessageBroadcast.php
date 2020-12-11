<?php

namespace RTippin\Messenger\Broadcasting;

use RTippin\Messenger\Broadcasting\Base\MessengerBroadcast;

class NewMessageBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new.message';
    }
}
