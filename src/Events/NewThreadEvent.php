<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class NewThreadEvent
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
     * Create a new event instance.
     *
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     */
    public function __construct(MessengerProvider $provider, Thread $thread)
    {
        $this->thread = $thread;
        $this->provider = $provider;
    }
}
