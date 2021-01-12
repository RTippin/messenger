<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\PendingFriend;

class PendingFriendPolicy
{
    use HandlesAuthorization;

    /**
     * @var Messenger
     */
    public Messenger $messenger;

    /**
     * PendingFriendPolicy constructor.
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
            : $this->deny('Not authorized to view pending friend request');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param PendingFriend $pending
     * @return mixed
     */
    public function view($user, PendingFriend $pending)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $pending->recipient_id
            && $this->messenger->getProviderClass() === $pending->recipient_type)
            ? $this->allow()
            : $this->deny('Not authorized to view pending friend request');
    }

    /**
     * Determine whether the provider can update the model.
     *
     * @param $user
     * @param PendingFriend $pending
     * @return mixed
     */
    public function update($user, PendingFriend $pending)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $pending->recipient_id
            && $this->messenger->getProviderClass() === $pending->recipient_type)
            ? $this->allow()
            : $this->deny('Not authorized to accept pending friend request');
    }

    /**
     * Determine whether the provider can delete the model.
     *
     * @param $user
     * @param PendingFriend $pending
     * @return mixed
     */
    public function delete($user, PendingFriend $pending)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $pending->recipient_id
            && $this->messenger->getProviderClass() === $pending->recipient_type)
            ? $this->allow()
            : $this->deny('Not authorized to deny pending friend request');
    }
}
