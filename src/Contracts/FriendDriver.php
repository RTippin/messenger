<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;

interface FriendDriver
{
    /**
     * @return Friend|Builder
     */
    public function getProviderFriendsBuilder(): Builder;

    /**
     * @return PendingFriend|Builder
     */
    public function getProviderPendingFriendsBuilder(): Builder;

    /**
     * @return SentFriend|Builder
     */
    public function getProviderSentFriendsBuilder(): Builder;

    /**
     * @param bool $withRelations
     * @return Friend|Collection
     */
    public function getProviderFriends($withRelations = false);

    /**
     * @param bool $withRelations
     * @return PendingFriend|Collection
     */
    public function getProviderPendingFriends($withRelations = false);

    /**
     * @param bool $withRelations
     * @return SentFriend|Collection
     */
    public function getProviderSentFriends($withRelations = false);

    /**
     * @param null|mixed|MessengerProvider $provider
     * @return bool
     */
    public function isFriend($provider = null): bool;

    /**
     * @param null|mixed|MessengerProvider $provider
     * @return bool
     */
    public function isSentFriendRequest($provider = null): bool;

    /**
     * @param null|mixed|MessengerProvider $provider
     * @return bool
     */
    public function isPendingFriendRequest($provider = null): bool;

    /**
     * @param null|mixed|MessengerProvider $provider
     * @return int
     */
    public function friendStatus($provider = null): int;

    /**
     * @param int $friendStatus
     * @param null|mixed|MessengerProvider $provider
     * @return Friend|PendingFriend|SentFriend|null
     */
    public function getFriendResource(int $friendStatus, $provider = null);

    /**
     * @param Thread $thread
     * @return Friend[]|Collection
     */
    public function getProviderFriendsNotInThread(Thread $thread);
}
