<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;

interface FriendDriver
{
    const NOT_FRIEND = 0;
    const FRIEND = 1;
    const SENT_FRIEND_REQUEST = 2;
    const PENDING_FRIEND_REQUEST = 3;
    const STATUS = [
        0 => 'NOT_FRIEND',
        1 => 'FRIEND',
        2 => 'SENT_FRIEND_REQUEST',
        3 => 'PENDING_FRIEND_REQUEST',
    ];

    /**
     * @param  bool  $withRelations
     * @return Friend|Collection
     */
    public function getProviderFriends(bool $withRelations = false);

    /**
     * @param  bool  $withRelations
     * @return PendingFriend|Collection
     */
    public function getProviderPendingFriends(bool $withRelations = false);

    /**
     * @param  bool  $withRelations
     * @return SentFriend|Collection
     */
    public function getProviderSentFriends(bool $withRelations = false);

    /**
     * @param  null|mixed|MessengerProvider  $provider
     * @return bool
     */
    public function isFriend($provider = null): bool;

    /**
     * @param  null|mixed|MessengerProvider  $provider
     * @return bool
     */
    public function isSentFriendRequest($provider = null): bool;

    /**
     * @param  null|mixed|MessengerProvider  $provider
     * @return bool
     */
    public function isPendingFriendRequest($provider = null): bool;

    /**
     * @param  null|mixed|MessengerProvider  $provider
     * @return int
     */
    public function friendStatus($provider = null): int;

    /**
     * @param  int  $friendStatus
     * @param  null|mixed|MessengerProvider  $provider
     * @return Friend|PendingFriend|SentFriend|null
     */
    public function getFriendResource(int $friendStatus, $provider = null);

    /**
     * @param  Thread  $thread
     * @return Friend[]|Collection
     */
    public function getProviderFriendsNotInThread(Thread $thread);
}
