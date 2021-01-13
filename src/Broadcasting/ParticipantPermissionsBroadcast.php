<?php

namespace RTippin\Messenger\Broadcasting;

class ParticipantPermissionsBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'permissions.updated';
    }
}
