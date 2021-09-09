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
    public bool $isGroupAdmin;

    /**
     * @var string|null
     */
    public ?string $senderIp;

    /**
     * Create a new event instance.
     *
     * @param  Message  $message
     * @param  Thread  $thread
     * @param  bool  $isGroupAdmin
     * @param  string|null  $senderIp
     */
    public function __construct(Message $message,
                                Thread $thread,
                                bool $isGroupAdmin,
                                ?string $senderIp = null)
    {
        $this->message = $message;
        $this->thread = $thread;
        $this->isGroupAdmin = $isGroupAdmin;
        $this->senderIp = $senderIp;
    }
}
