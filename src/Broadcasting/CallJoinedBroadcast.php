<?php

namespace RTippin\Messenger\Broadcasting;

use RTippin\Messenger\Broadcasting\Base\MessengerBroadcast;

class CallJoinedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'joined.call';
    }
}
