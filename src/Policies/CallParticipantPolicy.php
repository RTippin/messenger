<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallParticipantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the provider can view any models.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function viewAny($user, Thread $thread)
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view participants.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function view($user, Thread $thread)
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view participant.');
    }

    /**
     * Determine whether the provider can update the model.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function update($user, Thread $thread, Call $call)
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
