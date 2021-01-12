<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Friend;

class FriendPolicy
{
    use HandlesAuthorization;

    /**
     * @var Messenger
     */
    public Messenger $messenger;

    /**
     * FriendPolicy constructor.
     *
     * @param Messenger $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Determine whether the provider view any models.
     *
     * @param $user
     * @return mixed
     */
    public function viewAny($user)
    {
        return $this->allow();
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Friend $friend
     * @return mixed
     */
    public function view($user, Friend $friend)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $friend->owner_id
            && $this->messenger->getProviderClass() === $friend->owner_type)
            ? $this->allow()
            : $this->deny('Not authorized to view friend');
    }

    /**
     * Determine whether the provider can delete the model.
     *
     * @param $user
     * @param Friend $friend
     * @return mixed
     */
    public function delete($user, Friend $friend)
    {
        return ($this->messenger->providerHasFriends()
            && $this->messenger->getProviderId() == $friend->owner_id
            && $this->messenger->getProviderClass() === $friend->owner_type)
            ? $this->allow()
            : $this->deny('Not authorized to remove friend');
    }
}
