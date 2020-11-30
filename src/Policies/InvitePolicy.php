<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
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
     * Determine whether the provider can view any models.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function viewAny($user, Thread $thread)
    {
        return $thread->canInviteParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to view thread invite.');
    }

    /**
     * Determine whether the provider can create models.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function create($user, Thread $thread)
    {
        if( ! $thread->canInviteParticipants())
        {
            return $this->deny('Not authorized to create thread invite.');
        }

        return ($this->messenger->getThreadMaxInvitesCount() === 0
            || $this->messenger->getThreadMaxInvitesCount() > $thread->invites()->valid()->count())
            ? $this->allow()
            : $this->deny('Not authorized to create thread invite. You have the max allowed active invites.');
    }

    /**
     * Determine whether the provider can delete the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function delete($user, Thread $thread)
    {
        return $thread->canInviteParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to destroy this invite.');
    }

    /**
     * Determine whether the provider can delete the model.
     *
     * @param $user
     * @param Invite $code
     * @return mixed
     */
    public function join($user, Invite $code)
    {
        return $code->isValid()
        && ! $code->thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to join with this invite.');
    }
}
