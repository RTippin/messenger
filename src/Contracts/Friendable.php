<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;

/**
 * App\Contracts\Friendable
 *
 * @mixin Model
 * @noinspection SpellCheckingInspection
 */
interface Friendable
{
    /**
     * @return mixed|MorphMany|Friend
     */
    public function friends();

    /**
     * @return mixed|MorphMany|SentFriend
     */
    public function sentFriendRequest();

    /**
     * @return mixed|MorphMany|PendingFriend
     */
    public function pendingFriendRequest();

    /**
     * @param $model
     * @return bool
     */
    public function isFriend($model);

    /**
     * @param $model
     * @return bool
     */
    public function isSentFriendRequest($model);

    /**
     * @param $model
     * @return bool
     */
    public function isPendingFriendRequest($model);

    /**
     * @param $model
     * @return int
     */
    public function friendStatus($model);

    /**
     * @param int $friendStatus
     * @param $model
     * @return Friend|PendingFriend|SentFriend|null
     */
    public function getFriendResource(int $friendStatus, $model);
}