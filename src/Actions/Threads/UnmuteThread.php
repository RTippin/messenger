<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Models\Thread;

class UnmuteThread extends ThreadParticipantAction
{
    /**
     * Unmute the thread for the current participant.
     *
     * @param mixed ...$parameters
     * @var Thread $parameters[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->updateParticipant(
                $this->getThread()->currentParticipant(),
                ['muted' => false]
            );

        return $this;
    }
}
