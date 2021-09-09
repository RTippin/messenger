<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallStartedEvent
{
    use SerializesModels;

    /**
     * @var Call
     */
    public Call $call;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * Create a new event instance.
     *
     * @param  Call  $call
     * @param  Thread  $thread
     */
    public function __construct(Call $call, Thread $thread)
    {
        $this->call = $call;
        $this->thread = $thread;
    }
}
