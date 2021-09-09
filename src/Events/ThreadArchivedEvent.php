<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class ThreadArchivedEvent
{
    use SerializesModels;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * @var null|MessengerProvider
     */
    public ?MessengerProvider $provider;

    /**
     * Create a new event instance.
     *
     * @param  null|MessengerProvider  $provider
     * @param  Thread  $thread
     */
    public function __construct(?MessengerProvider $provider, Thread $thread)
    {
        $this->thread = $thread;
        $this->provider = $provider;
    }
}
