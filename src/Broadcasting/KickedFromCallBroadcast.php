<?php

namespace RTippin\Messenger\Broadcasting;

class KickedFromCallBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.kicked';
    }
}
