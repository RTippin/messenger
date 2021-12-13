<?php

namespace RTippin\Messenger\Brokers;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Models\Thread;

class NullFriendBroker implements FriendDriver
{
    /**
     * @inheritDoc
     */
    public function getProviderFriends(bool $withRelations = false)
    {
        return Collection::make();
    }

    /**
     * @inheritDoc
     */
    public function getProviderPendingFriends(bool $withRelations = false)
    {
        return Collection::make();
    }

    /**
     * @inheritDoc
     */
    public function getProviderSentFriends(bool $withRelations = false)
    {
        return Collection::make();
    }

    /**
     * @inheritDoc
     */
    public function isFriend($provider = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isSentFriendRequest($provider = null): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isPendingFriendRequest($provider = null): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function friendStatus($provider = null): int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function getFriendResource(int $friendStatus, $provider = null)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getProviderFriendsNotInThread(Thread $thread)
    {
        return Collection::make();
    }
}
