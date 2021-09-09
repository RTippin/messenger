<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class StoreParticipant extends ThreadParticipantAction
{
    /**
     * Store a single, fresh or restored participant for the provided thread.
     *
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     * @param  array  $params
     * @param  bool  $checkRestore
     * @return $this
     */
    public function execute(Thread $thread,
                            MessengerProvider $provider,
                            array $params = [],
                            bool $checkRestore = false): self
    {
        $this->setThread($thread);

        // Store fresh or see if we need to restore existing participant
        if ($checkRestore) {
            $this->storeOrRestoreParticipant($provider);
        } else {
            $this->storeParticipant($provider, $params);
        }

        return $this;
    }
}
