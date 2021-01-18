<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class StoreParticipant extends ThreadParticipantAction
{
    /**
     * Store a single, fresh participant for the provided thread.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var MessengerProvider[1]
     * @var array|null[2]
     * @var bool|null[3]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0]);

        // Whether we store fresh or see if we need
        // to restore existing participant

        if ($parameters[3] ?? null === true) {
            $this->storeOrRestoreParticipant($parameters[1]);
        } else {
            $this->storeParticipant(
                $parameters[1],
                $parameters[2] ?? []
            );
        }

        return $this;
    }
}
