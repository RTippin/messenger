<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
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
     * @param  Messenger  $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Determine whether the provider can view pending friends.
     *
     * @param $user
     * @return Response
     */
    public function viewAny($user): Response
    {
        return $this->messenger->providerHasFriends()
            ? $this->allow()
            : $this->deny('Not authorized to view pending friend request.');
    }

    /**
     * Determine whether the provider can view the pending friend.
     *
     * @param $user
     * @param  PendingFriend  $pending
     * @return Response
     */
    public function view($user, PendingFriend $pending): Response
    {
        return $this->messenger->providerHasFriends()
        && $pending->isRecipientCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view pending friend request.');
    }

    /**
     * Determine whether the provider can accept the pending friend.
     *
     * @param $user
     * @param  PendingFriend  $pending
     * @return Response
     */
    public function update($user, PendingFriend $pending): Response
    {
        return $this->messenger->providerHasFriends()
        && $pending->isRecipientCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to accept pending friend request.');
    }

    /**
     * Determine whether the provider can deny the pending friend.
     *
     * @param $user
     * @param  PendingFriend  $pending
     * @return Response
     */
    public function delete($user, PendingFriend $pending): Response
    {
        return $this->messenger->providerHasFriends()
        && $pending->isRecipientCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to deny pending friend request.');
    }
}
