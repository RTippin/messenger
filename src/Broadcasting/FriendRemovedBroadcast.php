<?php

namespace RTippin\Messenger\Broadcasting;

class FriendRemovedBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'friend.removed';
    }
}
