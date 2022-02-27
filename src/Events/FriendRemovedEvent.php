<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Friend;

class FriendRemovedEvent
{
    use SerializesModels;

    /**
     * @param  Friend  $friend
     * @param  Friend|null  $inverseFriend
     */
    public function __construct(
        public Friend $friend,
        public ?Friend $inverseFriend
    ) {
    }
}
