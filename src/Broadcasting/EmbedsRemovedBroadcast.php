<?php

namespace RTippin\Messenger\Broadcasting;

class EmbedsRemovedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'embeds.removed';
    }
}
