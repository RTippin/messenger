<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class ThreadApprovalEvent
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
     * @var bool
     */
    public bool $approved;

    /**
     * Create a new event instance.
     *
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     * @param  bool  $approved
     */
    public function __construct(MessengerProvider $provider,
                                Thread $thread,
                                bool $approved)
    {
        $this->thread = $thread;
        $this->provider = $provider;
        $this->approved = $approved;
    }
}
