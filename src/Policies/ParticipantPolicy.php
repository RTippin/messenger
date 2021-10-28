<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class ParticipantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the provider can view participants.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function viewAny($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view participants.');
    }

    /**
     * Determine whether the provider can view the participant.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function view($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view participant.');
    }

    /**
     * Determine whether the provider can create a participant.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function create($user, Thread $thread): Response
    {
        return $thread->canAddParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to add participants.');
    }

    /**
     * Determine whether the provider can update the participant.
     *
     * @param $user
     * @param  Participant  $participant
     * @param  Thread  $thread
     * @return Response
     */
    public function update($user, Participant $participant, Thread $thread): Response
    {
        return $thread->id === $participant->thread_id
        && $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
        && ! $participant->admin
            ? $this->allow()
            : $this->deny('Not authorized to update participant permissions.');
    }

    /**
     * Determine whether the provider can promote the participant.
     *
     * @param $user
     * @param  Participant  $participant
     * @param  Thread  $thread
     * @return Response
     */
    public function promote($user, Participant $participant, Thread $thread): Response
    {
        return $thread->id === $participant->thread_id
        && $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
        && ! $participant->admin
            ? $this->allow()
            : $this->deny('Not authorized to promote participant.');
    }

    /**
     * Determine whether the provider can demote the participant.
     *
     * @param $user
     * @param  Participant  $participant
     * @param  Thread  $thread
     * @return Response
     */
    public function demote($user, Participant $participant, Thread $thread): Response
    {
        return $thread->id === $participant->thread_id
        && $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
        && $participant->admin
            ? $this->allow()
            : $this->deny('Not authorized to demote participant.');
    }

    /**
     * Determine whether the provider can delete the participant.
     *
     * @param $user
     * @param  Participant  $participant
     * @param  Thread  $thread
     * @return Response
     */
    public function delete($user, Participant $participant, Thread $thread): Response
    {
        return $thread->id === $participant->thread_id
        && $thread->isGroup()
        && ! $thread->isLocked()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
            ? $this->allow()
            : $this->deny('Not authorized to remove participant.');
    }
}
