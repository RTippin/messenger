<?php

namespace RTippin\Messenger\Broadcasting;

class CallIgnoredBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.ignored';
    }
}
