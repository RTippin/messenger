<?php

namespace RTippin\Messenger\Repositories\Friends;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\PendingFriend;

class PendingFriendRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * PendingFriendRepository constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return PendingFriend|Collection
     */
    public function getProviderPendingFriends()
    {
        return $this->messenger->getProvider()
            ->pendingFriendRequest()
            ->with('sender')
            ->latest()
            ->get();
    }
}