<?php

namespace RTippin\Messenger\Broadcasting;

class FriendDeniedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'friend.denied';
    }
}
