<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Message;

class MessageEditedEvent
{
    use SerializesModels;

    /**
     * @param  Message  $message
     * @param  string|null  $originalBody
     */
    public function __construct(
        public Message $message,
        public ?string $originalBody
    ){}
}
