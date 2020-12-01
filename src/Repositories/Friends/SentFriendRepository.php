<?php

namespace RTippin\Messenger\Repositories\Friends;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\SentFriend;

class SentFriendRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * SentFriendRepository constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return SentFriend|Collection
     */
    public function getProviderSentFriends()
    {
        return $this->messenger->getProvider()
            ->sentFriendRequest()
            ->with('recipient')
            ->latest()
            ->get();
    }
}