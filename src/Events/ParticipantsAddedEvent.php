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
     * @var Thread
     */
    public Thread $thread;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var Collection
     */
    public Collection $participants;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  Collection  $participants
     */
    public function __construct(MessengerProvider $provider,
                                Thread $thread,
                                Collection $participants)
    {
        $this->thread = $thread;
        $this->provider = $provider;
        $this->participants = $participants;
    }
}
