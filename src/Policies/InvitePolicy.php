<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;

class InvitePolicy
{
    use HandlesAuthorization;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * InvitePolicy constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Determine whether the provider can view thread invites.
     *
     * @param $user
     * @param Thread $thread
     * @return Response
     */
    public function viewAny($user, Thread $thread): Response
    {
        return $thread->canInviteParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to view thread invite.');
    }

    /**
     * Determine whether the provider can create a thread invite.
     *
     * @param $user
     * @param Thread $thread
     * @return Response
     */
    public function create($user, Thread $thread): Response
    {
        if (! $thread->canInviteParticipants()) {
            return $this->deny('Not authorized to create thread invite.');
        }

        return ($this->messenger->getThreadMaxInvitesCount() === 0
            || $this->messenger->getThreadMaxInvitesCount() > $thread->invites()->valid()->count())
            ? $this->allow()
            : $this->deny('Not authorized to create thread invite. You have the max allowed active invites.');
    }

    /**
     * Determine whether the provider can delete the invite.
     *
     * @param $user
     * @param Thread $thread
     * @return Response
     */
    public function delete($user, Thread $thread): Response
    {
        return $thread->canInviteParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to destroy this invite.');
    }

    /**
     * Determine whether the provider can join using the invite.
     *
     * @param $user
     * @param Invite $code
     * @return Response
     */
    public function join($user, Invite $code): Response
    {
        return $code->isValid()
        && $code->thread->canJoinWithInvite()
            ? $this->allow()
            : $this->deny('Not authorized to join with this invite.');
    }
}
