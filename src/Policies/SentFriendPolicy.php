<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\SentFriend;

class SentFriendPolicy
{
    use HandlesAuthorization;

    /**
     * @var Messenger
     */
    public Messenger $messenger;

    /**
     * SentFriendPolicy constructor.
     *
     * @param Messenger $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Determine whether the provider can view any models.
     *
     * @param $user
     * @return mixed
     */
    public function viewAny($user)
    {
        return $this->messenger->providerHasFriends()
            ? $this->allow()
            : $this->deny('Not authorized to view sent friend request');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param SentFriend $sent
     * @return mixed
     */
    public function view($user, SentFriend $sent)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $sent->sender_id
            && $this->messenger->getProviderClass() === $sent->sender_type)
            ? $this->allow()
            : $this->deny('Not authorized to view sent friend request');
    }

    /**
     * Determine whether the provider can create models.
     *
     * @param $user
     * @return mixed
     */
    public function create($user)
    {
        return $this->messenger->providerHasFriends()
            ? $this->allow()
            : $this->deny('Not authorized add friends');
    }

    /**
     * Determine whether the provider can delete the model.
     *
     * @param $user
     * @param SentFriend $sent
     * @return mixed
     */
    public function delete($user, SentFriend $sent)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $sent->sender_id
            && $this->messenger->getProviderClass() === $sent->sender_type)
            ? $this->allow()
            : $this->deny('Not authorized to view remove friend request');
    }
}
