<?php

namespace RTippin\Messenger\Broadcasting;

class CallStartedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'incoming.call';
    }
}
