<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallParticipantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the provider can view call participants.
     *
     * @param $user
     * @param Thread $thread
     * @return Response
     */
    public function viewAny($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view participants.');
    }

    /**
     * Determine whether the provider can view the call participant.
     *
     * @param $user
     * @param Thread $thread
     * @return Response
     */
    public function view($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view participant.');
    }

    /**
     * Determine whether the provider can update the call participant.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return Response
     */
    public function update($user, Thread $thread, Call $call): Response
    {
        return $thread->hasCurrentProvider()
        && $thread->isGroup()
        && $call->isActive()
        && $call->isCallAdmin($thread)
        && $call->isInCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to kick / un-kick that participant.');
    }
}
