<?php

namespace RTippin\Messenger\Broadcasting;

class MessageEditedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.edited';
    }
}
