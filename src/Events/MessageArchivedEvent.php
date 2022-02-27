<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;

class MessageArchivedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  Message  $message
     */
    public function __construct(
        public MessengerProvider $provider,
        public Message $message
    ) {
    }
}
