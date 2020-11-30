<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Models\Thread;

class MuteThread extends ThreadParticipantAction
{
    /**
     * Mute the thread for the current participant
     *
     * @param mixed ...$parameters
     * @var Thread $thread $parameters[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->updateParticipant(
                $this->getThread()->currentParticipant(),
                ['muted' => true]
            );

        return $this;
    }
}