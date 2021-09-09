<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Models\Thread;

class ThreadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the can view threads.
     *
     * @return Response
     */
    public function viewAny(): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the provider can view the thread.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function view($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view thread.');
    }

    /**
     * Determine whether the provider can join the thread socket channel.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function socket($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
        && ! $thread->isLocked()
        && ! $thread->isAwaitingMyApproval()
            ? $this->allow()
            : $this->deny('Not authorized join thread.');
    }

    /**
     * Determine whether the provider can accept or deny a pending thread request.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function approval($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
        && $thread->isAwaitingMyApproval()
            ? $this->allow()
            : $this->deny('Not authorized to accept or deny the thread approval request.');
    }

    /**
     * Determine whether the provider can use a group thread method.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function groupMethod($user, Thread $thread): Response
    {
        return $thread->isGroup()
        && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view thread.');
    }

    /**
     * Determine whether the provider can add group participants.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function addParticipants($user, Thread $thread): Response
    {
        return $thread->canAddParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to add participants.');
    }

    /**
     * Determine whether the provider can send a knock.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function sendKnock($user, Thread $thread): Response
    {
        return $thread->canKnock()
            ? $this->allow()
            : $this->deny('Not authorized to knock.');
    }

    /**
     * Determine whether the provider can use a private thread method.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function privateMethod($user, Thread $thread): Response
    {
        return $thread->isPrivate() && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view thread.');
    }

    /**
     * Determine whether the provider can create a thread.
     *
     * @return Response
     */
    public function create(): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the provider can manage the group thread settings.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function settings($user, Thread $thread): Response
    {
        return $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
            ? $this->allow()
            : $this->deny('Not authorized to manage settings.');
    }

    /**
     * Determine whether the provider can update the thread.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function update($user, Thread $thread): Response
    {
        return $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
            ? $this->allow()
            : $this->deny('Not authorized to update thread.');
    }

    /**
     * Determine whether the provider can mute or unmute the thread.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function mutes($user, Thread $thread): Response
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
     * @param  Thread  $thread
     * @return Response
     */
    public function leave($user, Thread $thread): Response
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
     * Determine whether the provider can delete the thread.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function delete($user, Thread $thread): Response
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
