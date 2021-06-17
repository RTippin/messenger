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
     * @var bool
     */
    private bool $isGroupAdmin;

    /**
     * Create a new event instance.
     *
     * @param Message $message
     * @param Thread $thread
     * @param bool $isGroupAdmin
     */
    public function __construct(Message $message,
                                Thread $thread,
                                bool $isGroupAdmin)
    {
        $this->message = $message;
        $this->thread = $thread;
        $this->isGroupAdmin = $isGroupAdmin;
    }
}
