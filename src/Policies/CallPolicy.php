<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the provider can view the calls.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function viewAny($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view calls.');
    }

    /**
     * Determine whether the provider can view the call.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Response
     */
    public function view($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view call.');
    }

    /**
     * Determine whether the provider can connect to the call socket channel.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Response
     */
    public function socket($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->hasJoinedCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized join that call.');
    }

    /**
     * Determine whether the provider can start a new call.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function create($user, Thread $thread): Response
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
     * @param  Call  $call
     * @param  Thread  $thread
     * @return Response
     */
    public function join($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
        && $call->isActive()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to join that call.');
    }

    /**
     * Determine whether the provider can leave the call.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Response
     */
    public function leave($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->isInCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to leave that call.');
    }

    /**
     * Determine whether the provider can end the call.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Response
     */
    public function end($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->isInCall()
        && ! $call->wasKicked()
        && ($call->isCallAdmin($thread)
            || $thread->isPrivate())
            ? $this->allow()
            : $this->deny('Not authorized to end that call.');
    }

    /**
     * Determine whether the provider can ignore the call.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Response
     */
    public function ignore($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
        && $call->isActive()
        && ! $call->hasJoinedCall()
            ? $this->allow()
            : $this->deny('Not authorized to ignore that call.');
    }

    /**
     * Determine whether the provider can use the call heartbeat.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Response
     */
    public function heartbeat($user, Call $call, Thread $thread): Response
    {
        return $thread->id === $call->thread_id
        && $thread->hasCurrentProvider()
        && $call->isActive()
        && $call->isInCall()
        && ! $call->wasKicked()
            ? $this->allow()
            : $this->deny('Not authorized to be in that call.');
    }
}
