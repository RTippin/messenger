<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallPolicy
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
            : $this->deny('Not authorized to view calls.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function view($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view call.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function socket($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->hasJoinedCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized join that call.');
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
        return $thread->hasCurrentProvider()
        && $thread->canCall()
        && ! $thread->hasActiveCall()
            ? $this->allow()
            : $this->deny('Not authorized to start a call.');
    }

    /**
     * Determine whether the provider join the call.
     *
     * @param $user
     * @param Call $call
     * @param Thread $thread
     * @return mixed
     */
    public function join($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && $call->isActive()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to join this session.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function leave($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->isInCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to leave that session.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function end($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->isInCall()
        && ! $call->wasKicked()
        && ($call->isCallAdmin($thread)
            || $thread->isPrivate())
            ? $this->allow()
            : $this->deny('Not authorized to end that session.');
    }

    /**
     * Determine whether the provider can deny the call.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function ignore($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && $call->isActive()
        && ! $call->hasJoinedCall()
            ? $this->allow()
            : $this->deny('Not authorized to ignore that session.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @param Call $call
     * @return mixed
     */
    public function heartbeat($user, Call $call, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->isInCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to be in that session.');
    }
}
