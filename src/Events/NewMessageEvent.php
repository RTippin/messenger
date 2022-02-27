<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

class NewMessageEvent
{
    use SerializesModels;

    /**
     * @param  Message  $message
     * @param  Thread  $thread
     * @param  bool  $isGroupAdmin
     * @param  string|null  $senderIp
     */
    public function __construct(
        public Message $message,
        public Thread $thread,
        public bool $isGroupAdmin,
        public ?string $senderIp = null
    ) {
    }
}
