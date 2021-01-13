<?php

namespace RTippin\Messenger\Broadcasting;

class MessageArchivedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.archived';
    }
}
