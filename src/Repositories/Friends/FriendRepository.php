<?php

namespace RTippin\Messenger\Repositories\Friends;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Thread;

class FriendRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * FriendRepository constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return Friend|Collection|\Illuminate\Support\Collection
     */
    public function getProviderFriends()
    {
        if( ! $this->messenger->providerHasFriends())
        {
            return $this->sendEmptyCollection();
        }

        return $this->messenger->getProvider()
            ->friends()
            ->with('party')
            ->get();
    }

    /**
     * @param Thread $thread
     * @return Friend[]|Collection|\Illuminate\Support\Collection
     */
    public function getProviderFriendsNotInThread(Thread $thread)
    {
        if( ! $this->messenger->providerHasFriends())
        {
            return $this->sendEmptyCollection();
        }

        $participants = $thread->participants()->get();

        return $this->messenger->getProvider()
            ->friends()
            ->get()
            ->reject(fn(
                Friend $friend) => $participants
                ->where('owner_id', '=', $friend->party_id)
                ->where('owner_type', '=', $friend->party_type)
                ->first()
            )
            ->load('party');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function sendEmptyCollection(): \Illuminate\Support\Collection
    {
        return collect();
    }
}