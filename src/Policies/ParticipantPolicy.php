<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class ParticipantPolicy
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
            : $this->deny('Not authorized to view this participant.');
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
        return $thread->canAddParticipants()
            ? $this->allow()
            : $this->deny('Not authorized to add participants.');
    }

    /**
     * Determine whether the provider can update the model.
     *
     * @param $user
     * @param Participant $participant
     * @param Thread $thread
     * @return mixed
     */
    public function update($user, Participant $participant, Thread $thread)
    {
        return $thread->isGroup()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
        && ! $participant->admin
            ? $this->allow()
            : $this->deny('Not authorized to update participant permissions.');
    }

    /**
     * @param $user
     * @param Participant $participant
     * @param Thread $thread
     * @return mixed
     */
    public function promote($user, Participant $participant, Thread $thread)
    {
        return $thread->isGroup()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
        && ! $participant->admin
            ? $this->allow()
            : $this->deny('Not authorized to promote participant.');
    }

    /**
     * @param $user
     * @param Participant $participant
     * @param Thread $thread
     * @return mixed
     */
    public function demote($user, Participant $participant, Thread $thread)
    {
        return $thread->isGroup()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
        && $participant->admin
            ? $this->allow()
            : $this->deny('Not authorized to demote participant.');
    }

    /**
     * Determine whether the provider can delete the model.
     *
     * @param $user
     * @param Participant $participant
     * @param Thread $thread
     * @return mixed
     */
    public function delete($user, Participant $participant, Thread $thread)
    {
        return $thread->isGroup()
        && $thread->isAdmin()
        && $participant->id !== $thread->currentParticipant()->id
            ? $this->allow()
            : $this->deny('Not authorized to remove participant.');
    }
}
