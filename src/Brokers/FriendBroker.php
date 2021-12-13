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
use RTippin\Messenger\Support\Helpers;

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
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @inheritDoc
     */
    public function getProviderFriends(bool $withRelations = false)
    {
        if (! $this->messenger->providerHasFriends()) {
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
    public function getProviderPendingFriends(bool $withRelations = false)
    {
        if (! $this->messenger->providerHasFriends()) {
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
    public function getProviderSentFriends(bool $withRelations = false)
    {
        if (! $this->messenger->providerHasFriends()) {
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
            && Helpers::forProviderInCollection(
                $this->getProviderFriends(),
                $provider,
                'party'
            )->first();
    }

    /**
     * @inheritDoc
     */
    public function isSentFriendRequest($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && Helpers::forProviderInCollection(
                $this->getProviderSentFriends(),
                $provider,
                'recipient'
            )->first();
    }

    /**
     * @inheritDoc
     */
    public function isPendingFriendRequest($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && Helpers::forProviderInCollection(
                $this->getProviderPendingFriends(),
                $provider,
                'sender'
            )->first();
    }

    /**
     * @inheritDoc
     */
    public function friendStatus($provider = null): int
    {
        if ($this->isFriend($provider)) {
            return self::FRIEND;
        }

        if ($this->isSentFriendRequest($provider)) {
            return self::SENT_FRIEND_REQUEST;
        }

        if ($this->isPendingFriendRequest($provider)) {
            return self::PENDING_FRIEND_REQUEST;
        }

        return self::NOT_FRIEND;
    }

    /**
     * @inheritDoc
     */
    public function getFriendResource(int $friendStatus, $provider = null)
    {
        switch ($friendStatus) {
            case self::FRIEND:
                return $this->getFriend($provider);
            case self::SENT_FRIEND_REQUEST:
                return $this->getSentFriend($provider);
            case self::PENDING_FRIEND_REQUEST:
                return $this->getPendingFriend($provider);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getProviderFriendsNotInThread(Thread $thread)
    {
        if (! $this->messenger->providerHasFriends()) {
            return $this->sendEmptyCollection();
        }

        $participants = $thread->participants()->get();

        return $this->getProviderFriendsBuilder()
            ->get()
            ->reject(function (Friend $friend) use ($participants) {
                return $participants->where('owner_id', '=', $friend->party_id)
                    ->where('owner_type', '=', $friend->party_type)
                    ->first();
            })
            ->load('party');
    }

    /**
     * @return Friend|Builder
     */
    private function getProviderFriendsBuilder(): Builder
    {
        return Friend::forProvider($this->messenger->getProvider())
            ->whereIn('party_type', $this->messenger->getAllFriendableProviders());
    }

    /**
     * @return PendingFriend|Builder
     */
    private function getProviderPendingFriendsBuilder(): Builder
    {
        return PendingFriend::forProvider($this->messenger->getProvider(), 'recipient')
            ->whereIn('sender_type', $this->messenger->getAllFriendableProviders());
    }

    /**
     * @return SentFriend|Builder
     */
    private function getProviderSentFriendsBuilder(): Builder
    {
        return SentFriend::forProvider($this->messenger->getProvider(), 'sender')
            ->whereIn('recipient_type', $this->messenger->getAllFriendableProviders());
    }

    /**
     * @param $model
     * @return Friend|null
     */
    private function getFriend($model): ?Friend
    {
        return Helpers::forProviderInCollection(
            $this->getProviderFriends(),
            $model,
            'party'
        )->first();
    }

    /**
     * @param $model
     * @return SentFriend|null
     */
    private function getSentFriend($model): ?SentFriend
    {
        return Helpers::forProviderInCollection(
            $this->getProviderSentFriends(),
            $model,
            'recipient'
        )->first();
    }

    /**
     * @param $model
     * @return PendingFriend|null
     */
    private function getPendingFriend($model): ?PendingFriend
    {
        return Helpers::forProviderInCollection(
            $this->getProviderPendingFriends(),
            $model,
            'sender'
        )->first();
    }

    /**
     * @return Collection
     */
    private function sendEmptyCollection(): Collection
    {
        return Collection::make();
    }
}
