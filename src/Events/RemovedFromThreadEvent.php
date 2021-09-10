<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class RemovedFromThreadEvent
{
    use SerializesModels;

    /**
     * @var Participant
     */
    public Participant $participant;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  Participant  $participant
     */
    public function __construct(MessengerProvider $provider,
                                Thread $thread,
                                Participant $participant)
    {
        $this->provider = $provider;
        $this->thread = $thread;
        $this->participant = $participant;
    }
}
