<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;

/**
 * App\Traits\HasFriends
 *
 * @property-read MorphMany|Friend $friends
 * @property-read MorphMany|SentFriend $sentFriendRequest
 * @property-read MorphMany|PendingFriend $pendingFriendRequest
 * @mixin Model
 */

trait HasFriends
{
    /**
     * @return mixed|MorphMany|Friend
     */
    public function friends()
    {
        return $this->morphMany(
            Friend::class,
            'owner'
        )->whereIn(
            'party_type',
            messenger()->getAllFriendableProviders()
        );
    }

    /**
     * @return mixed|MorphMany|SentFriend
     */
    public function sentFriendRequest()
    {
        return $this->morphMany(
            SentFriend::class,
            'sender'
        )->whereIn(
            'recipient_type',
            messenger()->getAllFriendableProviders()
        );
    }

    /**
     * @return mixed|MorphMany|PendingFriend
     */
    public function pendingFriendRequest()
    {
        return $this->morphMany(
            PendingFriend::class,
            'recipient'
        )->whereIn(
            'sender_type',
            messenger()->getAllFriendableProviders()
        );
    }

    /**
     * @param $model
     * @return bool
     */
    public function isFriend($model)
    {
        return $this->friends
            ->where('party_id', $model->id)
            ->where('party_type', get_class($model))
            ->first()
            ? true
            : false;
    }

    /**
     * @param $model
     * @return bool
     */
    public function isSentFriendRequest($model)
    {
        return $this->sentFriendRequest
            ->where('recipient_id', $model->id)
            ->where('recipient_type', get_class($model))
            ->first()
            ? true
            : false;
    }

    /**
     * @param $model
     * @return bool
     */
    public function isPendingFriendRequest($model)
    {
        return $this->pendingFriendRequest
            ->where('sender_id', $model->id)
            ->where('sender_type', get_class($model))
            ->first()
            ? true
            : false;
    }

    /**
     * @param $model
     * @return int
     */
    public function friendStatus($model)
    {
        if($this->isFriend($model)){
            return 1;
        }
        if($this->isSentFriendRequest($model)){
            return 2;
        }
        if($this->isPendingFriendRequest($model)){
            return 3;
        }
        return 0;
    }

    /**
     * @param int $friendStatus
     * @param $model
     * @return Friend|PendingFriend|SentFriend|null
     */
    public function getFriendResource(int $friendStatus, $model)
    {
        switch($friendStatus){
            case 1:
                return $this->getFriend($model);
            case 2:
                return $this->getSentFriend($model);
            case 3:
                return $this->getPendingFriend($model);
        }

        return null;
    }

    /**
     * @param $model
     * @return Friend|null
     */
    private function getFriend($model)
    {
        return $this->friends
            ->where('party_id', $model->id)
            ->where('party_type', get_class($model))
            ->first();
    }

    /**
     * @param $model
     * @return SentFriend|null
     */
    private function getSentFriend($model)
    {
        return $this->sentFriendRequest
            ->where('recipient_id', $model->id)
            ->where('recipient_type', get_class($model))
            ->first();
    }

    /**
     * @param $model
     * @return PendingFriend|null
     */
    private function getPendingFriend($model)
    {
        return $this->pendingFriendRequest
            ->where('sender_id', $model->id)
            ->where('sender_type', get_class($model))
            ->first();
    }
}