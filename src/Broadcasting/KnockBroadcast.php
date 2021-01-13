<?php

namespace RTippin\Messenger\Broadcasting;

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
