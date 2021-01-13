<?php

namespace RTippin\Messenger\Broadcasting;

class FriendRequestBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'friend.request';
    }
}
