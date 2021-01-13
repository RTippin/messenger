<?php

namespace RTippin\Messenger\Broadcasting;

class NewThreadBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new.thread';
    }
}
