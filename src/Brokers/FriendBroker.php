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
        return Friend::forProvider($this->messenger->getProvider())
            ->whereIn('party_type', $this->messenger->getAllFriendableProviders());
    }

    /**
     * @inheritDoc
     */
    public function getProviderPendingFriendsBuilder(): Builder
    {
        return PendingFriend::forProvider($this->messenger->getProvider(), 'recipient')
            ->whereIn('sender_type', $this->messenger->getAllFriendableProviders());
    }

    /**
     * @inheritDoc
     */
    public function getProviderSentFriendsBuilder(): Builder
    {
        return SentFriend::forProvider($this->messenger->getProvider(), 'sender')
            ->whereIn('recipient_type', $this->messenger->getAllFriendableProviders());
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
            && $this->getProviderFriends()->forProvider($provider, 'party')->first();
    }

    /**
     * @inheritDoc
     */
    public function isSentFriendRequest($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && $this->getProviderSentFriends()->forProvider($provider, 'recipient')->first();
    }

    /**
     * @inheritDoc
     */
    public function isPendingFriendRequest($provider = null): bool
    {
        return $this->messenger->isValidMessengerProvider($provider)
            && $this->getProviderPendingFriends()->forProvider($provider, 'sender')->first();
    }

    /**
     * @inheritDoc
     */
    public function friendStatus($provider = null): int
    {
        if ($this->isFriend($provider)) {
            return 1;
        }
        if ($this->isSentFriendRequest($provider)) {
            return 2;
        }
        if ($this->isPendingFriendRequest($provider)) {
            return 3;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getFriendResource(int $friendStatus, $provider = null)
    {
        switch ($friendStatus) {
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
     * @param $model
     * @return Friend|null
     */
    private function getFriend($model): ?Friend
    {
        return $this->getProviderFriends()->forProvider($model, 'party')->first();
    }

    /**
     * @param $model
     * @return SentFriend|null
     */
    private function getSentFriend($model): ?SentFriend
    {
        return $this->getProviderSentFriends()->forProvider($model, 'recipient')->first();
    }

    /**
     * @param $model
     * @return PendingFriend|null
     */
    private function getPendingFriend($model): ?PendingFriend
    {
        return $this->getProviderPendingFriends()->forProvider($model, 'sender')->first();
    }

    /**
     * @return Collection
     */
    private function sendEmptyCollection(): Collection
    {
        return new Collection();
    }
}
