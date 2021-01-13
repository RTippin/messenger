<?php

namespace RTippin\Messenger\Broadcasting;

class PromotedAdminBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'promoted.admin';
    }
}
