<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Models\Thread;

class ThreadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the can view any models.
     *
     * @return mixed
     */
    public function viewAny()
    {
        return $this->allow();
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
            : $this->deny('Not authorized to view that thread.');
    }

    /**
     * Determine whether the provider can join thread socket channel.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function socket($user, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && ! $thread->isLocked()
        && ! $thread->isAwaitingMyApproval()
            ? $this->allow()
            : $this->deny('Not authorized join that thread.');
    }

    /**
     * Determine whether the provider can accept a pending thread request.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function approval($user, Thread $thread)
    {
        return $thread->hasCurrentProvider() && $thread->isAwaitingMyApproval()
            ? $this->allow()
            : $this->deny('Not authorized to accept or deny a request.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function groupMethod($user, Thread $thread)
    {
        return $thread->isGroup() && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view that thread.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function addParticipants($user, Thread $thread)
    {
        return $thread->canAddParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to add participants.');
    }

    /**
     * Determine whether the provider can send a knock.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function sendKnock($user, Thread $thread)
    {
        return $thread->canKnock()
            ? $this->allow()
            : $this->deny('Not authorized to knock.');
    }

    /**
     * Determine whether the provider can view the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function privateMethod($user, Thread $thread)
    {
        return $thread->isPrivate() && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view that thread.');
    }

    /**
     * Determine whether the provider can create models.
     *
     * @return mixed
     */
    public function create()
    {
        return $this->allow();
    }

    /**
     * Determine whether the provider can update the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function settings($user, Thread $thread)
    {
        return $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
            ? $this->allow()
            : $this->deny('Not authorized to manage thread settings.');
    }

    /**
     * Determine whether the provider can update the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function update($user, Thread $thread)
    {
        return $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
            ? $this->allow()
            : $this->deny('Not authorized to update that thread.');
    }

    /**
     * Determine whether the provider can update the model.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function mutes($user, Thread $thread)
    {
        return $thread->hasCurrentProvider()
        && ! $thread->isLocked()
            ? $this->allow()
            : $this->deny('Not authorized to mute/unmute thread.');
    }

    /**
     * Determine whether the provider can leave group thread.
     *
     * @param $user
     * @param Thread $thread
     * @return mixed
     */
    public function leave($user, Thread $thread)
    {
        if ($thread->isGroup() && $thread->hasCurrentProvider()) {
            if ($thread->isLocked()
                || ! $thread->isAdmin()
                || $thread->participants()->count() === 1) {
                return $this->allow();
            }

            return $thread->participants()->admins()->count() > 1
                ? $this->allow()
                : $this->deny('You must promote a new group admin before you may leave.');
        }

        return $this->deny('Not authorized to leave that thread.');
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
        if ($thread->hasCurrentProvider()) {
            if ($thread->hasActiveCall()) {
                return $this->deny('Not authorized to archive thread while there is an ongoing call.');
            }

            return ($thread->isPrivate()
                || ($thread->isGroup()
                    && ! $thread->isLocked()
                    && $thread->isAdmin()))
                ? $this->allow()
                : $this->deny('Not authorized to delete thread.');
        }

        return $this->deny('Not authorized to delete thread.');
    }
}
