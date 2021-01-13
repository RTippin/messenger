<?php

namespace RTippin\Messenger\Broadcasting;

class ThreadArchivedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'thread.archived';
    }
}
