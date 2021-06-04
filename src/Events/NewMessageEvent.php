<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

class NewMessageEvent
{
    use SerializesModels;

    /**
     * @var Message
     */
    public Message $message;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * Create a new event instance.
     *
     * @param Message $message
     * @param Thread $thread
     */
    public function __construct(Message $message, Thread $thread)
    {
        $this->message = $message;
        $this->thread = $thread;
    }
}
