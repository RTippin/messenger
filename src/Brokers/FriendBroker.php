<?php

namespace RTippin\Messenger\Brokers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;

class FriendBroker implements FriendDriver
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var Collection|null
     */
    protected ?Collection $friends = null;

    /**
     * @var Collection|null
     */
    protected ?Collection $pendingFriends = null;

    /**
     * @var Collection|null
     */
    protected ?Collection $sentFriends = null;

    /**
     * FriendBroker constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @inheritDoc
     */
    public function getProviderFriendsBuilder(): Builder
    {
        return Friend::where('owner_id', '=', $this->messenger->getProviderId())
            ->where('owner_type', '=', $this->messenger->getProviderClass())
            ->whereIn('party_type',  $this->messenger->getAllFriendableProviders());
    }

    /**
     * @inheritDoc
     */
    public function getProviderPendingFriendsBuilder(): Builder
    {
        return PendingFriend::where('recipient_id', '=', $this->messenger->getProviderId())
            ->where('recipient_type', '=', $this->messenger->getProviderClass())
            ->whereIn('sender_type',  $this->messenger->getAllFriendableProviders());
    }

    /**
     * @inheritDoc
     */
    public function getProviderSentFriendsBuilder(): Builder
    {
        return SentFriend::where('sender_id', '=', $this->messenger->getProviderId())
            ->where('sender_type', '=', $this->messenger->getProviderClass())
            ->whereIn('recipient_type',  $this->messenger->getAllFriendableProviders());
    }

    /**
     * @inheritDoc
     */
    public function getProviderFriends($withRelations = false)
    {
        if( ! $this->messenger->providerHasFriends())
        {
            return $this->sendEmptyCollection();
        }

        return is_null($this->friends)
            ? $this->friends = $this->getProviderFriendsBuilder()
                ->with($withRelations ? 'party' : [])
                ->get()
            : $this->friends;
    }

    /**
     * @inheritDoc
     */
    public function getProviderPendingFriends($withRelations = false)
    {
        if( ! $this->messenger->providerHasFriends())
        {
            return $this->sendEmptyCollection();
        }

        return is_null($this->pendingFriends)
            ? $this->pendingFriends = $this->getProviderPendingFriendsBuilder()
                ->with($withRelations ? 'sender' : [])
                ->latest()
                ->get()
            : $this->pendingFriends;
    }

    /**
     * @inheritDoc
     */
    public function getProviderSentFriends($withRelations = false)
    {
        if( ! $this->messenger->providerHasFriends())
        {
            return $this->sendEmptyCollection();
        }

        return is_null($this->sentFriends)
            ? $this->sentFriends = $this->getProviderSentFriendsBuilder()
                ->with($withRelations ? 'recipient' : [])
                ->latest()
                ->get()
            : $this->sentFriends;
    }

    /**
     * @inheritDoc
     */
    public function isFriend($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && $this->getProviderFriends()
                ->where('party_id', $provider->getKey())
                ->where('party_type', get_class($provider))
                ->first();
    }

    /**
     * @inheritDoc
     */
    public function isSentFriendRequest($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && $this->getProviderSentFriends()
            ->where('recipient_id', $provider->getKey())
            ->where('recipient_type', get_class($provider))
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function isPendingFriendRequest($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && $this->getProviderPendingFriends()
            ->where('sender_id', $provider->getKey())
            ->where('sender_type', get_class($provider))
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function friendStatus($provider = null): int
    {
        if($this->isFriend($provider))
        {
            return 1;
        }
        if($this->isSentFriendRequest($provider))
        {
            return 2;
        }
        if($this->isPendingFriendRequest($provider))
        {
            return 3;
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getFriendResource(int $friendStatus, $provider = null)
    {
        switch($friendStatus)
        {
            case 1:
                return $this->getFriend($provider);
            case 2:
                return $this->getSentFriend($provider);
            case 3:
                return $this->getPendingFriend($provider);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getProviderFriendsNotInThread(Thread $thread)
    {
        if( ! $this->messenger->providerHasFriends())
        {
            return $this->sendEmptyCollection();
        }

        $participants = $thread->participants()->get();

        return $this->getProviderFriendsBuilder()
            ->get()
            ->reject(
                fn(Friend $friend) => $participants->where('owner_id', '=', $friend->party_id)
                    ->where('owner_type', '=', $friend->party_type)
                    ->first()
            )
            ->load('party');
    }

    /**
     * @param $model
     * @return Friend|null
     */
    private function getFriend($model): ?Friend
    {
        return $this->getProviderFriends()
            ->where('party_id', $model->id)
            ->where('party_type', get_class($model))
            ->first();
    }

    /**
     * @param $model
     * @return SentFriend|null
     */
    private function getSentFriend($model): ?SentFriend
    {
        return $this->getProviderSentFriends()
            ->where('recipient_id', $model->id)
            ->where('recipient_type', get_class($model))
            ->first();
    }

    /**
     * @param $model
     * @return PendingFriend|null
     */
    private function getPendingFriend($model): ?PendingFriend
    {
        return $this->getProviderPendingFriends()
            ->where('sender_id', $model->id)
            ->where('sender_type', get_class($model))
            ->first();
    }

    /**
     * @return Collection
     */
    private function sendEmptyCollection(): Collection
    {
        return new Collection();
    }
}