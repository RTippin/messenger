<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\SentFriend;

class FriendRequestEvent
{
    use SerializesModels;

    /**
     * @param  SentFriend  $friend
     */
    public function __construct(
        public SentFriend $friend
    ) {
    }
}
