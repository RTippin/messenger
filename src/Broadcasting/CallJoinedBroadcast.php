<?php

namespace RTippin\Messenger\Broadcasting;

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
