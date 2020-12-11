<?php

namespace RTippin\Messenger\Broadcasting;

use RTippin\Messenger\Broadcasting\Base\MessengerBroadcast;

class CallLeftBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'left.call';
    }
}
