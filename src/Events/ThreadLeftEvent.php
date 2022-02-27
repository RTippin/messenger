<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class ThreadLeftEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  Participant  $participant
     */
    public function __construct(
        public MessengerProvider $provider,
        public Thread $thread,
        public Participant $participant
    ) {
    }
}
