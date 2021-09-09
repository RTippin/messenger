<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Friend;

class FriendRemovedEvent
{
    use SerializesModels;

    /**
     * @var Friend
     */
    public Friend $friend;

    /**
     * @var Friend|null
     */
    public ?Friend $inverseFriend;

    /**
     * Create a new event instance.
     *
     * @param  Friend  $friend
     * @param  Friend|null  $inverseFriend
     */
    public function __construct(Friend $friend, ?Friend $inverseFriend)
    {
        $this->friend = $friend;
        $this->inverseFriend = $inverseFriend;
    }
}
