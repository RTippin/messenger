<?php

namespace RTippin\Messenger\Broadcasting;

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
