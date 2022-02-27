<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\PendingFriend;

class FriendDeniedEvent
{
    use SerializesModels;

    /**
     * @param  PendingFriend  $friend
     */
    public function __construct(
        public PendingFriend $friend
    ){}
}
