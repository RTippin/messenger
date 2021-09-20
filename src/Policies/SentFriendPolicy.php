<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
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
     * @param  Messenger  $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Determine whether the provider can view sent friend request.
     *
     * @param $user
     * @return Response
     */
    public function viewAny($user): Response
    {
        return $this->messenger->providerHasFriends()
            ? $this->allow()
            : $this->deny('Not authorized to view sent friend request.');
    }

    /**
     * Determine whether the provider can view the sent friend request.
     *
     * @param $user
     * @param  SentFriend  $sent
     * @return Response
     */
    public function view($user, SentFriend $sent): Response
    {
        return $this->messenger->providerHasFriends()
        && $sent->isSenderCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view sent friend request.');
    }

    /**
     * Determine whether the provider can add new friend request.
     *
     * @param $user
     * @return Response
     */
    public function create($user): Response
    {
        return $this->messenger->providerHasFriends()
            ? $this->allow()
            : $this->deny('Not authorized add friends.');
    }

    /**
     * Determine whether the provider can cancel sent friend request.
     *
     * @param $user
     * @param  SentFriend  $sent
     * @return Response
     */
    public function delete($user, SentFriend $sent): Response
    {
        return $this->messenger->providerHasFriends()
        && $sent->isSenderCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view remove friend request.');
    }
}
