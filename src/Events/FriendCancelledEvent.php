<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\SentFriend;

class FriendCancelledEvent
{
    use SerializesModels;

    /**
     * @var SentFriend
     */
    public SentFriend $friend;

    /**
     * Create a new event instance.
     *
     * @param  SentFriend  $friend
     */
    public function __construct(SentFriend $friend)
    {
        $this->friend = $friend;
    }
}
