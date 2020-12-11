<?php

namespace RTippin\Messenger\Broadcasting;

use RTippin\Messenger\Broadcasting\Base\MessengerBroadcast;

class KnockBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'knock.knock';
    }
}
