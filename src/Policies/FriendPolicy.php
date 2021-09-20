<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
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
     * @param  Messenger  $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Determine whether the provider can view friends.
     *
     * @param $user
     * @return Response
     */
    public function viewAny($user): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the provider can view a friend.
     *
     * @param $user
     * @param  Friend  $friend
     * @return Response
     */
    public function view($user, Friend $friend): Response
    {
        return $this->messenger->providerHasFriends()
            && $friend->isOwnedByCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view friend.');
    }

    /**
     * Determine whether the provider can delete the friend.
     *
     * @param $user
     * @param  Friend  $friend
     * @return Response
     */
    public function delete($user, Friend $friend): Response
    {
        return $this->messenger->providerHasFriends()
        && $friend->isOwnedByCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to remove friend.');
    }
}
