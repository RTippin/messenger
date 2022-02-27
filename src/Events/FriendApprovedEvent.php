<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Friend;

class FriendApprovedEvent
{
    use SerializesModels;

    /**
     * @param  Friend  $friend
     * @param  Friend  $inverseFriend
     */
    public function __construct(
        public Friend $friend,
        public Friend $inverseFriend
    ) {
    }
}
