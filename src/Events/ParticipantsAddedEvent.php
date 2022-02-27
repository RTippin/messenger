<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class ParticipantsAddedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  Collection  $participants
     */
    public function __construct(
        public MessengerProvider $provider,
        public Thread $thread,
        public Collection $participants
    ){}
}
